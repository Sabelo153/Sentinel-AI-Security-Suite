<?php
declare(strict_types=1);
namespace Sentinel\Tests\Unit\Services;

use PDO;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\{Config,Database,HttpException,RuntimeContext};
use Sentinel\Services\PatrolScheduleService;

final class PatrolScheduleServiceTest extends TestCase
{
    private function fixture(): array
    {
        $db=new Database(new Config([]),static fn():PDO=>new PDO('sqlite::memory:',null,null,[PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]));
        foreach([
            'patrol_schedule_windows (patrol_schedule_id INTEGER PRIMARY KEY AUTOINCREMENT,company_id INTEGER,patrol_route_id INTEGER,shift_assignment_id INTEGER,starts_at TEXT,ends_at TEXT,cancelled_at TEXT)',
            'patrol_routes (patrol_route_id INTEGER,company_id INTEGER,site_id INTEGER,route_name TEXT,status TEXT,deleted_at TEXT)',
            'shift_assignments (shift_assignment_id INTEGER,company_id INTEGER,employee_id INTEGER,site_id INTEGER,shift_name TEXT,shift_start_at TEXT,shift_end_at TEXT,assignment_status TEXT,deleted_at TEXT)',
            'employee_assignments (employee_assignment_id INTEGER,company_id INTEGER,employee_id INTEGER)',
            'patrol_sessions (patrol_session_id INTEGER,company_id INTEGER,patrol_route_id INTEGER,shift_assignment_id INTEGER,employee_assignment_id INTEGER,started_at TEXT,ended_at TEXT,status TEXT,deleted_at TEXT)',
        ]as$schema)$db->execute('CREATE TABLE '.$schema);
        foreach([101,202]as$c){
            $db->execute("INSERT INTO patrol_routes VALUES (1,:c,1,'Perimeter','ACTIVE',NULL)",['c'=>$c]);
            $db->execute("INSERT INTO shift_assignments VALUES (1,:c,10,1,'Day','2030-01-01 08:00:00','2030-01-01 18:00:00','ASSIGNED',NULL)",['c'=>$c]);
            $db->execute('INSERT INTO employee_assignments VALUES (1,:c,10)',['c'=>$c]);
        }
        $context=new RuntimeContext();$context->setTenantId(101);
        return [$db,$context,new PatrolScheduleService($db,$context)];
    }
    private function window(): array {return ['patrol_route_id'=>1,'shift_assignment_id'=>1,'starts_at'=>'2030-01-01 09:00:00','ends_at'=>'2030-01-01 09:15:00'];}
    private function clock(string $time): \DateTimeImmutable {return new \DateTimeImmutable('2030-01-01 '.$time,new \DateTimeZone('UTC'));}
    private function rejects(int $code,callable $call):void {try{$call();self::fail('Expected controlled rejection.');}catch(HttpException $e){self::assertSame($code,$e->statusCode());}}
    public function testMissedWindowBoundariesAndLateOfflineEvidence():void
    {
        [$db,,$s]=$this->fixture();$s->create($this->window());
        self::assertSame('UPCOMING',$s->list([],$this->clock('08:59:59'))['items'][0]['status']);
        self::assertSame('DUE',$s->list([],$this->clock('09:00:00'))['items'][0]['status']);
        self::assertSame('MISSED',$s->list([],$this->clock('09:15:00'))['items'][0]['status']);
        $db->execute("INSERT INTO patrol_sessions VALUES (1,202,1,1,1,'2030-01-01 09:02:00',NULL,'IN_PROGRESS',NULL)");
        self::assertSame('MISSED',$s->list([],$this->clock('09:30:00'))['items'][0]['status']);
        $db->execute("INSERT INTO patrol_sessions VALUES (2,101,1,1,1,'2030-01-01 09:02:00','2030-01-01 09:25:00','COMPLETED',NULL)");
        self::assertSame('COMPLETED',$s->list([],$this->clock('09:30:00'))['items'][0]['status']);
        self::assertCount(1,$db->fetchAll('SELECT * FROM patrol_schedule_windows'));
    }
    public function testOverlapRejectedButAdjacentWindowsAreAllowed():void
    {
        [,, $s]=$this->fixture();$s->create($this->window());
        $this->rejects(409,fn()=>$s->create($this->window()));
        $s->create([...$this->window(),'starts_at'=>'2030-01-01 09:15:00','ends_at'=>'2030-01-01 09:30:00']);
        self::assertCount(2,$s->list()['items']);
    }
    public function testBadFieldsDatesShiftRelationshipsAndTenantFailSafely():void
    {
        [$db,$context,$s]=$this->fixture();
        $this->rejects(422,fn()=>$s->create([...$this->window(),'company_id'=>202]));
        $this->rejects(422,fn()=>$s->create([...$this->window(),'starts_at'=>'2030-02-30 08:00:00']));
        $this->rejects(422,fn()=>$s->create([...$this->window(),'ends_at'=>'2030-01-01 19:00:00']));
        $this->rejects(404,fn()=>$s->create([...$this->window(),'patrol_route_id'=>2]));
        $db->execute('UPDATE patrol_routes SET site_id=2 WHERE company_id=101');
        $this->rejects(422,fn()=>$s->create($this->window()));
        $context->setTenantId(null);$this->rejects(403,fn()=>$s->list());
        self::assertSame([],$db->fetchAll('SELECT * FROM patrol_schedule_windows'));
    }
    public function testEmployeeScopeAndCancellationIsolation():void
    {
        [,$context,$s]=$this->fixture();$row=$s->create($this->window());
        self::assertCount(1,$s->list([],null,10)['items']);self::assertSame([],$s->list([],null,11)['items']);
        $context->setTenantId(202);self::assertSame([],$s->list()['items']);
        $this->rejects(404,fn()=>$s->cancel((string)$row['patrol_schedule_id']));
        $context->setTenantId(101);$s->cancel((string)$row['patrol_schedule_id']);
        self::assertSame('CANCELLED',$s->list()['items'][0]['status']);
    }
    public function testPaginationDoesNotDropOrRepeatWindows():void
    {
        [$db,,$s]=$this->fixture();
        for($i=0;$i<202;$i++)$db->execute("INSERT INTO patrol_schedule_windows (company_id,patrol_route_id,shift_assignment_id,starts_at,ends_at) VALUES (101,1,1,'2030-01-01 09:00:00','2030-01-01 09:15:00')");
        $first=$s->list();self::assertCount(200,$first['items']);
        $second=$s->list(['before_id'=>$first['next_before_id']]);self::assertCount(2,$second['items']);self::assertNull($second['next_before_id']);
        self::assertSame([],array_intersect(array_column($first['items'],'patrol_schedule_id'),array_column($second['items'],'patrol_schedule_id')));
    }
    public function testUnavailableStorageReturns503():void
    {
        [$db,,$s]=$this->fixture();$db->execute('DROP TABLE patrol_schedule_windows');
        $this->rejects(503,fn()=>$s->list());$this->rejects(503,fn()=>$s->create($this->window()));self::assertFalse($db->inTransaction());
    }
}
