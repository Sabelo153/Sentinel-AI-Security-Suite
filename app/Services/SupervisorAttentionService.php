<?php
declare(strict_types=1);
namespace Sentinel\Services;
use Sentinel\Core\{Database,HttpException,RuntimeContext};

final class SupervisorAttentionService
{
    public function __construct(private readonly Database $db,private readonly RuntimeContext $context){}
    public function attention(array $input=[],?\DateTimeImmutable $clock=null):array
    {
        if(array_diff(array_keys($input),['telemetry_minutes','check_in_grace_minutes'])!==[])throw HttpException::unprocessable('Unknown attention filters.');
        $telemetry=$this->minutes($input['telemetry_minutes']??30);$grace=$this->minutes($input['check_in_grace_minutes']??15);
        $clock??=new \DateTimeImmutable('now',new \DateTimeZone('UTC'));$now=$clock->format('Y-m-d H:i:s');$company=$this->context->requireTenant();
        try{
            $shifts=$this->db->fetchAll("SELECT s.shift_assignment_id,s.employee_id,s.site_id,s.shift_start_at,s.check_in_window_end,MAX(a.clock_in_at) AS clock_in_at,MAX(a.clock_out_at) AS clock_out_at,MAX(g.captured_at) AS last_location_at FROM shift_assignments s LEFT JOIN attendance a ON a.shift_assignment_id=s.shift_assignment_id AND a.company_id=s.company_id AND a.employee_id=s.employee_id AND a.deleted_at IS NULL LEFT JOIN gps_locations g ON g.employee_id=s.employee_id AND g.company_id=s.company_id AND g.captured_at>=s.shift_start_at AND g.captured_at<=:now_gps WHERE s.company_id=:company_id AND s.deleted_at IS NULL AND s.assignment_status IN ('ASSIGNED','CONFIRMED','IN_PROGRESS') AND s.shift_start_at<=:now_start AND s.shift_end_at>=:now_end GROUP BY s.shift_assignment_id,s.employee_id,s.site_id,s.shift_start_at,s.check_in_window_end ORDER BY s.shift_assignment_id LIMIT 501",['company_id'=>$company,'now_gps'=>$now,'now_start'=>$now,'now_end'=>$now]);
            $checkpoints=$this->db->fetchAll("SELECT ps.patrol_session_id,ps.started_at,pr.site_id,c.patrol_checkpoint_id,c.checkpoint_order,c.expected_interval_minutes,MAX(CASE WHEN e.event_type='VISITED' THEN e.recorded_at END) AS visited_at FROM patrol_sessions ps INNER JOIN patrol_routes pr ON pr.patrol_route_id=ps.patrol_route_id AND pr.company_id=ps.company_id INNER JOIN patrol_checkpoints c ON c.patrol_route_id=ps.patrol_route_id AND c.company_id=ps.company_id LEFT JOIN patrol_checkpoint_events e ON e.patrol_session_id=ps.patrol_session_id AND e.patrol_checkpoint_id=c.patrol_checkpoint_id AND e.company_id=ps.company_id AND e.recorded_at<=:now WHERE ps.company_id=:company_id AND ps.status='IN_PROGRESS' AND ps.deleted_at IS NULL AND c.status='ACTIVE' AND c.deleted_at IS NULL GROUP BY ps.patrol_session_id,ps.started_at,pr.site_id,c.patrol_checkpoint_id,c.checkpoint_order,c.expected_interval_minutes ORDER BY ps.patrol_session_id,c.checkpoint_order LIMIT 2001",['company_id'=>$company,'now'=>$now]);
            $missed=$this->db->fetchAll("SELECT w.patrol_schedule_id,w.patrol_route_id,w.shift_assignment_id,w.ends_at,pr.site_id FROM patrol_schedule_windows w INNER JOIN patrol_routes pr ON pr.patrol_route_id=w.patrol_route_id AND pr.company_id=w.company_id INNER JOIN shift_assignments s ON s.shift_assignment_id=w.shift_assignment_id AND s.company_id=w.company_id WHERE w.company_id=:company_id AND w.cancelled_at IS NULL AND s.assignment_status<>'CANCELLED' AND w.ends_at<=:now AND NOT EXISTS (SELECT 1 FROM patrol_sessions ps INNER JOIN employee_assignments ea ON ea.employee_assignment_id=ps.employee_assignment_id AND ea.company_id=ps.company_id WHERE ps.company_id=w.company_id AND ps.patrol_route_id=w.patrol_route_id AND ps.shift_assignment_id=w.shift_assignment_id AND ea.employee_id=s.employee_id AND ps.deleted_at IS NULL AND ps.started_at>=w.starts_at AND ps.started_at<w.ends_at) ORDER BY w.ends_at,w.patrol_schedule_id LIMIT 501",['company_id'=>$company,'now'=>$now]);
            $findings=[];$truncated=count($shifts)>500||count($checkpoints)>2000;
            foreach(array_slice($shifts,0,500)as$shift){ if ($shift['clock_out_at'] !== null) continue;
                if($shift['clock_in_at']===null){$deadline=$shift['check_in_window_end']??$this->add($shift['shift_start_at'],$grace);if($now>$deadline)$findings[]=['type'=>'MISSED_CHECK_IN','shift_assignment_id'=>$shift['shift_assignment_id'],'site_id'=>$shift['site_id'],'due_at'=>$deadline,'message'=>'No check-in received. Contact the guard and review offline connectivity before acting.'];}
                else{$last=$shift['last_location_at']??$shift['clock_in_at'];$deadline=$this->add($last,$telemetry);if($now>$deadline)$findings[]=['type'=>'STALE_TELEMETRY','shift_assignment_id'=>$shift['shift_assignment_id'],'site_id'=>$shift['site_id'],'due_at'=>$deadline,'message'=>'No recent location sample received. This is a telemetry gap, not proof of physical inactivity.'];}
            }
            $previous=[];$firstMissing=[];
            foreach(array_slice($checkpoints,0,2000)as$checkpoint){$id=(string)$checkpoint['patrol_session_id'];if(isset($firstMissing[$id]))continue;
                if($checkpoint['visited_at']!==null){$previous[$id]=$checkpoint['visited_at'];continue;}
                $firstMissing[$id]=true;$interval=(int)($checkpoint['expected_interval_minutes']??0);if($interval<1)continue;
                $deadline=$this->add($previous[$id]??$checkpoint['started_at'],$interval);
                if($now>$deadline)$findings[]=['type'=>'OVERDUE_CHECKPOINT','patrol_session_id'=>$checkpoint['patrol_session_id'],'patrol_checkpoint_id'=>$checkpoint['patrol_checkpoint_id'],'site_id'=>$checkpoint['site_id'],'due_at'=>$deadline,'message'=>'Expected checkpoint visit has not arrived. Verify with the guard before escalating.'];
            }
            foreach(array_slice($missed,0,500)as$window)$findings[]=['type'=>'MISSED_PATROL','patrol_schedule_id'=>$window['patrol_schedule_id'],'patrol_route_id'=>$window['patrol_route_id'],'shift_assignment_id'=>$window['shift_assignment_id'],'site_id'=>$window['site_id'],'due_at'=>$window['ends_at'],'message'=>'No patrol start was received in the scheduled window. Check for offline synchronization and contact the guard before escalating.'];
            return ['evaluated_at'=>$now,'policy'=>['telemetry_minutes'=>$telemetry,'check_in_grace_minutes'=>$grace],'truncated'=>$truncated||count($missed)>500||count($findings)>500,'findings'=>array_slice($findings,0,500)];
        }catch(\PDOException){throw HttpException::serviceUnavailable('Supervisor attention data is unavailable.');}
    }
    private function minutes(mixed $value):int{if((!is_int($value)&&!is_string($value))||!ctype_digit((string)$value)||(int)$value<1||(int)$value>1440)throw HttpException::unprocessable('Threshold must be between 1 and 1440 minutes.');return(int)$value;}
    private function add(string $value,int $minutes):string{return(new \DateTimeImmutable($value,new \DateTimeZone('UTC')))->modify('+'.$minutes.' minutes')->format('Y-m-d H:i:s');}
}
