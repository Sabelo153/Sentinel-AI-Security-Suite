<?php
declare(strict_types=1);
use Sentinel\Core\{Container,Router,Request,Response};
return static function(Router $router,Container $container):void {
    $opts=static fn(string $p):array=>['auth'=>true,'tenant'=>true,'permission'=>$p];
    $json=static fn(mixed $data):Response=>Response::json(['data'=>$data])->withHeader('Cache-Control','no-store');
    $access=static fn()=>$container->make('site_access_service');$forms=static fn()=>$container->make('inspection_forms_service');$care=static fn()=>$container->make('client_care_service');$leave=static fn()=>$container->make('availability_service');$quotes=static fn()=>$container->make('quotation_service');
    $router->get('/api/v1/site-workspace/sites',static fn():Response=>$json($access()->sites()),$opts('site_workspace.view'));
    $router->get('/api/v1/site-workspace',static fn(Request $r):Response=>$json($access()->workspace($r->queryParams())),$opts('site_workspace.view'));
    $router->get('/api/v1/site-access',static fn():Response=>$json($access()->grants()),$opts('site_access.view'));
    $router->post('/api/v1/site-access',static fn(Request $r):Response=>$json($access()->grant($r->json())),$opts('site_access.manage'));
    $router->post('/api/v1/site-access/{id}/revoke',static fn(Request $r,array $p):Response=>$json($access()->revoke((string)$p['id'])),$opts('site_access.manage'));
    $router->post('/api/v1/site-access/role',static fn(Request $r):Response=>$json($access()->restrictRole($r->json())),$opts('site_access.manage'));
    $router->get('/api/v1/inspection-forms',static fn(Request $r):Response=>$json($forms()->templates($r->queryParams())),$opts('inspection_forms.view'));
    $router->post('/api/v1/inspection-forms',static fn(Request $r):Response=>$json($forms()->publish($r->json())),$opts('inspection_forms.manage'));
    $router->get('/api/v1/inspection-form-history',static fn(Request $r):Response=>$json($forms()->history($r->queryParams())),$opts('inspection_forms.view'));
    $router->post('/api/v1/inspection-forms/{id}/submit',static fn(Request $r,array $p):Response=>$json($forms()->submit((string)$p['id'],$r->json())),$opts('inspection_forms.view'));
    $router->post('/api/v1/inspection-followups/{id}/resolve',static fn(Request $r,array $p):Response=>$json($forms()->resolve((string)$p['id'],$r->json())),$opts('inspection_forms.manage'));
    $router->get('/api/v1/inspection-photos/{id}',static function(Request $r,array $p)use($forms):Response{$photo=$forms()->photo((string)$p['id']);return Response::raw($photo['bytes'],200,['Content-Type'=>$photo['mime'],'Cache-Control'=>'no-store','X-Content-Type-Options'=>'nosniff']);},$opts('inspection_forms.view'));
    $router->get('/api/v1/client/care-sites',static fn():Response=>$json($care()->clientSites()),$opts('client_requests.client'));
    foreach([false,true]as$client){
        $base=$client?'/api/v1/client/requests':'/api/v1/client-requests';$view=$client?'client_requests.client':'client_requests.view';$manage=$client?'client_requests.client':'client_requests.manage';
        $router->get($base,static fn(Request $r):Response=>$json($care()->requests($r->queryParams(),$client)),$opts($view));
        $router->post($base,static fn(Request $r):Response=>$json($care()->create($r->json(),$client)),$opts($manage));
        $router->get($base.'/{id}/history',static fn(Request $r,array $p):Response=>$json($care()->history((string)$p['id'],$client)),$opts($view));
        foreach($client?['reply','confirm','reopen']:['begin','reply','resolve']as$action)$router->post($base.'/{id}/'.$action,static fn(Request $r,array $p):Response=>$json($care()->action((string)$p['id'],$action,$r->json(),$client)),$opts($manage));
        $path=$client?'/api/v1/my-availability':'/api/v1/availability';$view=$client?'availability.self':'availability.view';$manage=$client?'availability.self':'availability.manage';
        $router->get($path,static fn():Response=>$json($leave()->list($client)),$opts($view));
        $router->post($path,static fn(Request $r):Response=>$json($leave()->request($r->json(),$client)),$opts($manage));
        foreach($client?['cancel']:['approve','reject','cancel']as$action)$router->post($path.'/{id}/'.$action,static fn(Request $r,array $p):Response=>$json($leave()->review((string)$p['id'],$action,$r->json(),$client)),$opts($manage));
        $path=$client?'/api/v1/client/quotations':'/api/v1/quotations';$permission=$client?'quotations.client':'quotations.view';
        $router->get($path,static fn():Response=>$json($quotes()->list($client)),$opts($permission));
        $router->get($path.'/{id}',static fn(Request $r,array $p):Response=>$json($quotes()->detail((string)$p['id'],$client)),$opts($permission));
    }
    $router->get('/api/v1/client/report-reviews',static fn():Response=>$json($care()->reportsForReview()),$opts('report_reviews.client'));
    $router->post('/api/v1/client/report-reviews/{id}/acknowledge',static fn(Request $r,array $p):Response=>$json($care()->acknowledge((string)$p['id'],$r->json())),$opts('report_reviews.client'));
    $router->get('/api/v1/report-reviews',static fn(Request $r):Response=>$json($care()->reviews($r->queryParams())),$opts('report_reviews.view'));
    $router->post('/api/v1/quotations',static fn(Request $r):Response=>$json($quotes()->create($r->json())),$opts('quotations.manage'));
    $router->patch('/api/v1/quotations/{id}',static fn(Request $r,array $p):Response=>$json($quotes()->update((string)$p['id'],$r->json())),$opts('quotations.manage'));
    $router->post('/api/v1/quotations/{id}/share',static fn(Request $r,array $p):Response=>$json($quotes()->share((string)$p['id'],$r->json())),$opts('quotations.manage'));
    foreach(['accept','decline']as$action)$router->post('/api/v1/client/quotations/{id}/'.$action,static fn(Request $r,array $p):Response=>$json($quotes()->decide((string)$p['id'],$action,$r->json())),$opts('quotations.client'));
    $router->post('/api/v1/quotations/{id}/convert',static fn(Request $r,array $p):Response=>$json($quotes()->convert((string)$p['id'],$r->json())),$opts('quotations.manage'));
    $router->get('/api/v1/site-onboardings',static fn():Response=>$json($quotes()->onboardings()),$opts('quotations.view'));
    $router->post('/api/v1/mobilisation-tasks/{id}/complete',static fn(Request $r,array $p):Response=>$json($quotes()->completeTask((string)$p['id'],$r->json())),$opts('quotations.manage'));
    $router->post('/api/v1/site-onboardings/{id}/activate',static fn(Request $r,array $p):Response=>$json($quotes()->activate((string)$p['id'])),$opts('quotations.manage'));
};
