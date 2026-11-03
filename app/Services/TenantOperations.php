<?php
declare(strict_types=1);
namespace Sentinel\Services;
use Sentinel\Core\HttpException;

/** Shared validation and transaction boundaries for the assurance services. */
trait TenantOperations
{
    private function company(): int { return $this->context->requireTenant(); }
    private function actor(): string { if(!$this->context->isAuthenticated())throw HttpException::unauthorized();return (string)$this->context->user(); }
    private function fields(array $input,array $allowed): void { if(array_diff(array_keys($input),$allowed))throw HttpException::unprocessable('Unknown fields.'); }
    private function text(mixed $value,int $max=500): string {
        if(!is_string($value)||trim($value)===''||mb_strlen($value)>$max)throw HttpException::unprocessable('Required text is missing or too long.');return trim($value);
    }
    private function number(mixed $value,int $min=1,int $max=2147483647): int {
        if((!is_int($value)&&!(is_string($value)&&ctype_digit($value)))||(float)$value<$min||(float)$value>$max)throw HttpException::unprocessable('Number is invalid or outside the allowed range.');return (int)$value;
    }
    private function at(mixed $value): string {
        if(!is_string($value)||preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2} [0-9]{2}:[0-9]{2}:[0-9]{2}$/D',$value)!==1)throw HttpException::unprocessable('Use UTC YYYY-MM-DD HH:MM:SS.');
        $d=is_string($value)?\DateTimeImmutable::createFromFormat('!Y-m-d H:i:s',$value,new \DateTimeZone('UTC')):false;
        if(!$d||$d->format('Y-m-d H:i:s')!==$value)throw HttpException::unprocessable('Use UTC YYYY-MM-DD HH:MM:SS.');return $value;
    }
    private function day(mixed $value): string { $this->at((is_string($value)?$value:'').' 00:00:00');return $value; }
    private function row(string $table,string $key,mixed $id): array {
        // Table and key are source constants, never request-provided identifiers.
        return $this->db->fetch("SELECT * FROM $table WHERE company_id=:company AND $key=:id",['company'=>$this->company(),'id'=>$this->number($id)])??throw HttpException::notFound('Record not found.');
    }
    private function atomic(callable $work): array {
        $owns=!$this->db->inTransaction();if($owns)$this->db->beginTransaction();
        try {
            if($this->db->connection()->getAttribute(\PDO::ATTR_DRIVER_NAME)==='mysql')$this->db->fetch('SELECT company_id FROM companies WHERE company_id=:company FOR UPDATE',['company'=>$this->company()]);
            $result=$work();if($owns)$this->db->commit();return $result;
        }catch(\Throwable $e){if($owns&&$this->db->inTransaction())$this->db->rollBack();throw $e;}
    }
    private function created(string $entity): array { $id=(string)$this->db->connection()->lastInsertId();$this->audit->record($entity,'created',$id);return ['id'=>$id]; }
    private function employee(): string { $user=$this->row('users','user_id',$this->actor());if($user['status']!=='ACTIVE'||empty($user['employee_id']))throw HttpException::forbidden('An active employee account is required.');return (string)$user['employee_id']; }
    private function ownShift(mixed $id): array { $shift=$this->row('shift_assignments','shift_assignment_id',$id);if((string)$shift['employee_id']!==$this->employee()||!empty($shift['deleted_at'])||$shift['assignment_status']==='CANCELLED')throw HttpException::notFound('Assigned shift not found.');return $shift; }
    private function site(mixed $id): array { $site=$this->row('sites','site_id',$id);if(!empty($site['deleted_at']))throw HttpException::notFound('Site not found.');return $site; }
}
