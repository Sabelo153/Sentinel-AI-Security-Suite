<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$options=getopt('',['company:','operator:']);$company=filter_var($options['company']??null,FILTER_VALIDATE_INT);$operator=filter_var($options['operator']??null,FILTER_VALIDATE_INT);
if(!$company||!$operator){fwrite(STDERR,"Usage: php bin/run-control-room.php --company=ID --operator=SERVICE_USER_ID\n");exit(2);}
\Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();$app=\Sentinel\Core\Application::create(dirname(__DIR__))->bootstrap();$db=$app->database();$authorised=false;
try {
    $app->runtimeContext()->setTenantId($company);$app->runtimeContext()->setUser((string)$operator);
    if(!$db->fetch("SELECT user_id FROM users WHERE company_id=:company AND user_id=:actor AND status='ACTIVE'",['company'=>$company,'actor'=>$operator]))throw new \RuntimeException('Invalid operator.');
    $app->container()->make('authorization_service')->requirePermission((string)$operator,'readiness.manage');$authorised=true;
    echo json_encode($app->container()->make('control_room_service')->tick(),JSON_THROW_ON_ERROR).PHP_EOL;
} catch(\Throwable) {
    if($db->inTransaction())$db->rollBack();if($authorised)try{$app->container()->make('control_room_service')->recordWorkerFailure('control-room');}catch(\Throwable){}
    fwrite(STDERR,"Control-room evaluation failed. Inspect the restricted server logs and readiness dashboard.\n");exit(1);
}
