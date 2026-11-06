<?php
declare(strict_types=1);
use Sentinel\Core\{Container,Router,Request,Response};
return static function(Router $router,Container $container):void {
    $service=static fn()=>$container->make('photo_report_service');$opts=static fn($permission)=>['auth'=>true,'tenant'=>true,'permission'=>$permission];$json=static fn($data)=>Response::json(['data'=>$data])->withHeader('Cache-Control','no-store');
    $router->get('/api/v1/photo-reports',static fn():Response=>$json($service()->reports()),$opts('photo_reports.view'));
    $router->get('/api/v1/photo-reports/templates',static fn():Response=>$json($service()->templates()),$opts('photo_reports.view'));
    $router->post('/api/v1/photo-reports/templates',static fn(Request $r):Response=>$json($service()->createTemplate($r->json())),$opts('photo_reports.manage'));
    $router->post('/api/v1/photo-reports',static fn(Request $r):Response=>$json($service()->createReport($r->json())),$opts('photo_reports.manage'));
    $router->post('/api/v1/photo-reports/import-field-photo',static fn(Request $r):Response=>$json($service()->importFieldPhoto($r->json())),$opts('evidence.manage'));
    $router->get('/api/v1/photo-reports/{id}',static fn(Request $r,array $p):Response=>$json($service()->detail((string)$p['id'])),$opts('photo_reports.view'));
    foreach(['items'=>'addItem','update'=>'updateReport','approve'=>'approve']as$path=>$method)$router->post('/api/v1/photo-reports/{id}/'.$path,static fn(Request $r,array $p):Response=>$json($service()->$method((string)$p['id'],$r->json())),$opts('photo_reports.manage'));
    foreach(['annotate'=>'annotate','remove'=>'removeItem']as$path=>$method)$router->post('/api/v1/photo-report-items/{id}/'.$path,static fn(Request $r,array $p):Response=>$json($service()->$method((string)$p['id'],$r->json())),$opts('photo_reports.manage'));
    $router->get('/api/v1/photo-report-items/{id}/image',static function(Request $r,array $p)use($service):Response{$image=$service()->itemImage((string)$p['id']);return Response::raw($image['bytes'],200,['Content-Type'=>$image['mime'],'Cache-Control'=>'no-store','X-Content-Type-Options'=>'nosniff']);},$opts('photo_reports.export'));
    $router->get('/api/v1/photo-reports/{id}/printable',static fn(Request $r,array $p):Response=>Response::raw($service()->printable((string)$p['id']),200,['Content-Type'=>'text/html; charset=utf-8','Content-Disposition'=>'attachment; filename="sentinel-photo-report-'.(int)$p['id'].'.html"','Cache-Control'=>'no-store','X-Content-Type-Options'=>'nosniff']),$opts('photo_reports.export'));
};
