<?php

declare(strict_types=1);

namespace Sentinel\Security;

use Sentinel\Core\HttpException;
use Sentinel\Core\MiddlewareInterface;
use Sentinel\Core\Request;
use Sentinel\Core\Response;
use Sentinel\Core\RuntimeContext;

final class AuthorizationMiddleware implements MiddlewareInterface
{
    private string $permission;

    public function __construct(
        private readonly AuthorizationService $authorizationService,
        private readonly RuntimeContext $context,
    ) {
        $this->permission = '';
    }

    public function forPermission(string $permission): self
    {
        $this->permission = $permission;

        return $this;
    }

    public function process(Request $request, callable $next): Response
    {
        if ($this->permission === '') {
            return $next($request);
        }

        $userId = $this->context->user();
        if (!is_string($userId) || $userId === '') {
            throw new HttpException(401, 'Authentication failed.', 'authentication_failed');
        }

        $this->authorizationService->requirePermission($userId, $this->permission);

        return $next($request);
    }
}
