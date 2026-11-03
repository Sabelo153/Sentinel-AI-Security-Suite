<?php
declare(strict_types=1);
require dirname(__DIR__).'/vendor/autoload.php';
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$args=getopt('',['company:','operator:']);$company=filter_var($args['company']??null,FILTER_VALIDATE_INT);$actor=filter_var($args['operator']??null,FILTER_VALIDATE_INT);
if(!$company||!$actor){fwrite(STDERR,"Usage: php bin/run-client-reports.php --company=ID --operator=SERVICE_USER_ID\n");exit(2);}
\Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();$app=\Sentinel\Core\Application::create(dirname(__DIR__))->bootstrap();$db=$app->database();$authorised=false;
try {
    $app->runtimeContext()->setTenantId($company);$app->runtimeContext()->setUser((string)$actor);if(!$db->fetch("SELECT user_id FROM users WHERE company_id=:company AND user_id=:actor AND status='ACTIVE'",['company'=>$company,'actor'=>$actor]))throw new \RuntimeException('Invalid worker account.');$app->container()->make('authorization_service')->requirePermission((string)$actor,'client_reports.manage');$authorised=true;
    $service=$app->container()->make('client_report_delivery_service');$result=$service->generate()+$service->sendPending();$db->execute("INSERT INTO worker_runs(company_id,worker_name,started_at,finished_at,state,summary) VALUES(:company,'client-reports',:start,:end,'OK','Portal generation and email outbox evaluation completed. Inspect delivery states for mail failures.')",['company'=>$company,'start'=>gmdate('Y-m-d H:i:s'),'end'=>gmdate('Y-m-d H:i:s')]);echo json_encode($result,JSON_THROW_ON_ERROR).PHP_EOL;
}catch(\Throwable){if($db->inTransaction())$db->rollBack();if($authorised)try{$app->container()->make('control_room_service')->recordWorkerFailure('client-reports');}catch(\Throwable){}fwrite(STDERR,"Client report worker failed. Inspect readiness and delivery status; SMTP outcomes may require manual review.\n");exit(1);}
