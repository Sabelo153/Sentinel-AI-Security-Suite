<?php
declare(strict_types=1);
use Sentinel\Core\{Container,Router,Request,Response};
return static function(Router $router,Container $container):void {
    $service=static fn()=>$container->make('control_room_service');
    $opts=static fn(string $permission):array=>['auth'=>true,'tenant'=>true,'permission'=>$permission];
    $json=static fn(mixed $data):Response=>Response::json(['data'=>$data])->withHeader('Cache-Control','no-store');
    foreach(['dispatch'=>'dispatch','lone-workers'=>'lone_worker','gate'=>'gate_register','rates'=>'billing','invoices'=>'billing','signals'=>'readiness','relief-history'=>'rostering']as$resource=>$module)
        $router->get('/api/v1/control-room/'.$resource,static fn():Response=>$json($service()->list($resource)),$opts($module.'.view'));
    foreach(['dispatch'=>['createDispatch','dispatch'],'lone-workers'=>['createLonePlan','lone_worker'],'coverage'=>['createCoverage','rostering'],'gate'=>['gate','gate_register'],'rates'=>['rate','billing'],'invoices'=>['invoice','billing'],'backup-checks'=>['backup','readiness']]as$path=>[$method,$module])
        $router->post('/api/v1/control-room/'.$path,static fn(Request $r):Response=>$json($service()->$method($r->json())),$opts($module.'.manage'));
    $router->get('/api/v1/control-room/coverage',static fn():Response=>$json($service()->coverage()),$opts('rostering.view'));
    $router->get('/api/v1/control-room/readiness',static fn():Response=>$json($service()->readiness()),$opts('readiness.view'));
    $router->get('/api/v1/control-room/device-health',static fn():Response=>$json($service()->deviceHealth(max(1,(int)$container->make('config')->get('security.minimum_guard_version_code',15)))),$opts('device_health.view'));
    $router->get('/api/v1/control-room/invoices/{id}',static fn(Request $r,array $p):Response=>$json($service()->invoiceDetail((string)$p['id'])),$opts('billing.view'));
    $router->post('/api/v1/control-room/invoices/{id}/void',static fn(Request $r,array $p):Response=>$json($service()->voidInvoice((string)$p['id'],$r->json())),$opts('billing.manage'));
    foreach(['accept','arrive','close','cancel']as$action)$router->post('/api/v1/control-room/dispatch/{id}/'.$action,static fn(Request $r,array $p):Response=>$json($service()->dispatchAction((string)$p['id'],$action,$r->json())),$opts('dispatch.manage'));
    $router->post('/api/v1/control-room/lone-workers/{id}/close',static fn(Request $r,array $p):Response=>$json($service()->closePlan((string)$p['id'],$r->json())),$opts('lone_worker.manage'));
    $router->post('/api/v1/control-room/shifts/{id}/relief',static fn(Request $r,array $p):Response=>$json($service()->relief((string)$p['id'],$r->json())),$opts('rostering.manage'));
    $router->post('/api/v1/control-room/coverage/{id}/cancel',static fn(Request $r,array $p):Response=>$json($service()->cancelCoverage((string)$p['id'])),$opts('rostering.manage'));
    $router->post('/api/v1/control-room/signals/{id}/acknowledge',static fn(Request $r,array $p):Response=>$json($service()->acknowledge((string)$p['id'])),$opts('readiness.manage'));
    $router->get('/api/v1/guard/control-room',static fn():Response=>$json($service()->selfWorkspace()),$opts('guard.self'));
    foreach(['accept','arrive']as$action)$router->post('/api/v1/guard/dispatch/{id}/'.$action,static fn(Request $r,array $p):Response=>$json($service()->dispatchAction((string)$p['id'],$action,$r->json(),true)),$opts('guard.self'));
    $router->post('/api/v1/guard/checkins/{id}',static fn(Request $r,array $p):Response=>$json($service()->checkin((string)$p['id'],$r->json())),$opts('guard.self'));
    $router->post('/api/v1/guard/phone-health',static fn(Request $r):Response=>$json($service()->healthReport($r->json())),$opts('guard.self'));
};
