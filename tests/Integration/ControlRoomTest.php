<?php
declare(strict_types=1);
namespace Sentinel\Tests\Integration;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\HttpException;
use Sentinel\Tests\FieldFixture;

final class ControlRoomTest extends TestCase
{
    private function fixture(): array {
        [$app,$db,$context]=FieldFixture::create();
        $db->execute('ALTER TABLE sites ADD COLUMN contract_id INTEGER');
        $db->execute('ALTER TABLE employee_assignments ADD COLUMN position_id INTEGER');$db->execute('ALTER TABLE employee_assignments ADD COLUMN assignment_status TEXT');$db->execute('ALTER TABLE employee_assignments ADD COLUMN deleted_at TEXT');
        $db->execute('CREATE TABLE positions(position_id INTEGER PRIMARY KEY,company_id INTEGER,position_category TEXT,position_status TEXT,deleted_at TEXT)');$db->execute("INSERT INTO positions VALUES(1,101,'GUARD','ACTIVE',NULL)");$db->execute("INSERT INTO employee_assignments VALUES(1,101,1,1,'ACTIVE',NULL),(2,101,2,1,'ACTIVE',NULL)");
        $db->execute('CREATE TABLE contracts(contract_id INTEGER PRIMARY KEY,company_id INTEGER,status TEXT,start_date TEXT,end_date TEXT,deleted_at TEXT)');
        $db->execute("INSERT INTO contracts VALUES(1,101,'ACTIVE','2026-01-01',NULL,NULL),(3,202,'ACTIVE','2026-01-01',NULL,NULL)");$db->execute('UPDATE sites SET contract_id=site_id');
        $db->execute('CREATE TABLE posts(post_id INTEGER PRIMARY KEY,company_id INTEGER,site_id INTEGER,status TEXT,deleted_at TEXT)');$db->execute("INSERT INTO posts VALUES(1,101,1,'ACTIVE',NULL),(3,202,3,'ACTIVE',NULL)");
        return [$app->container()->make('control_room_service'),$db,$context,$app];
    }
    private function rejects(callable $fn,int $status): void {try{$fn();self::fail('Expected rejection');}catch(HttpException $e){self::assertSame($status,$e->statusCode());}}
    private function shift($db,int $id=1,int $employee=2,string $start='-1 hour',string $end='+4 hours'): void {
        $db->execute("INSERT INTO shift_assignments(shift_assignment_id,company_id,employee_id,site_id,post_id,shift_start_at,shift_end_at,assignment_status) VALUES(:id,101,:employee,1,1,:start,:end,'ASSIGNED')",['id'=>$id,'employee'=>$employee,'start'=>gmdate('Y-m-d H:i:s',strtotime($start)),'end'=>gmdate('Y-m-d H:i:s',strtotime($end))]);
    }
    private function incident($db): void {$db->execute("INSERT INTO incidents(incident_uuid,company_id,site_id,incident_number,incident_type,incident_category,occurred_at,description) VALUES('incident',101,1,'INC-1','FIELD','FIELD',:now,'Test incident')",['now'=>gmdate('Y-m-d H:i:s')]);}
    public function testDispatchTransitionsRequireAssignedResponderAndCurrentRevision(): void {
        [$s,$db,$ctx]=$this->fixture();$this->incident($db);$id=$s->createDispatch(['incident_id'=>1,'responder_user_id'=>2,'response_due_at'=>gmdate('Y-m-d H:i:s',time()+600)])['id'];
        $this->rejects(fn()=>$s->dispatchAction($id,'arrive',['expected_revision'=>1]),409);$this->rejects(fn()=>$s->dispatchAction($id,'accept',['expected_revision'=>1],true),404);
        $ctx->setUser('2');self::assertCount(1,$s->selfWorkspace()['dispatch']);self::assertSame('ACCEPTED',$s->dispatchAction($id,'accept',['expected_revision'=>1],true)['state']);$this->rejects(fn()=>$s->dispatchAction($id,'arrive',['expected_revision'=>1],true),409);
        self::assertSame('ARRIVED',$s->dispatchAction($id,'arrive',['expected_revision'=>2],true)['state']);$ctx->setUser('1');self::assertSame('CLOSED',$s->dispatchAction($id,'close',['expected_revision'=>3,'resolution'=>'Responders checked site.'])['state']);
        $ctx->setTenantId(202);self::assertSame([],$s->list('dispatch'));$this->rejects(fn()=>$s->dispatchAction($id,'accept',['expected_revision'=>4]),404);
    }
    public function testDispatchOverdueSignalsAreDeduplicatedAndEscalatedInApp(): void {
        [$s,$db,$ctx,$app]=$this->fixture();$this->incident($db);$s->createDispatch(['incident_id'=>1,'responder_user_id'=>2,'response_due_at'=>gmdate('Y-m-d H:i:s',time()+60)]);$db->execute("UPDATE dispatch_jobs SET response_due_at='2026-01-01 00:00:00'");$s->tick();$s->tick();self::assertCount(1,$s->list('signals'));
        $field=$app->container()->make('field_operations_service');$field->createRule(['recipient_user_id'=>1,'backup_user_id'=>2,'escalate_minutes'=>1]);$db->beginTransaction();$field->escalationTick();$db->commit();self::assertSame(1,(int)$db->fetch('SELECT COUNT(*) AS n FROM notifications')['n']);
    }
    public function testDuressCodeIsHashedHiddenFromGuardAndCreatesSignalImmediately(): void {
        [$s,$db,$ctx]=$this->fixture();$this->shift($db);$plan=$s->createLonePlan(['shift_assignment_id'=>1,'user_id'=>2,'interval_minutes'=>15]);$stored=$db->fetch('SELECT duress_hash FROM lone_worker_plans');self::assertTrue(password_verify($plan['duress_code'],$stored['duress_hash']));self::assertStringNotContainsString($plan['duress_code'],json_encode($s->list('lone-workers')));
        $this->rejects(fn()=>$s->checkin($plan['id'],['code'=>'OK']),404);$ctx->setUser('2');$normal=$s->checkin($plan['id'],['code'=>'OK']);$duress=$s->checkin($plan['id'],['code'=>$plan['duress_code']]);self::assertSame(array_keys($normal),array_keys($duress));self::assertTrue($duress['received']);self::assertStringNotContainsString('DURESS',json_encode($s->selfWorkspace()));self::assertSame('DURESS',$s->list('signals')[0]['signal_type']);
    }
    public function testMissedCheckinRemainsVisibleAtShiftEndAndClosureStopsNewSignals(): void {
        [$s,$db]=$this->fixture();$this->shift($db);$p=$s->createLonePlan(['shift_assignment_id'=>1,'user_id'=>2,'interval_minutes'=>5]);$db->execute("UPDATE lone_worker_plans SET next_due_at='2026-01-01 00:00:00',ends_at='2026-01-01 01:00:00'");$s->tick();$s->tick();self::assertCount(1,$s->list('signals'));$s->closePlan($p['id'],['reason'=>'Supervisor completed welfare check.']);$s->acknowledge((string)$s->list('signals')[0]['signal_id']);self::assertSame(0,$s->tick()['unacknowledged_signals']);
    }
    public function testCoverageUsesMinimumConcurrentHeadcountRatherThanTotalShifts(): void {
        [$s,$db]=$this->fixture();$this->shift($db,1,1,'+1 hour','+2 hours');$this->shift($db,2,2,'+3 hours','+4 hours');$s->createCoverage(['post_id'=>1,'starts_at'=>gmdate('Y-m-d H:i:s',strtotime('+1 hour')),'ends_at'=>gmdate('Y-m-d H:i:s',strtotime('+4 hours')),'required_guards'=>1]);self::assertSame(0,$s->coverage()[0]['minimum_scheduled_guards']);self::assertSame(1,$s->coverage()[0]['uncovered_guards']);$this->rejects(fn()=>$s->createCoverage(['post_id'=>3,'starts_at'=>gmdate('Y-m-d H:i:s'),'ends_at'=>gmdate('Y-m-d H:i:s',time()+60),'required_guards'=>1]),404);
    }
    public function testReliefRejectsOverlapAndKeepsPreviousAssignmentHistory(): void {
        [$s,$db]=$this->fixture();$this->shift($db,1,1);$this->shift($db,2,2);$body=['expected_employee_id'=>1,'replacement_employee_id'=>2,'reason'=>'Original guard unavailable.'];$this->rejects(fn()=>$s->relief('1',$body),409);$db->execute("UPDATE shift_assignments SET assignment_status='CANCELLED' WHERE shift_assignment_id=2");self::assertTrue($s->relief('1',$body)['assigned']);self::assertSame(1,(int)$s->list('relief-history')[0]['previous_employee_id']);$this->rejects(fn()=>$s->relief('1',$body),409);
    }
    public function testGateRetryIsIdempotentAndUuidCannotBeReusedForDifferentData(): void {
        [$s,$db,$ctx]=$this->fixture();$body=['operation_uuid'=>'11111111-1111-4111-8111-111111111111','site_id'=>1,'visitor_name'=>'Delivery contact','vehicle_registration'=>'abc 123','purpose'=>'Parcel delivery','direction'=>'ENTRY','authorization'=>'PENDING','occurred_at'=>gmdate('Y-m-d H:i:s')];$first=$s->gate($body);self::assertSame($first['id'],$s->gate($body)['id']);self::assertCount(1,$s->list('gate'));$this->rejects(fn()=>$s->gate([...$body,'purpose'=>'Changed']),409);$ctx->setTenantId(202);self::assertSame([],$s->list('gate'));$this->rejects(fn()=>$s->gate($body),404);
    }
    public function testInvoiceSnapshotsApprovedAttendanceAndFlagsLaterChanges(): void {
        [$s,$db,$ctx,$app]=$this->fixture();$date=gmdate('Y-m-d');$db->execute("INSERT INTO attendance VALUES(1,101,1,1,:day,:start,:end,NULL,NULL)",['day'=>$date,'start'=>$date.' 08:00:00','end'=>$date.' 10:00:00']);$app->container()->make('field_operations_service')->approveAttendance('1');$s->rate(['contract_id'=>1,'site_id'=>1,'hourly_minor'=>12500,'currency'=>'ZAR','effective_from'=>'2026-01-01']);$invoice=$s->invoice(['contract_id'=>1,'site_id'=>1,'date_from'=>$date,'date_to'=>$date]);self::assertSame(25000,$invoice['subtotal_minor']);self::assertFalse($s->invoiceDetail($invoice['id'])['attendance_changed']);$db->execute('UPDATE attendance SET clock_out_at=:end WHERE attendance_id=1',['end'=>$date.' 11:00:00']);self::assertTrue($s->invoiceDetail($invoice['id'])['attendance_changed']);$this->rejects(fn()=>$s->invoice(['contract_id'=>1,'site_id'=>1,'date_from'=>$date,'date_to'=>$date]),409);$ctx->setTenantId(202);$this->rejects(fn()=>$s->invoiceDetail($invoice['id']),404);
    }
    public function testReadinessStartsUnknownAndTracksWorkerFailureAndBackupEvidence(): void {
        [$s]=$this->fixture();$data=$s->readiness();self::assertSame('UNKNOWN',$data['workers'][0]['state']);self::assertSame('UNKNOWN',$data['backup']['state']);$s->tick();self::assertSame('OK',$s->readiness()['workers'][0]['state']);$s->recordWorkerFailure('control-room');self::assertSame('FAILED',$s->readiness()['workers'][0]['state']);$s->backup(['backup_reference'=>'Vault job 123','backed_up_at'=>gmdate('Y-m-d H:i:s',time()-600),'restored_at'=>gmdate('Y-m-d H:i:s',time()-60)]);self::assertSame('RECORDED',$s->readiness()['backup']['state']);
    }
    public function testDeviceHealthValidatesBatteryAndFlagsOldVersionsAndStaleContact(): void {
        [$s,$db,$ctx]=$this->fixture();$s->healthReport(['installation_id'=>'11111111-1111-4111-8111-111111111111','battery_percent'=>10,'version_code'=>15]);$phone=$s->deviceHealth(16)['phones'][0];self::assertTrue($phone['low_battery']);self::assertTrue($phone['outdated']);self::assertFalse($phone['missing_contact']);$db->execute("UPDATE phone_health SET received_at='2026-01-01 00:00:00'");self::assertTrue($s->deviceHealth(16)['phones'][0]['missing_contact']);$this->rejects(fn()=>$s->healthReport(['installation_id'=>'11111111-1111-4111-8111-111111111111','battery_percent'=>101,'version_code'=>16]),422);$ctx->setTenantId(202);self::assertSame([],$s->deviceHealth(16)['phones']);
    }
    public function testGuardRoutesAreOwnedAndCannotAccessManagerDataOrForgeGateSite(): void {
        [$s,$db,$ctx,$app]=$this->fixture();$this->shift($db);$token=$app->jwt()->createToken('2');$headers=['Authorization'=>'Bearer '.$token,'Content-Type'=>'application/json'];
        $call=static function(string $method,string $path,array $body=[])use($app,$headers):int {try{return $app->handle(\Sentinel\Core\Request::create($method,$path,[],$headers,$method==='GET'?null:json_encode($body)))->status();}catch(HttpException $e){return $e->statusCode();}};
        self::assertSame(200,$call('GET','/api/v1/guard/control-room'));self::assertSame(403,$call('GET','/api/v1/control-room/readiness'));self::assertSame(403,$call('GET','/api/v1/control-room/invoices'));self::assertSame(403,$call('POST','/api/v1/control-room/gate',[]));
        $body=['operation_uuid'=>'22222222-2222-4222-8222-222222222222','action'=>'gate_capture','data'=>['capture_uuid'=>'33333333-3333-4333-8333-333333333333','shift_assignment_id'=>1,'visitor_name'=>'Delivery contact','vehicle_registration'=>'','purpose'=>'Delivery','direction'=>'ENTRY','authorization'=>'PENDING','occurred_at'=>gmdate('Y-m-d H:i:s')]];
        self::assertSame(200,$call('POST','/api/v1/guard/commands',$body));self::assertSame(200,$call('POST','/api/v1/guard/commands',$body));self::assertSame(1,(int)$db->fetch('SELECT COUNT(*) AS n FROM gate_register')['n']);self::assertSame(1,(int)$db->fetch('SELECT site_id FROM gate_register')['site_id']);
        $body['operation_uuid']='44444444-4444-4444-8444-444444444444';$body['data']['site_id']=3;self::assertSame(422,$call('POST','/api/v1/guard/commands',$body));
    }
    public function testInvoiceDoesNotIncludeStaleApprovalsOrInventMissingRates(): void {
        [$s,$db,$ctx,$app]=$this->fixture();$date=gmdate('Y-m-d');$db->execute('INSERT INTO attendance VALUES(1,101,1,1,:day,:start,:end,NULL,NULL)',['day'=>$date,'start'=>$date.' 08:00:00','end'=>$date.' 10:00:00']);$app->container()->make('field_operations_service')->approveAttendance('1');$input=['contract_id'=>1,'site_id'=>1,'date_from'=>$date,'date_to'=>$date];$this->rejects(fn()=>$s->invoice($input),422);$s->rate(['contract_id'=>1,'site_id'=>1,'hourly_minor'=>10000,'currency'=>'ZAR','effective_from'=>'2026-01-01']);$db->execute('UPDATE attendance SET clock_out_at=:end WHERE attendance_id=1',['end'=>$date.' 11:00:00']);$this->rejects(fn()=>$s->invoice($input),422);self::assertSame([],$s->list('invoices'));
    }
    public function testVoidedInvoiceHistoryAllowsCorrectedReplacementDraft(): void {
        [$s,$db,$ctx,$app]=$this->fixture();$date=gmdate('Y-m-d');$db->execute('INSERT INTO attendance VALUES(1,101,1,1,:day,:start,:end,NULL,NULL)',['day'=>$date,'start'=>$date.' 08:00:00','end'=>$date.' 10:00:00']);$app->container()->make('field_operations_service')->approveAttendance('1');$s->rate(['contract_id'=>1,'site_id'=>1,'hourly_minor'=>10000,'currency'=>'ZAR','effective_from'=>'2026-01-01']);$input=['contract_id'=>1,'site_id'=>1,'date_from'=>$date,'date_to'=>$date];$old=$s->invoice($input);$s->voidInvoice($old['id'],['reason'=>'Attendance correction requires replacement draft.']);$replacement=$s->invoice($input);self::assertNotSame($old['id'],$replacement['id']);self::assertSame('VOID',$s->invoiceDetail($old['id'])['state']);self::assertCount(2,$s->list('invoices'));$this->rejects(fn()=>$s->voidInvoice($old['id'],['reason'=>'Repeat']),409);
    }
}
