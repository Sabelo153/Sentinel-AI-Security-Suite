<?php

declare(strict_types=1);

namespace Sentinel\Core;

/**
 * Small application-owned contract for ordered request/response processing.
 * Security middleware is deliberately layered on later phases.
 */
interface MiddlewareInterface
{
    public function process(Request $request, callable $next): Response;
}