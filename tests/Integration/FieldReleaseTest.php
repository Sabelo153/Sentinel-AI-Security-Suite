<?php
declare(strict_types=1);
namespace Sentinel\Tests\Integration;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\HttpException;
use Sentinel\Tests\FieldFixture;
final class FieldReleaseTest extends TestCase
{
    private function rejects(callable $operation,int $status):void{try{$operation();self::fail('Expected rejection');}catch(HttpException $e){self::assertSame($status,$e->statusCode());}}
    public function testManagedEnrollmentPolicyAndRecoveryCredentialsAreScoped():void{
        [$app,$db,$context]=FieldFixture::create();$service=$app->container()->make('managed_android_service');
        $created=$service->create(['device_name'=>'Site phone']);$id=$created['managed_device_id'];$uuid='11111111-1111-4111-8111-111111111111';
        $context->setTenantId(202);$this->rejects(fn()=>$service->enroll(['enrollment_code'=>$created['enrollment_code'],'installation_id'=>$uuid]),403);$context->setTenantId(101);
        $bound=$service->enroll(['enrollment_code'=>$created['enrollment_code'],'installation_id'=>$uuid]);$this->rejects(fn()=>$service->enroll(['enrollment_code'=>$created['enrollment_code'],'installation_id'=>$uuid]),403);
        $policy=['expected_revision'=>1,'enabled'=>true,'allowed_packages'=>['com.example.radio'],'block_status_bar'=>true,'block_install'=>true];$service->policy($id,$policy);
        $this->rejects(fn()=>$service->policy($id,$policy),409);$this->rejects(fn()=>$service->policy($id,[...$policy,'expected_revision'=>2,'allowed_packages'=>['com.android.settings']]),422);
        $poll=$service->poll(['installation_id'=>$uuid,'device_token'=>$bound['device_token'],'reported_revision'=>0,'reported_state'=>'NOT_DEVICE_OWNER']);self::assertSame(2,$poll['revision']);self::assertTrue($poll['policy']['block_status_bar']);
        self::assertSame(['com.sentinel.guard','com.example.radio'],$poll['policy']['allowed_packages']);self::assertSame(hash('sha256',$poll['recovery_salt'].':'.$created['recovery_code']),$poll['recovery_hash']);
        $stored=json_encode($db->fetch('SELECT * FROM managed_android'));self::assertStringNotContainsString($bound['device_token'],$stored);self::assertStringNotContainsString($created['enrollment_code'],$stored);self::assertStringNotContainsString($created['recovery_code'],$stored);
        $this->rejects(fn()=>$service->poll(['installation_id'=>$uuid,'device_token'=>str_repeat('0',64)]),401);
        // Installation credentials still retrieve release policies after the guard signs out.
        $context->setUser(null);self::assertSame(2,$service->poll(['installation_id'=>$uuid,'device_token'=>$bound['device_token']])['revision']);
    }
    public function testEquipmentCannotBeIssuedTwiceAndCrossTenantReferencesFail():void{
        [$app,$db]=$fixture=FieldFixture::create();$service=$app->container()->make('field_operations_service');$db->beginTransaction();
        $item=$service->createEquipment(['asset_code'=>'RAD-1','equipment_name'=>'Radio']);$handover=$service->issue(['equipment_id'=>$item['id'],'employee_id'=>1,'site_id'=>1,'issue_condition'=>'Working']);
        $this->rejects(fn()=>$service->issue(['equipment_id'=>$item['id'],'employee_id'=>2,'site_id'=>1,'issue_condition'=>'Working']),409);
        $this->rejects(fn()=>$service->createCompliance(['employee_id'=>3,'requirement_name'=>'Training','due_on'=>'2026-11-01']),404);
        $service->returnEquipment($handover['id'],['return_condition'=>'Working']);$this->rejects(fn()=>$service->returnEquipment($handover['id'],['return_condition'=>'Working']),409);$db->commit();self::assertCount(1,$service->list('handovers'));
    }
    public function testReplacingEnrollmentRequiresConfirmedReleaseAndRevokesOldDeviceCredential():void{
        [$app]=FieldFixture::create();$service=$app->container()->make('managed_android_service');$created=$service->create(['device_name'=>'Radio phone']);$id=$created['managed_device_id'];$uuid='aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';$bound=$service->enroll(['enrollment_code'=>$created['enrollment_code'],'installation_id'=>$uuid]);
        $policy=['expected_revision'=>1,'enabled'=>true,'allowed_packages'=>[],'block_status_bar'=>true,'block_install'=>true];$service->policy($id,$policy);$this->rejects(fn()=>$service->replaceEnrollment($id,['confirm_device_id'=>$id]),409);
        $service->policy($id,[...$policy,'expected_revision'=>2,'enabled'=>false]);$this->rejects(fn()=>$service->replaceEnrollment($id,['confirm_device_id'=>$id]),409);
        $service->poll(['installation_id'=>$uuid,'device_token'=>$bound['device_token'],'reported_revision'=>3,'reported_state'=>'RELEASED']);$replacement=$service->replaceEnrollment($id,['confirm_device_id'=>$id]);self::assertNotSame($created['enrollment_code'],$replacement['enrollment_code']);
        $this->rejects(fn()=>$service->poll(['installation_id'=>$uuid,'device_token'=>$bound['device_token']]),401);self::assertNotEmpty($service->enroll(['installation_id'=>$uuid,'enrollment_code'=>$replacement['enrollment_code']])['device_token']);
    }
    public function testPayrollApprovalBecomesStaleAfterAttendanceCorrection():void{
        [$app,$db]=FieldFixture::create();$service=$app->container()->make('field_operations_service');
        $db->execute("INSERT INTO attendance VALUES(1,101,1,1,'2026-10-30','2026-10-30 08:00:00','2026-10-30 20:00:00',NULL,NULL)");$service->approveAttendance('1');$range=['date_from'=>'2026-10-30','date_to'=>'2026-10-30'];$report=$service->payroll($range);self::assertSame('12.00',$report['rows'][0]['worked_hours']);
        $db->execute("UPDATE attendance SET clock_out_at='2026-10-30 19:00:00' WHERE attendance_id=1");$report=$service->payroll($range);self::assertCount(0,$report['rows']);self::assertSame(1,$report['stale_approvals_excluded']);
    }
    public function testClientPortalOnlyReturnsPublishedMappedClientReports():void{
        [$app,$db,$context]=FieldFixture::create();$service=$app->container()->make('field_operations_service');
        $service->publish(['site_id'=>1,'title'=>'Approved daily summary','report_text'=>'Patrols complete.']);$service->publish(['site_id'=>2,'title'=>'Other client private summary','report_text'=>'Internal detail.']);$service->grantPortal(['user_id'=>2,'client_id'=>1]);$context->setUser('2');self::assertCount(1,$service->portal());self::assertSame('Approved daily summary',$service->portal()[0]['title']);$context->setUser('3');$context->setTenantId(202);self::assertSame([],$service->portal());
    }
    public function testClientDraftCountsAreScopedAndDoNotPublishAutomatically():void{
        [$app,$db]=FieldFixture::create();$service=$app->container()->make('field_operations_service');$db->execute('INSERT INTO patrol_routes VALUES(1,101,1),(2,101,2),(3,202,3)');
        $db->execute("INSERT INTO patrol_sessions VALUES(1,101,1,NULL,NULL,'2026-10-30 08:00:00','COMPLETED',NULL),(2,101,2,NULL,NULL,'2026-10-30 08:00:00','COMPLETED',NULL),(3,202,3,NULL,NULL,'2026-10-30 08:00:00','COMPLETED',NULL)");
        $draft=$service->reportDraft(['site_id'=>1,'date_from'=>'2026-10-30','date_to'=>'2026-10-30']);self::assertFalse($draft['published']);self::assertStringContainsString('currently completed: 1',$draft['report_text']);self::assertSame([],$service->portal());
        $this->rejects(fn()=>$service->reportDraft(['site_id'=>3,'date_from'=>'2026-10-30','date_to'=>'2026-10-30']),404);
    }
    public function testComplianceRenewalRequiresNewEvidenceReview():void{
        [$app,$db]=FieldFixture::create();$service=$app->container()->make('field_operations_service');$item=$service->createCompliance(['employee_id'=>1,'requirement_name'=>'Training','reference_number'=>'REF-1','due_on'=>'2026-11-01']);$service->verifyCompliance($item['id']);self::assertNotNull($service->list('compliance')[0]['verified_at']);$service->renewCompliance($item['id'],['due_on'=>'2027-11-01','reference_number'=>'REF-2']);self::assertNull($service->list('compliance')[0]['verified_at']);
    }
    public function testEscalationsAreIdempotentAndAcknowledgementPreventsBackupDelivery():void{
        [$app,$db]=FieldFixture::create();$service=$app->container()->make('field_operations_service');$db->beginTransaction();
        $service->createRule(['recipient_user_id'=>1,'backup_user_id'=>2,'escalate_minutes'=>1]);$service->createCompliance(['employee_id'=>1,'requirement_name'=>'Review training','due_on'=>gmdate('Y-m-d')]);
        self::assertSame(1,$service->escalationTick()['created']);self::assertSame(0,$service->escalationTick()['created']);
        $id=$db->fetch('SELECT escalation_id FROM escalation_events')['escalation_id'];$service->acknowledge((string)$id);$db->execute("UPDATE escalation_events SET created_at='2026-01-01 00:00:00'");self::assertSame(0,$service->escalationTick()['escalated']);
        $service->createCompliance(['employee_id'=>2,'requirement_name'=>'Second training review','due_on'=>gmdate('Y-m-d')]);self::assertSame(1,$service->escalationTick()['created']);$db->execute("UPDATE escalation_events SET created_at='2026-01-01 00:00:00'");self::assertSame(1,$service->escalationTick()['escalated']);self::assertSame(0,$service->escalationTick()['escalated']);$db->commit();self::assertSame(3,(int)$db->fetch('SELECT COUNT(*) AS total FROM notifications')['total']);
    }
}
