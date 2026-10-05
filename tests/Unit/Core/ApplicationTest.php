<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use PDO;
use Sentinel\Core\Application;
use Sentinel\Core\Config;
use Sentinel\Core\Database;
use Sentinel\Core\Logger;
use Sentinel\Core\MiddlewarePipeline;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Core\Router;
use Sentinel\Core\RuntimeContext;
use Sentinel\Security\JwtService;

final class ApplicationTest extends TestCase
{
    private function projectRoot(): string
    {
        return dirname(__DIR__, 3);
    }

    public function testBootstrapLoadsExistingConfiguration(): void
    {
        $app = Application::create($this->projectRoot())->bootstrap();

        // Compare against the real env() resolution rather than a literal, since a
        // developer .env may legitimately override APP_NAME locally.
        self::assertInstanceOf(Config::class, $app->config());
        self::assertSame(env('APP_NAME', 'Sentinel AI Security Suite'), $app->config()->get('app.name'));
    }

    public function testContainerRegistersCoreServices(): void
    {
        $container = Application::create($this->projectRoot())->bootstrap()->container();

        self::assertTrue($container->has('config'));
        self::assertTrue($container->has('logger'));
        self::assertTrue($container->has('database'));
        self::assertTrue($container->has('runtime_context'));
        self::assertTrue($container->has('router'));
        self::assertTrue($container->has('middleware_pipeline'));
    }

    public function testLoggerIsRegisteredAsSingleton(): void
    {
        $app = Application::create($this->projectRoot())->bootstrap();

        self::assertInstanceOf(Logger::class, $app->logger());
        self::assertSame($app->logger(), $app->logger());
    }

    public function testDatabaseIsRegisteredAsSingleton(): void
    {
        $app = Application::create($this->projectRoot())->bootstrap();

        self::assertInstanceOf(Database::class, $app->database());
        self::assertSame($app->database(), $app->database());
    }

    public function testRouterIsRegisteredAsSingleton(): void
    {
        $app = Application::create($this->projectRoot())->bootstrap();

        self::assertInstanceOf(Router::class, $app->router());
        self::assertSame($app->router(), $app->router());
    }

    public function testMiddlewarePipelineIsRegisteredAsSingleton(): void
    {
        $app = Application::create($this->projectRoot())->bootstrap();

        self::assertInstanceOf(MiddlewarePipeline::class, $app->middlewarePipeline());
        self::assertSame($app->middlewarePipeline(), $app->middlewarePipeline());
    }

    public function testRuntimeContextIsRegisteredAsSingletonAndStartsUnauthenticatedWithNoTenant(): void
    {
        $app = Application::create($this->projectRoot())->bootstrap();
        $context = $app->runtimeContext();

        self::assertInstanceOf(RuntimeContext::class, $context);
        self::assertSame($context, $app->runtimeContext());
        self::assertFalse($context->isAuthenticated());
        self::assertNull($context->tenantId());
        self::assertNotSame('', $context->requestId());
    }

    public function testBootstrapDoesNotConnectToDatabase(): void
    {
        $app = Application::create($this->projectRoot())->bootstrap();

        self::assertFalse($app->database()->isConnected());
    }

    public function testRetrievingDatabaseServiceViaContainerDoesNotConnect(): void
    {
        $app = Application::create($this->projectRoot())->bootstrap();

        $database = $app->container()->make('database');

        self::assertFalse($database->isConnected());
    }

    public function testHandleUsesApplicationRoutes(): void
    {
        $app = Application::create($this->projectRoot())->bootstrap();

        $response = $app->handle(Request::create('GET', '/'));

        self::assertInstanceOf(Response::class, $response);
        self::assertSame(200, $response->status());
        self::assertSame('text/html; charset=utf-8', $response->header('Content-Type'));
        self::assertStringContainsString('Every site. Every shift.', $response->body());
    }

    public function testPublicMarketingRoutesRenderWithoutAuthentication(): void
    {
        $app = Application::create($this->projectRoot())->bootstrap();

        foreach (['/pricing', '/about', '/security', '/terms', '/privacy'] as $path) {
            $response = $app->handle(Request::create('GET', $path));
            self::assertSame(200, $response->status(), $path);
            self::assertSame('text/html; charset=utf-8', $response->header('Content-Type'), $path);
            self::assertStringContainsString('Sentinel', $response->body(), $path);
        }
    }

    public function testHandleAttachesRequestToRuntimeContext(): void
    {
        $app = Application::create($this->projectRoot())->bootstrap();
        $request = Request::create('GET', '/anything');

        $app->handle($request);

        self::assertSame($request, $app->runtimeContext()->request());
    }

    public function testHandleResponseCarriesRuntimeContextRequestId(): void
    {
        $app = Application::create($this->projectRoot())->bootstrap();

        $response = $app->handle(Request::create('GET', '/anything'));

        self::assertSame($app->runtimeContext()->requestId(), $response->header('X-Request-Id'));
        self::assertSame('nosniff', $response->header('X-Content-Type-Options'));
        self::assertSame('DENY', $response->header('X-Frame-Options'));
        self::assertSame('no-referrer', $response->header('Referrer-Policy'));
    }

    public function testApplicationRejectsOversizedAndExplicitNonJsonBodies(): void
    {
        $app = Application::create($this->projectRoot())->bootstrap();
        $app->config()->set('security.max_json_body_bytes', 5);

        foreach ([
            Request::create('POST', '/api/v1/auth/login', [], ['Content-Type' => 'application/json'], '123456'),
            Request::create('POST', '/api/v1/auth/login', [], ['Content-Type' => 'text/plain'], '{}'),
        ] as $request) {
            try {
                $app->handle($request);
                self::fail('Expected request boundary rejection.');
            } catch (\Sentinel\Core\HttpException $exception) {
                self::assertContains($exception->statusCode(), [413, 415]);
            }
        }
    }

    public function testAllManagementApiRoutesRequireAuthenticationTenantAndPermission(): void
    {
        $routes = Application::create($this->projectRoot())->bootstrap()->router()->securityMetadata();
        $publicPaths = [];

        foreach ($routes as $route) {
            if (!str_starts_with($route['path'], '/api/v1/')) {
                continue;
            }
            if (!$route['auth']) {
                $publicPaths[] = $route['path'];
                continue;
            }
            if (str_starts_with($route['path'], '/api/v1/auth/')) {
                continue;
            }

            self::assertTrue($route['tenant'], $route['path'] . ' must require tenant context.');
            self::assertNotNull($route['permission'], $route['path'] . ' must require a permission.');
            self::assertNotSame('', trim((string) $route['permission']));
        }

        self::assertSame(['/api/v1/status', '/api/v1/plans', '/api/v1/auth/signup', '/api/v1/auth/login'], $publicPaths);
    }

    public function testApiRouteAndUnknownRoute(): void
    {
        $app = Application::create($this->projectRoot())->bootstrap();

        self::assertSame(200, $app->handle(Request::create('GET', '/api/v1/status'))->status());
        self::assertSame(404, $app->handle(Request::create('GET', '/unknown'))->status());
    }

    public function testPublicLoginRouteReturnsJwtWithoutAuthenticationMiddleware(): void
    {
        $app = Application::create($this->projectRoot())->bootstrap();
        $database = new Database(new Config(['database' => []]), static fn (): PDO => new PDO('sqlite::memory:', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]));
        $database->execute('CREATE TABLE users (user_id INTEGER PRIMARY KEY, username TEXT, email TEXT, password_hash TEXT, status TEXT)');
        $database->execute('INSERT INTO users VALUES (7, :username, :email, :hash, :status)', [
            'username' => 'guard7', 'email' => 'guard@example.test', 'hash' => password_hash('secret', PASSWORD_DEFAULT), 'status' => 'ACTIVE',
        ]);
        $app->container()->instance('database', $database);
        $app->container()->instance('jwt', new JwtService(new Config(['jwt' => ['algorithm' => 'HS256', 'secret' => 'test-secret-that-is-at-least-32-bytes-long', 'issuer' => 'test', 'ttl' => 3600]])));

        $response = $app->handle(Request::create('POST', '/api/v1/auth/login', [], ['Content-Type' => 'application/json'], '{"identifier":"guard@example.test","password":"secret"}'));
        $body = json_decode($response->body(), true);

        self::assertSame(200, $response->status());
        self::assertIsString($body['data']['token']);
        self::assertSame('7', $app->jwt()->validateToken($body['data']['token'])->claims()->get('sub'));
    }

    public function testLoginRouteRejectsMalformedInputWithoutRequiringBearerToken(): void
    {
        $app = Application::create($this->projectRoot())->bootstrap();

        try {
            $app->handle(Request::create('POST', '/api/v1/auth/login', [], ['Content-Type' => 'application/json'], '{"identifier":"only"'));
            self::fail('Expected malformed JSON failure.');
        } catch (\Sentinel\Core\HttpException $exception) {
            self::assertSame(400, $exception->statusCode());
            self::assertSame('invalid_json', $exception->errorCode());
        }
    }

    public function testBootstrapReturnsApplicationInstanceForFluentUse(): void
    {
        $app = Application::create($this->projectRoot());

        self::assertSame($app, $app->bootstrap());
    }
}
