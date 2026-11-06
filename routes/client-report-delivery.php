<?php
declare(strict_types=1);
use Sentinel\Core\{Container,Router,Request,Response};
return static function(Router $router,Container $container):void {
    $service=static fn()=>$container->make('client_report_delivery_service');$opts=static fn($p)=>['auth'=>true,'tenant'=>true,'permission'=>$p];$json=static fn($d)=>Response::json(['data'=>$d])->withHeader('Cache-Control','no-store');
    $router->get('/api/v1/client-report-schedules',static fn():Response=>$json($service()->schedules()),$opts('client_reports.view'));
    $router->post('/api/v1/client-report-schedules',static fn(Request $r):Response=>$json($service()->save($r->json(),true)),$opts('client_reports.manage'));
    $router->get('/api/v1/client-report-deliveries',static fn():Response=>$json($service()->history()),$opts('client_reports.view'));
    $router->post('/api/v1/client-report-deliveries/{id}/retry',static fn(Request $r,array $p):Response=>$json($service()->retry((string)$p['id'],$r->json())),$opts('client_reports.manage'));
    $router->get('/api/v1/client/report-preferences',static fn():Response=>$json($service()->preferences()),$opts('client_reports.schedule'));
    $router->post('/api/v1/client/report-preferences',static fn(Request $r):Response=>$json($service()->save($r->json())),$opts('client_reports.schedule'));
    $router->get('/api/v1/client/report-deliveries',static fn():Response=>$json($service()->history(true)),$opts('client_reports.schedule'));
};
