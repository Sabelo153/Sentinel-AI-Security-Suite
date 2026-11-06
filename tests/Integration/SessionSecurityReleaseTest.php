<?php
declare(strict_types=1);
namespace Sentinel\Tests\Integration;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\{HttpException,Request};
use Sentinel\Security\Totp;
use Sentinel\Tests\FieldFixture;
final class SessionSecurityReleaseTest extends TestCase
{
    public function testRfc6238ReferenceVectors():void{
        $secret='GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';
        foreach([59=>'94287082',1111111109=>'07081804',1111111111=>'14050471',1234567890=>'89005924',2000000000=>'69279037']as$time=>$expected)self::assertSame($expected,Totp::code($secret,intdiv($time,30),8));
    }
    public function testMfaEnrollmentReplayLockoutAndOneUseRecovery():void{
        [$app,$db,$context]=FieldFixture::create();$service=$app->container()->make('session_security_service');
        $setup=$service->setup(['current_password'=>'test-password-at-least-12']);self::assertCount(8,$setup['recovery_codes']);self::assertFalse($service->status()['enabled']);
        $code=Totp::code($setup['secret'],intdiv(time(),30));$service->confirm(['code'=>$code]);self::assertTrue($service->status()['enabled']);
        try{$service->verifyLogin('1',$code);self::fail('TOTP replay must fail');}catch(HttpException $e){self::assertSame(401,$e->statusCode());}
        self::assertTrue($service->verifyLogin('1',$setup['recovery_codes'][0]));
        try{$service->verifyLogin('1',$setup['recovery_codes'][0]);self::fail('Recovery code replay must fail');}catch(HttpException $e){self::assertSame(401,$e->statusCode());}
        $stored=json_encode($db->fetch('SELECT * FROM account_mfa'));self::assertStringNotContainsString($setup['secret'],$stored);self::assertStringNotContainsString($setup['recovery_codes'][1],$stored);
        for($i=0;$i<5;$i++){try{$service->verifyLogin('1','invalid');}catch(HttpException){}}
        self::assertGreaterThan(time(),(int)$db->fetch('SELECT locked_until FROM account_mfa')['locked_until']);
    }
    public function testIndividualRevocationRejectsOneTokenAndPreservesAnother():void{
        [$app,$db,$context]=FieldFixture::create();$jwt=$app->jwt();$first=$jwt->createToken('1');$second=$jwt->createToken('1');$service=$app->container()->make('session_security_service');
        $id=$db->fetch('SELECT session_id FROM security_sessions WHERE token_hash=:hash',['hash'=>hash('sha256',$jwt->validateToken($first)->claims()->get('jti'))])['session_id'];$service->revoke($id);
        try{$jwt->validateToken($first);self::fail('Revoked token accepted');}catch(HttpException $e){self::assertSame(401,$e->statusCode());}
        self::assertSame('1',$jwt->validateToken($second)->claims()->get('sub'));$context->setTenantId(202);self::assertSame([],$service->sessions());
    }
    public function testJwtKeyRotationDoesNotInvalidateMfaEncryption():void{
        [$app]=FieldFixture::create();$service=$app->container()->make('session_security_service');$setup=$service->setup(['current_password'=>'test-password-at-least-12']);$counter=intdiv(time(),30);$service->confirm(['code'=>Totp::code($setup['secret'],$counter)]);$app->config()->set('jwt.secret','a-rotated-independent-jwt-signing-key');self::assertTrue($service->verifyLogin('1',Totp::code($setup['secret'],$counter+1)));
    }
    public function testTemporaryGrantRequiresMfaAuthenticatedSessionAndExpires():void{
        [$app,$db,$context]=FieldFixture::create();$service=$app->container()->make('session_security_service');
        $db->execute("INSERT INTO account_mfa(user_id,secret_cipher,enabled) VALUES(2,'not-used-by-this-test',1)");$grant=$service->grant(['user_id'=>2,'hours'=>1,'reason'=>'Customer requested diagnostic assistance']);$context->setUser('2');
        try{$service->assertDelegatedAccess();self::fail('Password-only session accepted');}catch(HttpException $e){self::assertSame(403,$e->statusCode());}
        $context->setMfaVerified(true);$service->assertDelegatedAccess();$db->execute("UPDATE support_grants SET expires_at='2026-01-01 00:00:00'");
        try{$service->assertDelegatedAccess();self::fail('Expired grant accepted');}catch(HttpException $e){self::assertSame(403,$e->statusCode());}
        self::assertNotEmpty($grant['grant_id']);
    }
    public function testGuardOnlyAccountCanRevokeItsOwnLabeledSessionOnLogout():void{
        [$app,$db]=FieldFixture::create();$token=$app->jwt()->createToken('2',false,'Android abcd1234');
        $db->execute("DELETE FROM role_permissions WHERE role_id=2 AND permission_id IN (SELECT permission_id FROM permissions WHERE permission_code='users.password_change')");
        $response=$app->handle(Request::create('POST','/api/v1/auth/logout',[],['Authorization'=>'Bearer '.$token,'Content-Type'=>'application/json'],'{}'));self::assertSame(200,$response->status());
        self::assertSame('Android abcd1234',$db->fetch('SELECT session_label FROM security_sessions')['session_label']);
        try{$app->jwt()->validateToken($token);self::fail('Signed-out token remains active');}catch(HttpException $e){self::assertSame(401,$e->statusCode());}
    }
}
