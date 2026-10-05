<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Core\Router;

final class ContractPostRouteTest extends TestCase
{
    public function testContractAndPostRoutesAreProtected(): void
    {
        $router = new Router(); $handler = static fn (): Response => Response::raw('ok');
        foreach ([['GET','/api/v1/contracts','contracts.view'],['GET','/api/v1/contracts/1','contracts.view'],['POST','/api/v1/contracts','contracts.create'],['PATCH','/api/v1/contracts/1','contracts.update'],['GET','/api/v1/posts','posts.view'],['GET','/api/v1/posts/1','posts.view'],['POST','/api/v1/posts','posts.create'],['PATCH','/api/v1/posts/1','posts.update']] as [$method,$path,$permission]) $router->{strtolower($method)}($path, $handler, ['auth'=>true,'tenant'=>true,'permission'=>$permission]);
        $calls=[]; $auth=static function(Request $r,callable$n)use(&$calls):Response{$calls[]='auth';return$n($r);}; $authz=static function(Request$r,callable$n,string$p)use(&$calls):Response{$calls[]=$p;return$n($r);}; $tenant=static function(Request$r,callable$n)use(&$calls):Response{$calls[]='tenant';return$n($r);};
        self::assertSame('ok',$router->dispatch(Request::create('GET','/api/v1/contracts'),$auth,$authz,$tenant)->body());
        self::assertSame(['auth','tenant','contracts.view'],$calls);
        self::assertSame('ok',$router->dispatch(Request::create('PATCH','/api/v1/posts/1'),$auth,$authz,$tenant)->body());
    }
}
