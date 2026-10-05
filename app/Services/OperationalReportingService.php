<?php
declare(strict_types=1);
namespace Sentinel\Services;
use Sentinel\Core\{Database,HttpException,RuntimeContext};

final class OperationalReportingService
{
    private const REPORTS=[
        'incidents'=>['incidents','incident_id','incident_id,incident_number,site_id,incident_type,severity,status,occurred_at,reported_at','occurred_at','status',true,true],
        'occurrence-book'=>['occurrence_entries','occurrence_entry_id','occurrence_entry_id,entry_reference,site_id,entry_type,occurred_at,handover_required','occurred_at',null,true,false],
        'visitor-visits'=>['visitor_visits','visitor_visit_id','visitor_visit_id,visitor_id,site_id,purpose,visit_status,arrival_at,departure_at,created_at','created_at','visit_status',true,false],
        'access-events'=>['access_events','access_event_id','access_event_id,site_id,vehicle_id,visitor_visit_id,employee_id,event_type,direction,authorization_status,occurred_at','occurred_at','authorization_status',true,false],
        'inspections'=>['inspections','inspection_id','inspection_id,site_id,inspection_type,status,overall_outcome,scheduled_at,completed_at','created_at','status',true,true],
        'alerts'=>['alerts','alert_id','alert_id,alert_type,severity,status,occurred_at,acknowledged_at,resolved_at','occurred_at','status',false,false],
    ];
    public function __construct(private readonly Database $db,private readonly RuntimeContext $context,private readonly CsvExportService $csv){}
    public function report(string $name,array $filters):array
    {
        $def=$this->definition($name);[$table,$key,$columns,$date,$status,$site,$soft]=$def;
        if(array_diff(array_keys($filters),['site_id','status','date_from','date_to','limit'])!==[])throw HttpException::unprocessable('Unknown report filters.');
        $where=['company_id=:company_id'];$params=['company_id'=>$this->context->requireTenant()];
        if($soft)$where[]='deleted_at IS NULL';
        if(array_key_exists('site_id',$filters)){
            if(!$site||(!is_int($filters['site_id'])&&!is_string($filters['site_id']))||!preg_match('/^[1-9][0-9]{0,18}$/D',(string)$filters['site_id']))throw HttpException::unprocessable('Invalid site filter.');
            $where[]='site_id=:site_id';$params['site_id']=$filters['site_id'];
        }
        if(array_key_exists('status',$filters)){
            $statuses=match($name){'incidents'=>['OPEN','INVESTIGATING','RESOLVED','CLOSED','CANCELLED'],'visitor-visits'=>['PRE_REGISTERED','AUTHORIZED','CHECKED_IN','CHECKED_OUT','CANCELLED','DENIED'],'access-events'=>['AUTHORIZED','DENIED','PENDING'],'inspections'=>['DRAFT','IN_PROGRESS','COMPLETED','REVIEWED','CANCELLED'],'alerts'=>['OPEN','ACKNOWLEDGED','RESOLVED','DISMISSED'],default=>[]};
            if($status===null||!in_array($filters['status'],$statuses,true))throw HttpException::unprocessable('Invalid report status.');
            $where[]="$status=:status";$params['status']=$filters['status'];
        }
        foreach(['date_from','date_to']as$field)if(array_key_exists($field,$filters)){
            $value=$filters[$field];$parsed=is_string($value)?\DateTimeImmutable::createFromFormat('!Y-m-d',$value):false;
            if($parsed===false||$parsed->format('Y-m-d')!==$value)throw HttpException::unprocessable('Invalid report date.');
            $where[]=$date.($field==='date_from'?' >= :':' < :').$field;
            $params[$field]=$field==='date_from'?$value.' 00:00:00':$parsed->modify('+1 day')->format('Y-m-d H:i:s');
        }
        if(isset($filters['date_from'],$filters['date_to'])&&$filters['date_from']>$filters['date_to'])throw HttpException::unprocessable('Invalid date range.');
        $limit=$filters['limit']??500;
        if((!is_int($limit)&&!is_string($limit))||!ctype_digit((string)$limit)||(int)$limit<1||(int)$limit>1000)throw HttpException::unprocessable('Limit must be between 1 and 1000.');
        return $this->safe(fn()=>$this->db->fetchAll("SELECT $columns FROM $table WHERE ".implode(' AND ',$where)." ORDER BY $date DESC,$key DESC LIMIT ".(int)$limit,$params));
    }
    public function csv(string $name,array $filters):string{return$this->csv->export($this->report($name,$filters),explode(',',$this->definition($name)[2]));}
    public function summary():array
    {
        $company=$this->context->requireTenant();
        $queries=[
            'open_incidents'=>"SELECT COUNT(*) AS total FROM incidents WHERE company_id=:company_id AND deleted_at IS NULL AND status IN ('OPEN','INVESTIGATING')",
            'occurrence_entries'=>"SELECT COUNT(*) AS total FROM occurrence_entries WHERE company_id=:company_id",
            'visitors_on_site'=>"SELECT COUNT(*) AS total FROM visitor_visits WHERE company_id=:company_id AND visit_status='CHECKED_IN'",
            'inspections_in_progress'=>"SELECT COUNT(*) AS total FROM inspections WHERE company_id=:company_id AND deleted_at IS NULL AND status='IN_PROGRESS'",
            'open_alerts'=>"SELECT COUNT(*) AS total FROM alerts WHERE company_id=:company_id AND status='OPEN'",
        ];
        return$this->safe(function()use($queries,$company):array{$result=[];foreach($queries as$key=>$sql)$result[$key]=(int)$this->db->fetch($sql,['company_id'=>$company])['total'];return$result;});
    }
    private function definition(string $name):array{$this->context->requireTenant();return self::REPORTS[$name]??throw HttpException::notFound('Operational report not found.');}
    private function safe(callable $operation):mixed{try{return$operation();}catch(\PDOException){throw HttpException::serviceUnavailable('Operational reporting is unavailable.');}}
}
