<?php
declare(strict_types=1);
use Sentinel\Core\{Container,Router,Request,Response,HttpException};

return static function(Router $router,Container $container):void{
    $field=static fn()=>$container->make('field_operations_service');
    $security=static fn()=>$container->make('session_security_service');
    $options=static fn(string $permission):array=>['auth'=>true,'tenant'=>true,'permission'=>$permission];
    $json=static fn(mixed $data):Response=>Response::json(['data'=>$data])->withHeader('Cache-Control','no-store');

    foreach(['compliance'=>'compliance','equipment'=>'equipment','handovers'=>'equipment','escalation-rules'=>'escalations','escalations'=>'escalations','portal-access'=>'client_portal']as$resource=>$module){
        $router->get('/api/v1/field/'.$resource,static fn():Response=>$json($field()->list($resource)),$options($module.'.view'));
    }
    foreach(['compliance'=>['createCompliance','compliance'],'equipment'=>['createEquipment','equipment'],'handovers'=>['issue','equipment'],'escalation-rules'=>['createRule','escalations'],'portal-access'=>['grantPortal','client_portal'],'publish'=>['publish','client_portal']]as$path=>[$method,$module]){
        $router->post('/api/v1/field/'.$path,static fn(Request $request):Response=>$json($field()->$method($request->json())),$options($module.'.manage'));
    }
    $router->post('/api/v1/field/compliance/{id}/verify',static fn(Request $r,array $p):Response=>$json($field()->verifyCompliance((string)$p['id'])),$options('compliance.manage'));
    $router->post('/api/v1/field/compliance/{id}/renew',static fn(Request $r,array $p):Response=>$json($field()->renewCompliance((string)$p['id'],$r->json())),$options('compliance.manage'));
    $router->post('/api/v1/field/handovers/{id}/return',static fn(Request $r,array $p):Response=>$json($field()->returnEquipment((string)$p['id'],$r->json())),$options('equipment.manage'));
    $router->post('/api/v1/field/escalations/{id}/acknowledge',static fn(Request $r,array $p):Response=>$json($field()->acknowledge((string)$p['id'])),$options('escalations.manage'));
    $router->post('/api/v1/field/escalation-rules/{id}/disable',static fn(Request $r,array $p):Response=>$json($field()->disableRule((string)$p['id'])),$options('escalations.manage'));
    $router->post('/api/v1/field/portal-access/revoke',static fn(Request $r):Response=>$json($field()->revokePortal($r->json())),$options('client_portal.manage'));
    $router->post('/api/v1/field/report-draft',static fn(Request $r):Response=>$json($field()->reportDraft($r->json())),$options('client_portal.manage'));
    $router->get('/api/v1/client-portal',static fn():Response=>$json($field()->portal()),$options('client_portal.read'));
    $router->post('/api/v1/field/attendance/{id}/approve',static fn(Request $r,array $p):Response=>$json($field()->approveAttendance((string)$p['id'])),$options('payroll_export.manage'));
    $router->get('/api/v1/field/payroll',static fn(Request $r):Response=>$json($field()->payroll($r->queryParams())),$options('payroll_export.view'));
    $router->get('/api/v1/field/payroll/csv',static function(Request $r)use($field,$container):Response{
        $report=$field()->payroll($r->queryParams());$csv=$container->make('csv_export_service')->export($report['rows']);
        return Response::raw($csv,200,['Content-Type'=>'text/csv; charset=utf-8','Content-Disposition'=>'attachment; filename="sentinel-approved-hours.csv"','Cache-Control'=>'no-store','X-Stale-Approvals-Excluded'=>(string)$report['stale_approvals_excluded']]);
    },$options('payroll_export.view'));

    $router->get('/api/v1/account/security',static fn():Response=>$json(['mfa'=>$security()->status(),'sessions'=>$security()->sessions()]),$options('users.password_change'));
    $router->post('/api/v1/auth/logout',static function(Request $r)use($security,$container,$json):Response{$token=$container->make('jwt')->validateToken(\Sentinel\Security\BearerToken::fromRequest($r));return $json($security()->logout($token->claims()->get('jti')));},['auth'=>true,'tenant'=>true]);
    foreach(['setup','confirm','disable']as$action)$router->post('/api/v1/account/mfa/'.$action,static fn(Request $r):Response=>$json($security()->$action($r->json())),$options('users.password_change'));
    $router->post('/api/v1/account/sessions/{id}/revoke',static fn(Request $r,array $p):Response=>$json($security()->revoke((string)$p['id'])),$options('users.password_change'));
    $router->get('/api/v1/support/sessions',static fn():Response=>$json($security()->sessions(true)),$options('support.manage'));
    $router->post('/api/v1/support/sessions/{id}/revoke',static fn(Request $r,array $p):Response=>$json($security()->revoke((string)$p['id'],true)),$options('support.manage'));
    $router->get('/api/v1/support/grants',static fn():Response=>$json($security()->grants()),$options('support.manage'));
    $router->post('/api/v1/support/grants',static fn(Request $r):Response=>$json($security()->grant($r->json())),$options('support.manage'));
    $router->post('/api/v1/support/grants/{id}/revoke',static fn(Request $r,array $p):Response=>$json($security()->revokeGrant((string)$p['id'])),$options('support.manage'));
    $router->get('/api/v1/support/delegated-overview',static function()use($security,$container,$json):Response{$security()->assertDelegatedAccess();return $json($container->make('support_workspace_service')->overview());},$options('support.delegated_view'));

    $router->post('/api/v1/guard/diagnostics',static function(Request $r)use($container,$json):Response{
        $input=$r->json();if(array_diff(array_keys($input),['installation_id','app_version','pending_count','blocked_count','network_state']))throw HttpException::unprocessable('Unknown diagnostic fields.');
        if(!is_string($input['installation_id']??null)||preg_match('/^[a-f0-9-]{36}$/Di',$input['installation_id'])!==1||!is_string($input['app_version']??null)||strlen($input['app_version'])>40)throw HttpException::unprocessable('Invalid installation or app version.');
        foreach(['pending_count','blocked_count']as$key)if(!is_int($input[$key]??null)||$input[$key]<0||$input[$key]>500)throw HttpException::unprocessable('Invalid queue count.');
        if($input['blocked_count']>$input['pending_count']||!in_array($input['network_state']??null,['VALIDATED','UNVERIFIED'],true))throw HttpException::unprocessable('Invalid diagnostic state.');
        $db=$container->make('database');$context=$container->make('runtime_context');$keys=['company'=>$context->requireTenant(),'actor'=>$context->user(),'installation'=>$input['installation_id']];
        $db->execute('DELETE FROM mobile_diagnostics WHERE company_id=:company AND user_id=:actor AND installation_id=:installation',$keys);
        $db->execute('INSERT INTO mobile_diagnostics(company_id,user_id,installation_id,app_version,pending_count,blocked_count,network_state,received_at) VALUES(:company,:actor,:installation,:version,:pending,:blocked,:network,:now)',$keys+['version'=>$input['app_version'],'pending'=>$input['pending_count'],'blocked'=>$input['blocked_count'],'network'=>$input['network_state'],'now'=>gmdate('Y-m-d H:i:s')]);return $json(['received'=>true]);
    },$options('guard.self'));
    $router->get('/api/v1/support/mobile-diagnostics',static function()use($container,$json):Response{return $json($container->make('database')->fetchAll('SELECT user_id,installation_id,app_version,pending_count,blocked_count,network_state,received_at FROM mobile_diagnostics WHERE company_id=:company ORDER BY received_at DESC LIMIT 200',['company'=>$container->make('runtime_context')->requireTenant()]));},$options('support.view'));
    foreach(['incident'=>'incidents.view','occurrence'=>'occurrence_book.view']as$type=>$permission)$router->get('/api/v1/report-photos/'.$type.'/{id}',static function(Request $r,array $p)use($container,$json,$type):Response{return $json($container->make('database')->fetchAll('SELECT photo_id,image_base64,created_at FROM report_photos WHERE company_id=:company AND report_type=:type AND report_id=:id LIMIT 10',['company'=>$container->make('runtime_context')->requireTenant(),'type'=>$type,'id'=>$p['id']]));},$options($permission));
};
