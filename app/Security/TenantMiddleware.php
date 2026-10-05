<?php

declare(strict_types=1);

namespace Sentinel\Security;

use Sentinel\Core\MiddlewareInterface;
use Sentinel\Core\Request;
use Sentinel\Core\Response;

final class TenantMiddleware implements MiddlewareInterface
{
	public function __construct(private readonly TenantContextService $tenantContext)
	{
	}

	public function process(Request $request, callable $next): Response
	{
		$this->tenantContext->resolveForAuthenticatedUser();

		return $next($request);
	}
}