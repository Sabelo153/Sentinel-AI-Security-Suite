<?php

declare(strict_types=1);

namespace Sentinel\Security;

use Sentinel\Core\MiddlewareInterface;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Core\RuntimeContext;

/**
 * Establishes authentication state only. Authorization and tenant decisions
 * remain separate concerns and are intentionally absent here.
 */
final class AuthenticationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly JwtService $jwt,
        private readonly RuntimeContext $context,
    ) {
    }

    public function process(Request $request, callable $next): Response
    {
        $token = $this->jwt->validateToken(BearerToken::fromRequest($request));
        $this->context->setUser((string) $token->claims()->get('sub'));

        return $next($request);
    }
}