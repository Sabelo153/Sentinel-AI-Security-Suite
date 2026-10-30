<?php
declare(strict_types=1);
/** One tenant per invocation; schedule as a restricted server service account. */
require dirname(__DIR__).'/vendor/autoload.php';
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$options=getopt('',['company:','operator:']);
$company=filter_var($options['company']??null,FILTER_VALIDATE_INT);$operator=filter_var($options['operator']??null,FILTER_VALIDATE_INT);
if(!$company||!$operator){fwrite(STDERR,"Usage: php bin/run-escalations.php --company=ID --operator=SERVICE_USER_ID\n");exit(2);}
\Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$app=\Sentinel\Core\Application::create(dirname(__DIR__))->bootstrap();$db=$app->database();$context=$app->runtimeContext();
try{
    $context->setTenantId($company);$context->setUser((string)$operator);
    if(!$db->fetch("SELECT user_id FROM users WHERE company_id=:company AND user_id=:user AND status='ACTIVE'",['company'=>$company,'user'=>$operator]))throw new \RuntimeException('Invalid worker operator.');
    $app->container()->make('authorization_service')->requirePermission((string)$operator,'escalations.manage');
    $db->beginTransaction();$result=$app->container()->make('field_operations_service')->escalationTick();$db->commit();echo json_encode($result,JSON_THROW_ON_ERROR).PHP_EOL;
}catch(\Throwable){if($db->inTransaction())$db->rollBack();fwrite(STDERR,"Escalation worker failed. Check service health and audit availability; no partial notifications committed.\n");exit(1);}
