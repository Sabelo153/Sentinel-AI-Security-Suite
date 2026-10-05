<?php

declare(strict_types=1);

namespace Sentinel\Core;

/**
 * Executes middleware in registration order around one explicit destination.
 * The pipeline does not catch exceptions: callers must be able to distinguish
 * application failures from a normal middleware response.
 */
final class MiddlewarePipeline
{
    /** @var array<int, MiddlewareInterface> */
    private array $middleware = [];

    public function add(MiddlewareInterface $middleware): self
    {
        $this->middleware[] = $middleware;

        return $this;
    }

    public function handle(Request $request, callable $destination): Response
    {
        $next = function (Request $nextRequest) use ($destination): Response {
            $response = $destination($nextRequest);

            return $this->requireResponse($response);
        };

        foreach (array_reverse($this->middleware) as $middleware) {
            $current = $middleware;
            $next = static function (Request $nextRequest) use ($current, $next): Response {
                $response = $current->process($nextRequest, $next);

                if (!$response instanceof Response) {
                    throw new \UnexpectedValueException('Middleware must return a Response instance.');
                }

                return $response;
            };
        }

        return $next($request);
    }

    private function requireResponse(mixed $response): Response
    {
        if (!$response instanceof Response) {
            throw new \UnexpectedValueException('Middleware destination must return a Response instance.');
        }

        return $response;
    }
}