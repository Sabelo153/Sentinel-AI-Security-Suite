<?php
declare(strict_types=1);
namespace Sentinel\Services;

use Sentinel\Core\{Config,Database,HttpException,RuntimeContext};
use Sentinel\Security\Totp;

final class SessionSecurityService
{
    public function __construct(private readonly Database $db,private readonly RuntimeContext $context,private readonly Config $config,private readonly AuditService $audit){}
    public function register(string $user,string $jti,\DateTimeImmutable $expires,string $label='API'):void{
        $row=$this->db->fetch("SELECT company_id FROM users WHERE user_id=:user AND status='ACTIVE'",['user'=>$user]);if($row===null)throw HttpException::unauthorized();
        if(mb_strlen($label)>100||preg_match('/[\x00-\x1f]/',$label))throw HttpException::unprocessable('Invalid session label.');
        $this->db->execute('INSERT INTO security_sessions(session_id,user_id,company_id,token_hash,session_label,created_at,expires_at) VALUES(:id,:user,:company,:hash,:label,:now,:expires)',['id'=>uuid4(),'user'=>$user,'company'=>$row['company_id'],'hash'=>hash('sha256',$jti),'label'=>$label,'now'=>gmdate('Y-m-d H:i:s'),'expires'=>$expires->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s')]);
    }
    public function assertActive(string $user,string $jti):void{
        if(!$this->db->fetch('SELECT session_id FROM security_sessions WHERE user_id=:user AND token_hash=:hash AND revoked_at IS NULL AND expires_at>:now',['user'=>$user,'hash'=>hash('sha256',$jti),'now'=>gmdate('Y-m-d H:i:s')]))throw HttpException::unauthorized('Session expired or revoked.');
    }
    public function sessions(bool $company=false):array{
        $params=['company'=>$this->context->requireTenant()];$where='';if(!$company){$where=' AND user_id=:user';$params['user']=$this->context->user();}
        return $this->db->fetchAll('SELECT session_id,user_id,session_label,created_at,expires_at,revoked_at FROM security_sessions WHERE company_id=:company'.$where.' ORDER BY created_at DESC LIMIT 200',$params);
    }
    public function logout(string $jti):array{
        $owns=!$this->db->inTransaction();if($owns)$this->db->beginTransaction();
        try{$row=$this->db->fetch('SELECT session_id FROM security_sessions WHERE company_id=:company AND user_id=:user AND token_hash=:hash AND revoked_at IS NULL',['company'=>$this->context->requireTenant(),'user'=>$this->context->user(),'hash'=>hash('sha256',$jti)]);if(!$row)throw HttpException::unauthorized();$result=$this->revoke($row['session_id']);if($owns)$this->db->commit();return $result;}
        catch(\Throwable $error){if($owns&&$this->db->inTransaction())$this->db->rollBack();throw $error;}
    }
    public function revoke(string $id,bool $company=false):array{
        $params=['company'=>$this->context->requireTenant(),'id'=>$id,'now'=>gmdate('Y-m-d H:i:s')];$where='';if(!$company){$where=' AND user_id=:user';$params['user']=$this->context->user();}
        $count=$this->db->execute('UPDATE security_sessions SET revoked_at=:now WHERE company_id=:company AND session_id=:id AND revoked_at IS NULL'.$where,$params);
        if($count!==1)throw HttpException::notFound('Active session not found.');$this->audit->record('security_session','revoked',$id);return ['revoked'=>true];
    }
    private function key():string{$secret=(string)$this->config->get('security.mfa_encryption_key','');if(strlen($secret)<32)throw HttpException::serviceUnavailable('MFA encryption is not configured.');return hash('sha256','sentinel-mfa-encryption:'.$secret,true);}
    private function encrypt(string $secret):string{$nonce=random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);return base64_encode($nonce.sodium_crypto_secretbox($secret,$nonce,$this->key()));}
    private function decrypt(string $cipher):string{$bytes=base64_decode($cipher,true);if($bytes===false||strlen($bytes)<40)throw HttpException::serviceUnavailable('MFA secret unavailable.');$plain=sodium_crypto_secretbox_open(substr($bytes,24),substr($bytes,0,24),$this->key());if($plain===false)throw HttpException::serviceUnavailable('MFA secret unavailable.');return $plain;}
    public function status():array{$row=$this->db->fetch('SELECT enabled FROM account_mfa WHERE user_id=:user',['user'=>$this->context->user()]);return ['enabled'=>(bool)($row['enabled']??false)];}
    public function isMfaEnabled(string $user):bool{return (bool)($this->db->fetch('SELECT enabled FROM account_mfa WHERE user_id=:user',['user'=>$user])['enabled']??false);}
    public function revokeUserSessions(string $user):void{$this->db->execute('UPDATE security_sessions SET revoked_at=:now WHERE company_id=:company AND user_id=:user AND revoked_at IS NULL',['now'=>gmdate('Y-m-d H:i:s'),'company'=>$this->context->requireTenant(),'user'=>$user]);$this->audit->record('security_session','all_revoked',$user);}
    private function password(array $input):void{
        $row=$this->db->fetch("SELECT password_hash FROM users WHERE user_id=:user AND company_id=:company AND status='ACTIVE'",['user'=>$this->context->user(),'company'=>$this->context->requireTenant()]);
        if($row===null||!is_string($input['current_password']??null)||!password_verify($input['current_password'],$row['password_hash']))throw HttpException::unauthorized('Current password is invalid.');
    }
    public function setup(array $input):array{
        if(array_diff(array_keys($input),['current_password']))throw HttpException::unprocessable('Unknown MFA fields.');$this->password($input);if($this->status()['enabled'])throw HttpException::conflict('MFA is already enabled.');
        $secret=Totp::newSecret();$recovery=[];for($i=0;$i<8;$i++)$recovery[]=bin2hex(random_bytes(8));$hashes=array_map(static fn(string $value):string=>hash('sha256',$value),$recovery);
        $this->db->execute('DELETE FROM account_mfa WHERE user_id=:user',['user'=>$this->context->user()]);$this->db->execute('INSERT INTO account_mfa(user_id,secret_cipher,recovery_codes_json) VALUES(:user,:cipher,:recovery)',['user'=>$this->context->user(),'cipher'=>$this->encrypt($secret),'recovery'=>json_encode($hashes)]);$this->audit->record('account_mfa','setup_started',(string)$this->context->user());
        return ['secret'=>$secret,'recovery_codes'=>$recovery,'otpauth_url'=>'otpauth://totp/Sentinel:'.rawurlencode((string)$this->context->user()).'?secret='.$secret.'&issuer=Sentinel&algorithm=SHA1&digits=6&period=30','instruction'=>'Add this key to your authenticator, then confirm a code. Store the single-use recovery codes securely offline; they are shown once.'];
    }
    /** Failure counters persist during login; one successful code cannot be replayed. */
    public function verifyLogin(string $user,string $code,bool $pending=false):bool{
        $row=$this->db->fetch('SELECT * FROM account_mfa WHERE user_id=:user',['user'=>$user]);
        if($row===null||(!$pending&&!(bool)$row['enabled'])){if($pending)throw HttpException::conflict('Begin MFA setup first.');return false;}
        if((int)$row['locked_until']>time())throw HttpException::unauthorized('MFA temporarily locked. Try later.');
        if(!$pending&&preg_match('/^[a-f0-9]{16}$/Di',$code)===1){
            $hashes=json_decode($row['recovery_codes_json']??'[]',true,32,JSON_THROW_ON_ERROR);$hash=hash('sha256',strtolower($code));$index=array_search($hash,$hashes,true);
            if($index!==false){unset($hashes[$index]);$changed=$this->db->execute('UPDATE account_mfa SET recovery_codes_json=:next,failed_attempts=0,locked_until=0 WHERE user_id=:user AND recovery_codes_json=:previous',['next'=>json_encode(array_values($hashes)),'user'=>$user,'previous'=>$row['recovery_codes_json']]);if($changed===1)return true;}
        }
        $counter=Totp::counter($this->decrypt($row['secret_cipher']),$code);
        if($counter===null||$counter<=(int)$row['last_counter']){
            $this->db->execute('UPDATE account_mfa SET locked_until=CASE WHEN failed_attempts>=4 THEN :locked ELSE locked_until END,failed_attempts=failed_attempts+1 WHERE user_id=:user AND locked_until<=:now',['locked'=>time()+900,'user'=>$user,'now'=>time()]);throw HttpException::unauthorized('A fresh authenticator code is required.','mfa_invalid_code');
        }
        $changed=$this->db->execute('UPDATE account_mfa SET last_counter=:counter,failed_attempts=0,locked_until=0 WHERE user_id=:user AND last_counter<:expected',['counter'=>$counter,'user'=>$user,'expected'=>$counter]);
        if($changed!==1)throw HttpException::unauthorized('Authenticator code already used.');
        return true;
    }
    public function confirm(array $input):array{
        if(array_diff(array_keys($input),['code'])||!is_string($input['code']??null))throw HttpException::unprocessable('Invalid MFA fields.');$this->verifyLogin((string)$this->context->user(),$input['code'],true);$this->db->execute('UPDATE account_mfa SET enabled=1 WHERE user_id=:user',['user'=>$this->context->user()]);$this->audit->record('account_mfa','enabled',(string)$this->context->user());return ['enabled'=>true];
    }
    public function disable(array $input):array{
        if(array_diff(array_keys($input),['code','current_password'])||!is_string($input['code']??null))throw HttpException::unprocessable('Invalid MFA fields.');$this->password($input);$this->verifyLogin((string)$this->context->user(),$input['code']);$this->db->execute('DELETE FROM account_mfa WHERE user_id=:user',['user'=>$this->context->user()]);$this->audit->record('account_mfa','disabled',(string)$this->context->user());return ['enabled'=>false];
    }
    public function grants():array{return $this->db->fetchAll('SELECT grant_id,user_id,expires_at,reason,revoked_at FROM support_grants WHERE company_id=:company ORDER BY grant_id DESC LIMIT 100',['company'=>$this->context->requireTenant()]);}
    public function grant(array $input):array{
        if(array_diff(array_keys($input),['user_id','hours','reason']))throw HttpException::unprocessable('Unknown grant fields.');$hours=filter_var($input['hours']??null,FILTER_VALIDATE_INT);$reason=$input['reason']??null;
        if($hours===false||$hours<1||$hours>8||!is_string($reason)||mb_strlen(trim($reason))<10||mb_strlen($reason)>500)throw HttpException::unprocessable('Provide 1–8 hours and a reason between 10 and 500 characters.');
        $row=$this->db->fetch("SELECT u.user_id FROM users u JOIN account_mfa m ON m.user_id=u.user_id AND m.enabled=1 WHERE u.company_id=:company AND u.user_id=:user AND u.status='ACTIVE'",['company'=>$this->context->requireTenant(),'user'=>$input['user_id']??'']);if(!$row)throw HttpException::unprocessable('Choose an active same-company support account with MFA enabled.');
        $this->db->execute('INSERT INTO support_grants(company_id,user_id,expires_at,reason,created_by_user_id) VALUES(:company,:user,:expires,:reason,:actor)',['company'=>$this->context->requireTenant(),'user'=>$row['user_id'],'expires'=>gmdate('Y-m-d H:i:s',time()+$hours*3600),'reason'=>trim($reason),'actor'=>$this->context->user()]);$id=(string)$this->db->connection()->lastInsertId();$this->audit->record('support_grant','created',$id);return ['grant_id'=>$id];
    }
    public function revokeGrant(string $id):array{$this->db->execute('UPDATE support_grants SET revoked_at=:now WHERE company_id=:company AND grant_id=:id',['company'=>$this->context->requireTenant(),'id'=>$id,'now'=>gmdate('Y-m-d H:i:s')]);$this->audit->record('support_grant','revoked',$id);return ['revoked'=>true];}
    public function assertDelegatedAccess():void{
        if(!$this->context->mfaVerified()||!$this->status()['enabled']||!$this->db->fetch('SELECT grant_id FROM support_grants WHERE company_id=:company AND user_id=:user AND revoked_at IS NULL AND expires_at>:now',['company'=>$this->context->requireTenant(),'user'=>$this->context->user(),'now'=>gmdate('Y-m-d H:i:s')]))throw HttpException::forbidden('Temporary support requires an MFA-authenticated session and an unexpired grant.');
    }
}
