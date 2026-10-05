<?php

declare(strict_types=1);

namespace Sentinel\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use Sentinel\Core\MiddlewareInterface;
use Sentinel\Core\MiddlewarePipeline;
use Sentinel\Core\Request;
use Sentinel\Core\Response;

final class MiddlewarePipelineTest extends TestCase
{
    public function testMiddlewareRunsInRegistrationOrderAroundDestination(): void
    {
        $events = [];
        $pipeline = new MiddlewarePipeline();
        $pipeline->add(new RecordingMiddleware('A', $events));
        $pipeline->add(new RecordingMiddleware('B', $events));
        $pipeline->add(new RecordingMiddleware('C', $events));

        $pipeline->handle(Request::create('GET', '/'), function () use (&$events): Response {
            $events[] = 'destination';

            return Response::raw('ok');
        });

        self::assertSame([
            'A before', 'B before', 'C before', 'destination',
            'C after', 'B after', 'A after',
        ], $events);
    }

    public function testEmptyPipelineCallsDestinationWithSameRequestAndResponse(): void
    {
        $pipeline = new MiddlewarePipeline();
        $request = Request::create('GET', '/');
        $response = Response::raw('same');

        $result = $pipeline->handle($request, function (Request $received) use ($request, $response): Response {
            self::assertSame($request, $received);

            return $response;
        });

        self::assertSame($response, $result);
    }

    public function testMiddlewareCanForwardAModifiedImmutableRequest(): void
    {
        $pipeline = new MiddlewarePipeline();
        $modified = null;
        $pipeline->add(new RequestReplacingMiddleware());

        $pipeline->handle(Request::create('GET', '/original'), function (Request $request) use (&$modified): Response {
            $modified = $request;

            return Response::raw('ok');
        });

        self::assertInstanceOf(Request::class, $modified);
        self::assertSame('middleware-request', $modified->requestId());
    }

    public function testMiddlewareCanReplaceResponse(): void
    {
        $pipeline = new MiddlewarePipeline();
        $replacement = Response::raw('replacement', 202);
        $pipeline->add(new ResponseReplacingMiddleware($replacement));

        $result = $pipeline->handle(Request::create('GET', '/'), static fn (): Response => Response::raw('original'));

        self::assertSame($replacement, $result);
    }

    public function testMiddlewareCanShortCircuit(): void
    {
        $events = [];
        $pipeline = new MiddlewarePipeline();
        $pipeline->add(new RecordingMiddleware('A', $events));
        $pipeline->add(new ShortCircuitMiddleware($events));
        $pipeline->add(new RecordingMiddleware('C', $events));

        $response = $pipeline->handle(Request::create('GET', '/'), function () use (&$events): Response {
            $events[] = 'destination';

            return Response::raw('destination');
        });

        self::assertSame(['A before', 'B before', 'B response', 'A after'], $events);
        self::assertSame('short-circuit', $response->body());
    }

    public function testExceptionsRemainObservableWhileUpstreamMiddlewareUnwinds(): void
    {
        $events = [];
        $pipeline = new MiddlewarePipeline();
        $pipeline->add(new RecordingMiddleware('A', $events));

        $this->expectException(\RuntimeException::class);
        try {
            $pipeline->handle(Request::create('GET', '/'), static function (): Response {
                throw new \RuntimeException('downstream failure');
            });
        } finally {
            self::assertSame(['A before'], $events);
        }
    }

    public function testInvalidDestinationResponseFailsClearly(): void
    {
        $this->expectException(\UnexpectedValueException::class);

        (new MiddlewarePipeline())->handle(Request::create('GET', '/'), static fn (): string => 'invalid');
    }
}

final class RecordingMiddleware implements MiddlewareInterface
{
    /** @param array<int, string> $events */
    public function __construct(private string $name, private array &$events)
    {
    }

    public function process(Request $request, callable $next): Response
    {
        $this->events[] = $this->name . ' before';
        $response = $next($request);
        $this->events[] = $this->name . ' after';

        return $response;
    }
}

final class RequestReplacingMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $next): Response
    {
        return $next($request->withRequestId('middleware-request'));
    }
}

final class ResponseReplacingMiddleware implements MiddlewareInterface
{
    public function __construct(private Response $replacement)
    {
    }

    public function process(Request $request, callable $next): Response
    {
        $next($request);

        return $this->replacement;
    }
}

final class ShortCircuitMiddleware implements MiddlewareInterface
{
    /** @param array<int, string> $events */
    public function __construct(private array &$events)
    {
    }

    public function process(Request $request, callable $next): Response
    {
        $this->events[] = 'B before';
        $this->events[] = 'B response';

        return Response::raw('short-circuit');
    }
}