<?php
declare(strict_types=1);
use Sentinel\Core\{Container,Router,Request,Response};
return static function(Router $router,Container $container):void {
    $o=static fn(string $p):array=>['auth'=>true,'tenant'=>true,'permission'=>$p];$j=static fn(mixed $v):Response=>Response::json(['data'=>$v])->withHeader('Cache-Control','no-store');
    $offers=static fn()=>$container->make('open_shift_offer_service');$visitors=static fn()=>$container->make('visitor_appointment_service');$fleet=static fn()=>$container->make('fleet_operations_service');$renewals=static fn()=>$container->make('contract_renewal_service');$backups=static fn()=>$container->make('encrypted_backup_service');$pilot=static fn()=>$container->make('pilot_setup_service');
    $router->get('/api/v1/auth/sponsor',static fn():Response=>$j($container->make('sponsor_service')->placement()),['auth'=>true,'tenant'=>true]);
    foreach([false,true]as$self){$path=$self?'/api/v1/my-shift-offers':'/api/v1/open-shift-offers';$router->get($path,static fn():Response=>$j($offers()->list($self)),$o($self?'shift_offers.self':'shift_offers.view'));}
    $router->post('/api/v1/open-shift-offers',static fn(Request $r):Response=>$j($offers()->broadcast($r->json())),$o('shift_offers.manage'));
    $router->post('/api/v1/my-shift-offers/{id}/accept',static fn(Request $r,array $p):Response=>$j($offers()->accept((string)$p['id'])),$o('shift_offers.self'));
    $router->post('/api/v1/open-shift-offers/{id}/award',static fn(Request $r,array $p):Response=>$j($offers()->award((string)$p['id'],$r->json())),$o('shift_offers.manage'));
    $router->post('/api/v1/open-shift-offers/{id}/cancel',static fn(Request $r,array $p):Response=>$j($offers()->cancel((string)$p['id'])),$o('shift_offers.manage'));
    foreach([false,true]as$client){$path=$client?'/api/v1/client/visitor-appointments':'/api/v1/visitor-appointments';$permission=$client?'visitor_invites.client':'visitor_invites.view';$router->get($path,static fn(Request $r):Response=>$j($visitors()->list($r->queryParams(),$client)),$o($permission));$router->post($path,static fn(Request $r):Response=>$j($visitors()->create($r->json(),$client)),$o($client?'visitor_invites.client':'visitor_invites.manage'));$router->post($path.'/{id}/cancel',static fn(Request $r,array $p):Response=>$j($visitors()->transition((string)$p['id'],'cancel',$r->json(),$client)),$o($client?'visitor_invites.client':'visitor_invites.manage'));}
    $router->post('/api/v1/client/visitor-appointments/{id}/approve',static fn(Request $r,array $p):Response=>$j($visitors()->approve((string)$p['id'],$r->json())),$o('visitor_invites.client'));
    $router->get('/api/v1/visitor-gate',static fn(Request $r):Response=>$j($visitors()->list($r->queryParams())),$o('visitor_invites.gate'));
    $router->post('/api/v1/visitor-gate/check-in',static fn(Request $r):Response=>$j($visitors()->checkIn($r->json())),$o('visitor_invites.gate'));
    $router->post('/api/v1/visitor-gate/{id}/checkout',static fn(Request $r,array $p):Response=>$j($visitors()->transition((string)$p['id'],'checkout',$r->json())),$o('visitor_invites.gate'));
    foreach([false,true]as$self){$path=$self?'/api/v1/my-fleet':'/api/v1/fleet';$router->get($path,static fn(Request $r):Response=>$j($fleet()->list($r->queryParams(),$self)),$o($self?'fleet.self':'fleet.view'));foreach(['start','finish','cancel']as$action)$router->post(($self?'/api/v1/my-fleet-trips':'/api/v1/fleet-trips').'/{id}/'.$action,static fn(Request $r,array $p):Response=>$j($fleet()->tripAction((string)$p['id'],$action,$r->json(),$self)),$o($self?'fleet.self':'fleet.manage'));}
    $router->post('/api/v1/fleet',static fn(Request $r):Response=>$j($fleet()->create($r->json())),$o('fleet.manage'));
    foreach(['allocate','fuel','service']as$action)$router->post('/api/v1/fleet/{id}/'.$action,static fn(Request $r,array $p):Response=>$j($fleet()->$action((string)$p['id'],$r->json())),$o('fleet.manage'));
    foreach([false,true]as$client)$router->get($client?'/api/v1/client/contract-renewals':'/api/v1/contract-renewals',static fn():Response=>$j($renewals()->list($client)),$o($client?'renewals.client':'renewals.view'));
    $router->post('/api/v1/contract-renewals',static fn(Request $r):Response=>$j($renewals()->propose($r->json())),$o('renewals.manage'));
    foreach(['accept','decline']as$action)$router->post('/api/v1/client/contract-renewals/{id}/'.$action,static fn(Request $r,array $p):Response=>$j($renewals()->decide((string)$p['id'],$action,$r->json())),$o('renewals.client'));
    $router->post('/api/v1/contract-renewals/{id}/schedule',static fn(Request $r,array $p):Response=>$j($renewals()->schedule((string)$p['id'],$r->json())),$o('renewals.manage'));
    $router->post('/api/v1/contract-renewals/{id}/cancel',static fn(Request $r,array $p):Response=>$j($renewals()->cancel((string)$p['id'])),$o('renewals.manage'));
    $router->get('/api/v1/encrypted-backups',static fn():Response=>$j($backups()->overview()),$o('backups.view'));
    $router->post('/api/v1/encrypted-backups/schedule',static fn(Request $r):Response=>$j($backups()->schedule($r->json())),$o('backups.manage'));
    $router->post('/api/v1/encrypted-backups/request',static fn():Response=>$j($backups()->request()),$o('backups.manage'));
    $router->get('/api/v1/pilot-setup',static fn():Response=>$j($pilot()->overview()),$o('pilot_setup.view'));
    $router->post('/api/v1/pilot-setup/roles',static fn(Request $r):Response=>$j($pilot()->role($r->json())),$o('pilot_setup.manage'));
    $router->post('/api/v1/pilot-setup/settings',static fn(Request $r):Response=>$j($pilot()->settings($r->json())),$o('pilot_setup.manage'));
    $router->post('/api/v1/pilot-setup/demo',static fn():Response=>$j($pilot()->demo()),$o('pilot_setup.manage'));
    $router->get('/api/v1/business-reminders',static fn():Response=>$j($container->make('business_reminder_service')->list()),$o('pilot_setup.view'));
};
