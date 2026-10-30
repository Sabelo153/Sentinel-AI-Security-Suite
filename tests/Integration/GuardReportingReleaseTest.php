<?php
declare(strict_types=1);
namespace Sentinel\Tests\Integration;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\{HttpException,Request};
use Sentinel\Tests\FieldFixture;
final class GuardReportingReleaseTest extends TestCase
{
    public function testGuardReportIsOwnedRetrySafeAndPhotosArePermissionProtected():void{
        [$app,$db]=FieldFixture::create();
        $db->execute("INSERT INTO shift_assignments VALUES(1,101,2,1,NULL,NULL,'2026-10-30','2026-10-30 08:00:00','2026-10-30 20:00:00',NULL,'ASSIGNED',NULL),(2,202,3,3,NULL,NULL,'2026-10-30','2026-10-30 08:00:00','2026-10-30 20:00:00',NULL,'ASSIGNED',NULL)");
        $token=$app->jwt()->createToken('2');$headers=['Authorization'=>'Bearer '.$token,'Content-Type'=>'application/json'];
        $call=static function(string $method,string $path,array $body=[])use($app,$headers):array{try{$r=$app->handle(Request::create($method,$path,[],$headers,$method==='GET'?null:json_encode($body)));return[$r->status(),json_decode($r->body(),true)];}catch(HttpException $e){return[$e->statusCode(),[]];}};
        $body=['operation_uuid'=>'11111111-1111-4111-8111-111111111111','action'=>'occurrence','data'=>['shift_assignment_id'=>'1','occurred_at'=>'2026-10-28 08:00:00','text'=>'Radio checked. Outstanding gate repair handed to supervisor.']];
        [$status,$payload]=$call('POST','/api/v1/guard/commands',$body);self::assertSame(200,$status);self::assertSame('occurrence',$payload['data']['report_type']);
        self::assertSame(200,$call('POST','/api/v1/guard/commands',$body)[0]);self::assertSame(1,(int)$db->fetch('SELECT COUNT(*) AS total FROM occurrence_entries')['total']);
        $wrong=[...$body,'operation_uuid'=>'22222222-2222-4222-8222-222222222222','data'=>[...$body['data'],'shift_assignment_id'=>'2']];self::assertSame(404,$call('POST','/api/v1/guard/commands',$wrong)[0]);
        $photo=[...$body,'operation_uuid'=>'33333333-3333-4333-8333-333333333333','data'=>[...$body['data'],'photo_base64'=>base64_encode('<script>not an image</script>')]];self::assertSame(422,$call('POST','/api/v1/guard/commands',$photo)[0]);
        self::assertSame(403,$call('GET','/api/v1/report-photos/occurrence/1')[0]);
        $validPhoto=[...$body,'operation_uuid'=>'55555555-5555-4555-8555-555555555555','data'=>[...$body['data'],'photo_base64'=>'/9j/4AAQSkZJRgABAQAAAQABAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0fAkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1NXW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQFBgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRobHBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVmZ2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExcbHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwDn6KKK7DkP/9k=']];self::assertSame(200,$call('POST','/api/v1/guard/commands',$validPhoto)[0]);self::assertSame(200,$call('POST','/api/v1/guard/commands',$validPhoto)[0]);self::assertSame(1,(int)$db->fetch('SELECT COUNT(*) AS total FROM report_photos')['total']);
        $incident=[...$body,'operation_uuid'=>'44444444-4444-4444-8444-444444444444','action'=>'incident'];self::assertSame(200,$call('POST','/api/v1/guard/commands',$incident)[0]);self::assertSame(1,(int)$db->fetch('SELECT COUNT(*) AS total FROM incidents')['total']);
        self::assertSame(3,(int)$db->fetch('SELECT COUNT(*) AS total FROM mobile_operations')['total']);
    }
    public function testMfaFailureCounterSurvivesProtectedMutationRollback():void{
        [$app,$db]=$fixture=FieldFixture::create();$service=$app->container()->make('session_security_service');$service->setup(['current_password'=>'test-password-at-least-12']);$token=$app->jwt()->createToken('1');
        for($i=0;$i<5;$i++){try{$app->handle(Request::create('POST','/api/v1/account/mfa/confirm',[],['Authorization'=>'Bearer '.$token,'Content-Type'=>'application/json'],'{"code":"invalid"}'));self::fail('Invalid code accepted');}catch(HttpException $e){self::assertSame(401,$e->statusCode());}}
        $row=$db->fetch('SELECT failed_attempts,locked_until FROM account_mfa WHERE user_id=1');self::assertSame(5,(int)$row['failed_attempts']);self::assertGreaterThan(time(),(int)$row['locked_until']);
    }
    public function testMdmManagementRequiresMfaEvenWithRolePermission():void{
        [$app]=FieldFixture::create();$token=$app->jwt()->createToken('1');
        try{$app->handle(Request::create('POST','/api/v1/managed-android',[],['Authorization'=>'Bearer '.$token,'Content-Type'=>'application/json'],'{"device_name":"Site phone"}'));self::fail('Password-only management accepted');}catch(HttpException $e){self::assertSame(403,$e->statusCode());}
    }
}
