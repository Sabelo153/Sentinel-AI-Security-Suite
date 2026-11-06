<?php
declare(strict_types=1);
use Sentinel\Core\{Container,Router,Request,Response};
return static function(Router $router,Container $c):void {
 $o=static fn(string $p):array=>['auth'=>true,'tenant'=>true,'permission'=>$p];$j=static fn($v):Response=>Response::json(['data'=>$v])->withHeader('Cache-Control','no-store');
 $response=static fn()=>$c->make('response_transport_service');$plans=static fn()=>$c->make('site_delivery_plan_service');$oversight=static fn()=>$c->make('operational_oversight_service');$clients=static fn()=>$c->make('client_delivery_service');$subs=static fn()=>$c->make('subcontractor_service');
 $router->get('/api/v1/alarm-responses',static fn(Request $r):Response=>$j($response()->alarms($r->queryParams())),$o('alarm_response.view'));
 $router->get('/api/v1/my-alarm-responses',static fn(Request $r):Response=>$j($response()->responder($r->queryParams())),$o('alarm_response.self'));
 $router->post('/api/v1/alarm-responses',static fn(Request $r):Response=>$j($response()->createAlarm($r->json())),$o('alarm_response.manage'));
 foreach(['dispatch','close']as$a)$router->post('/api/v1/alarm-responses/{id}/'.$a,static fn(Request $r,array $p):Response=>$j($response()->alarmAction((string)$p['id'],$a,$r->json())),$o('alarm_response.manage'));
 foreach(['accept','arrive']as$a)$router->post('/api/v1/my-alarm-responses/{id}/'.$a,static fn(Request $r,array $p):Response=>$j($response()->respond((string)$p['id'],$a,$r->json())),$o('alarm_response.self'));
 foreach([false,true]as$self)$router->get($self?'/api/v1/my-transports':'/api/v1/guard-transports',static fn(Request $r):Response=>$j($response()->transport($r->queryParams(),$self)),$o($self?'guard_transport.self':'guard_transport.view'));
 $router->post('/api/v1/guard-transports',static fn(Request $r):Response=>$j($response()->createRun($r->json())),$o('guard_transport.manage'));
 $router->post('/api/v1/guard-transports/{id}/passenger',static fn(Request $r,array $p):Response=>$j($response()->passenger((string)$p['id'],$r->json())),$o('guard_transport.manage'));
 foreach(['start','board','alight','missed','finish','cancel']as$a)$router->post('/api/v1/guard-transports/{id}/'.$a,static fn(Request $r,array $p):Response=>$j($response()->transportAction((string)$p['id'],$a,$r->json())),$o('guard_transport.manage'));
 foreach(['post-orders'=>['POST_ORDERS','post_orders'],'continuity-plans'=>['CONTINUITY','continuity'],'risk-assessments'=>['RISK','risk_assessments']]as$path=>[$kind,$permission]){
 $router->get('/api/v1/'.$path,static fn(Request $r):Response=>$j($plans()->list($kind,$r->queryParams())),$o($permission.'.view'));
 $router->post('/api/v1/'.$path,static fn(Request $r):Response=>$j($plans()->publish($kind,$r->json())),$o($permission.'.manage'));
 if($kind==='RISK'){$audience='CLIENT';$read='/api/v1/client/risk-assessments';$code='risk_assessments.client';}else{$audience='SELF';$read='/api/v1/my-'.$path;$code=$permission.'.self';}
 $router->get($read,static fn(Request $r):Response=>$j($plans()->list($kind,$r->queryParams(),$audience)),$o($code));
 $router->post($read.'/{id}/acknowledge',static fn(Request $r,array $p):Response=>$j($plans()->acknowledge((string)$p['id'],$r->json(),$audience)),$o($code));
 }
 $router->post('/api/v1/continuity-plans/{id}/activate',static fn(Request $r,array $p):Response=>$j($plans()->activate((string)$p['id'],$r->json())),$o('continuity.manage'));
 foreach(['step','close','cancel']as$a)$router->post('/api/v1/continuity-activations/{id}/'.$a,static fn(Request $r,array $p):Response=>$j($plans()->activationAction((string)$p['id'],$a,$r->json())),$o('continuity.manage'));
 $router->get('/api/v1/equipment-checks',static fn(Request $r):Response=>$j($oversight()->equipment($r->queryParams())),$o('equipment_checks.view'));
 $router->post('/api/v1/equipment-checks',static fn(Request $r):Response=>$j($oversight()->schedule($r->json())),$o('equipment_checks.manage'));
 $router->post('/api/v1/equipment-checks/{id}/reschedule',static fn(Request $r,array $p):Response=>$j($oversight()->reschedule((string)$p['id'],$r->json())),$o('equipment_checks.manage'));
 $router->post('/api/v1/equipment-checks/{id}/inspect',static fn(Request $r,array $p):Response=>$j($oversight()->inspect((string)$p['id'],$r->json())),$o('equipment_checks.manage'));
 $router->get('/api/v1/control-handovers',static fn(Request $r):Response=>$j($oversight()->handovers($r->queryParams())),$o('control_handover.view'));
 $router->post('/api/v1/control-handovers',static fn(Request $r):Response=>$j($oversight()->handover($r->json())),$o('control_handover.manage'));
 $router->post('/api/v1/control-handovers/{id}/accept',static fn(Request $r,array $p):Response=>$j($oversight()->acceptHandover((string)$p['id'],$r->json())),$o('control_handover.manage'));
 $router->get('/api/v1/operations-analytics',static fn(Request $r):Response=>$j($oversight()->analytics($r->queryParams())),$o('operations_analytics.view'));
 foreach([false,true]as$client){$path=$client?'/api/v1/client/extra-services':'/api/v1/extra-services';$permission=$client?'extra_services.client':'extra_services.manage';
 $router->get($path,static fn(Request $r):Response=>$j($clients()->list($r->queryParams(),$client)),$o($client?'extra_services.client':'extra_services.view'));
 $router->post($path,static fn(Request $r):Response=>$j($clients()->create($r->json(),$client)),$o($permission));
 foreach($client?['accept','decline','confirm','cancel']:['schedule','complete','cancel']as$a)$router->post($path.'/{id}/'.$a,static fn(Request $r,array $p):Response=>$j($clients()->action((string)$p['id'],$a,$r->json(),$client)),$o($permission));
 $router->get($client?'/api/v1/client/mobilisation':'/api/v1/client-mobilisation',static fn(Request $r):Response=>$j($clients()->onboardings($r->queryParams(),$client)),$o($client?'client_mobilisation.client':'client_mobilisation.view'));
 }
 $router->post('/api/v1/extra-services/{id}/quote',static fn(Request $r,array $p):Response=>$j($clients()->quote((string)$p['id'],$r->json())),$o('extra_services.manage'));
 $router->post('/api/v1/client-mobilisation/{id}/prepare',static fn(Request $r,array $p):Response=>$j($clients()->prepare((string)$p['id'],$r->json())),$o('client_mobilisation.manage'));
 $router->post('/api/v1/client-mobilisation/{id}/activate',static fn(Request $r,array $p):Response=>$j($clients()->activate((string)$p['id'])),$o('client_mobilisation.manage'));
 foreach(['accept','decline']as$a)$router->post('/api/v1/client/mobilisation-reviews/{id}/'.$a,static fn(Request $r,array $p):Response=>$j($clients()->decidePacket((string)$p['id'],$a,$r->json())),$o('client_mobilisation.client'));
 $router->get('/api/v1/subcontractors',static fn():Response=>$j($subs()->list()),$o('subcontractors.view'));
 $router->post('/api/v1/subcontractors',static fn(Request $r):Response=>$j($subs()->save(null,$r->json())),$o('subcontractors.manage'));
 $router->post('/api/v1/subcontractors/{id}/update',static fn(Request $r,array $p):Response=>$j($subs()->save((string)$p['id'],$r->json())),$o('subcontractors.manage'));
 $router->post('/api/v1/subcontractors/{id}/site',static fn(Request $r,array $p):Response=>$j($subs()->approveSite((string)$p['id'],$r->json())),$o('subcontractors.manage'));
 $router->post('/api/v1/subcontractors/{id}/revoke-site',static fn(Request $r,array $p):Response=>$j($subs()->revokeSite((string)$p['id'],$r->json())),$o('subcontractors.manage'));
 $router->post('/api/v1/subcontractors/{id}/member',static fn(Request $r,array $p):Response=>$j($subs()->member((string)$p['id'],$r->json())),$o('subcontractors.manage'));
};
