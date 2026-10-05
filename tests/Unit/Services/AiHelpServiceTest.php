<?php
declare(strict_types=1);
namespace Sentinel\Tests\Unit\Services;
use PHPUnit\Framework\TestCase;
use Sentinel\Core\{Config,HttpException};
use Sentinel\Services\AiHelpService;
final class AiHelpServiceTest extends TestCase
{
    public function testOnlyTypedHelpQuestionReachesLocalModelAndCannotAct():void
    {
        $seen=null;$service=new AiHelpService(new Config(['ai'=>['enabled'=>true,'model'=>'small-local']]),function(array $request)use(&$seen):array{$seen=$request;return['response'=>'Use a clear heading, facts in time order, and neutral language.','commands'=>['submit_report']];});
        $result=$service->answer(['topic'=>'report_writing','question'=>'How should I structure a report?']);
        self::assertSame('small-local',$seen['model']);self::assertStringContainsString('How should I structure a report?',$seen['prompt']);self::assertStringContainsString('User type: manager',$seen['prompt']);self::assertStringContainsString('no access to Sentinel records',$seen['system']);self::assertSame('manager',$result['audience']);self::assertFalse($result['can_access_records']);self::assertFalse($result['can_execute_actions']);self::assertFalse($result['stored']);self::assertArrayNotHasKey('commands',$result);
    }
    public function testDisabledAssistantNeverCallsModel():void
    {
        $called=false;$service=new AiHelpService(new Config([]),function()use(&$called):array{$called=true;return[];});
        try{$service->answer(['topic'=>'getting_started','question'=>'Where do I begin?']);self::fail('Expected disabled response.');}catch(HttpException $e){self::assertSame(503,$e->statusCode());self::assertSame('ai_help_not_configured',$e->errorCode());}self::assertFalse($called);
    }
    public function testRejectsExtraDataUnsupportedTopicsAndPersonalData():void
    {
        $service=new AiHelpService(new Config(['ai'=>['enabled'=>true]]),static fn():array=>['response'=>'Help']);
        foreach([['topic'=>'report_writing','question'=>'How do I write clearly?','record'=>['incident_id'=>'1']],['topic'=>'database_search','question'=>'Find a person'],['topic'=>'report_writing','question'=>'Contact person@example.com about this'],['topic'=>'report_writing','question'=>'Password: secret-value']]as$bad){try{$service->answer($bad);self::fail('Expected validation rejection.');}catch(HttpException $e){self::assertSame(422,$e->statusCode());}}
    }
    public function testGuardReceivesGuardSpecificInstructionsAndTopics():void
    {
        $seen=null;$service=new AiHelpService(new Config(['ai'=>['enabled'=>true]]),function(array $request)use(&$seen):array{$seen=$request;return['response'=>'Follow the assigned route and record each checkpoint.'];});
        $result=$service->answer(['topic'=>'patrols_checkpoints','question'=>'What do I do before starting a patrol?'],'guard');
        self::assertSame('guard',$result['audience']);self::assertNull($seen);self::assertStringContainsString('Patrol history',$result['answer']);self::assertFalse($result['local_model']);
        try{$service->answer(['topic'=>'team_setup','question'=>'How do I add a user?'],'guard');self::fail('Expected guard topic rejection.');}catch(HttpException $e){self::assertSame(422,$e->statusCode());}
    }
    public function testGeofenceQuestionUsesVerifiedInstructionsRegardlessOfSelectedTopic():void
    {
        $service=new AiHelpService(new Config(['ai'=>['enabled'=>true]]),static function():array{throw new \RuntimeException('Must not call model');});
        $answer=$service->answer(['topic'=>'shift_duties','question'=>'How do I set up a geofence?'],'guard');
        self::assertStringContainsString('not in the guard APK',$answer['answer']);
        self::assertStringContainsString('not implemented',$answer['answer']);
        self::assertFalse($answer['local_model']);
    }
    public function testWritingModelFailureReturnsUsefulFallback():void
    {
        $service=new AiHelpService(new Config(['ai'=>['enabled'=>true]]),static function():array{throw new \RuntimeException('timeout');});
        $answer=$service->answer(['topic'=>'report_writing','question'=>'Help me write clearly']);
        self::assertStringContainsString('No report has been submitted',$answer['answer']);
        self::assertFalse($answer['local_model']);
    }
}
