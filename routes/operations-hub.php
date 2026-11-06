<?php
declare(strict_types=1);
use Sentinel\Core\{Container,Router,Request,Response};
return static function(Router $router,Container $container):void {
    $opts=static fn(string $p):array=>['auth'=>true,'tenant'=>true,'permission'=>$p];$json=static fn(mixed $d):Response=>Response::json(['data'=>$d])->withHeader('Cache-Control','no-store');
    $radio=static fn()=>$container->make('radio_service');$reviews=static fn()=>$container->make('service_review_service');$integrations=static fn()=>$container->make('integration_centre_service');$support=static fn()=>$container->make('customer_support_service');$alerts=static fn()=>$container->make('operational_alert_service');
    $router->get('/api/v1/radio/channels',static fn():Response=>$json($radio()->channels()),$opts('radio.self'));
    $router->get('/api/v1/radio-admin/channels',static fn():Response=>$json($radio()->channels(true)),$opts('radio.manage'));
    $router->post('/api/v1/radio-admin/channels',static fn(Request $r):Response=>$json($radio()->create($r->json())),$opts('radio.manage'));
    $router->post('/api/v1/radio-admin/channels/{id}/members',static fn(Request $r,array $p):Response=>$json($radio()->member((string)$p['id'],$r->json())),$opts('radio.manage'));
    $router->post('/api/v1/radio-admin/channels/{id}/disable',static fn(Request $r,array $p):Response=>$json($radio()->disable((string)$p['id'],$r->json())),$opts('radio.manage'));
    $router->get('/api/v1/radio/channels/{id}/feed',static fn(Request $r,array $p):Response=>$json($radio()->feed((string)$p['id'],$r->queryParams())),$opts('radio.self'));
    foreach(['claim','release','send']as$action)$router->post('/api/v1/radio/channels/{id}/'.$action,static fn(Request $r,array $p):Response=>$json($radio()->$action((string)$p['id'],$r->json())),$opts('radio.self'));
    $router->get('/api/v1/radio/messages/{id}/audio',static function(Request $r,array $p)use($radio):Response{$a=$radio()->audio((string)$p['id']);return Response::raw($a['bytes'],200,['Content-Type'=>$a['mime'],'Cache-Control'=>'no-store','X-Content-Type-Options'=>'nosniff']);},$opts('radio.self'));
    $router->post('/api/v1/radio/calls/{id}/acknowledge',static fn(Request $r,array $p):Response=>$json($radio()->acknowledge((string)$p['id'])),$opts('radio.self'));
    $router->get('/api/v1/control-map',static fn(Request $r):Response=>$json($container->make('control_map_service')->snapshot($r->queryParams())),$opts('control_map.view'));
    foreach([false,true]as$client){$path=$client?'/api/v1/client/service-reviews':'/api/v1/service-reviews';$router->get($path,static fn(Request $r):Response=>$json($reviews()->list($r->queryParams(),$client)),$opts($client?'service_reviews.client':'service_reviews.view'));}
    $router->post('/api/v1/service-reviews',static fn(Request $r):Response=>$json($reviews()->generate($r->json())),$opts('service_reviews.manage'));
    $router->post('/api/v1/service-reviews/{id}/publish',static fn(Request $r,array $p):Response=>$json($reviews()->publish((string)$p['id'],$r->json())),$opts('service_reviews.manage'));
    $router->post('/api/v1/service-reviews/{id}/actions',static fn(Request $r,array $p):Response=>$json($reviews()->action((string)$p['id'],$r->json())),$opts('service_reviews.manage'));
    $router->post('/api/v1/service-review-actions/{id}/complete',static fn(Request $r,array $p):Response=>$json($reviews()->finishAction((string)$p['id'],$r->json())),$opts('service_reviews.manage'));
    $router->post('/api/v1/client/service-review-actions/{id}/confirm',static fn(Request $r,array $p):Response=>$json($reviews()->finishAction((string)$p['id'],$r->json(),true)),$opts('service_reviews.client'));
    $router->post('/api/v1/client/service-reviews/{id}/feedback',static fn(Request $r,array $p):Response=>$json($reviews()->feedback((string)$p['id'],$r->json())),$opts('service_reviews.client'));
    $router->get('/api/v1/integration-centre',static fn():Response=>$json($integrations()->overview()),$opts('integrations.view'));
    $router->post('/api/v1/integration-keys',static fn(Request $r):Response=>$json($integrations()->createKey($r->json())),$opts('integrations.manage'));
    $router->post('/api/v1/integration-keys/{id}/revoke',static fn(Request $r,array $p):Response=>$json($integrations()->revoke((string)$p['id'])),$opts('integrations.manage'));
    $router->post('/api/v1/integration-webhooks',static fn(Request $r):Response=>$json($integrations()->webhook($r->json())),$opts('integrations.manage'));
    $router->post('/api/v1/integration-webhooks/{id}/disable',static fn(Request $r,array $p):Response=>$json($integrations()->disableWebhook((string)$p['id'])),$opts('integrations.manage'));
    $router->post('/api/v1/webhook-deliveries/{id}/retry',static fn(Request $r,array $p):Response=>$json($integrations()->retry((string)$p['id'],$r->json())),$opts('integrations.manage'));
    // These three routes authenticate the X-Sentinel-Key credential inside the service.
    foreach(['alarms'=>'alarms.write','access'=>'access.write']as$path=>$scope)$router->post('/api/v1/connect/'.$path,static fn(Request $r):Response=>$json($integrations()->incoming((string)$r->header('X-Sentinel-Key',''),$scope,$r->json())),['auth'=>false,'tenant'=>false]);
    $router->get('/api/v1/connect/attendance',static fn(Request $r):Response=>$json($integrations()->incoming((string)$r->header('X-Sentinel-Key',''),'exports.read',$r->queryParams())),['auth'=>false,'tenant'=>false]);
    foreach([false,true]as$self){$path=$self?'/api/v1/my-support-tickets':'/api/v1/customer-support-tickets';$view=$self?'customer_support.self':'customer_support.view';$manage=$self?'customer_support.self':'customer_support.manage';
        $router->get($path,static fn(Request $r):Response=>$json($support()->list($r->queryParams(),$self)),$opts($view));
        $router->post($path,static fn(Request $r):Response=>$json($support()->create($r->json(),$self)),$opts($manage));
        foreach($self?['reply','confirm','reopen']:['reply','assign','resolve']as$action)$router->post($path.'/{id}/'.$action,static fn(Request $r,array $p):Response=>$json($support()->action((string)$p['id'],$action,$r->json(),$self)),$opts($manage));
        $router->get($path.'/diagnostics/{id}',static fn(Request $r,array $p):Response=>$json($support()->diagnostic((string)$p['id'],$self)),$opts($view));
    }
    $router->post('/api/v1/my-support-tickets/{id}/share-diagnostics',static fn(Request $r,array $p):Response=>$json($support()->share((string)$p['id'],$r->json())),$opts('customer_support.self'));
    $router->post('/api/v1/my-support-consents/{id}/revoke',static fn(Request $r,array $p):Response=>$json($support()->revoke((string)$p['id'])),$opts('customer_support.self'));
    $router->get('/api/v1/release-notices',static fn():Response=>$json($support()->notices()),$opts('release_notices.view'));
    $router->post('/api/v1/release-notices',static fn(Request $r):Response=>$json($support()->notice($r->json())),$opts('release_notices.manage'));
    $router->post('/api/v1/release-notices/{id}/read',static fn(Request $r,array $p):Response=>$json($support()->readNotice((string)$p['id'],$r->json())),$opts('release_notices.view'));
    $router->get('/api/v1/operational-alerts',static fn(Request $r):Response=>$json($alerts()->workspace($r->queryParams())),$opts('operational_alerts.view'));
    $router->post('/api/v1/operational-alert-rules',static fn(Request $r):Response=>$json($alerts()->rule($r->json())),$opts('operational_alerts.manage'));
    $router->post('/api/v1/operational-alerts/{id}/acknowledge',static fn(Request $r,array $p):Response=>$json($alerts()->acknowledge((string)$p['id'],$r->json())),$opts('operational_alerts.view'));
    $router->post('/api/v1/operational-alerts/tick',static fn():Response=>$json($alerts()->tick()),$opts('operational_alerts.manage'));
    $router->post('/api/v1/inspection-queue-health',static fn(Request $r):Response=>$json($alerts()->health($r->json())),$opts('inspection_forms.view'));
    $router->post('/api/v1/emergency-exercises/{id}/resolve-findings',static fn(Request $r,array $p):Response=>$json($container->make('emergency_procedure_service')->resolveFindings((string)$p['id'],$r->json())),$opts('emergency_procedures.manage'));
};
