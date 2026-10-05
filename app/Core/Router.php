<?php

declare(strict_types=1);

namespace Sentinel\Core;

/**
 * Explicit, instance-based HTTP route table. Route patterns are compiled from
 * literal path segments and named parameters; no user input becomes regex.
 */
final class Router
{
	/** @var array<int, array{methods: array<int, string>, path: string, regex: string, parameters: array<int, string>, handler: callable, auth: bool, tenant: bool, permission: ?string}> */
	private array $routes = [];

	/** @param array{auth?: bool, tenant?: bool, permission?: string|null} $options */
	public function get(string $path, callable $handler, array $options = []): self
	{
		return $this->any(['GET'], $path, $handler, $options);
	}

	/** @param array{auth?: bool, tenant?: bool, permission?: string|null} $options */
	public function post(string $path, callable $handler, array $options = []): self
	{
		return $this->any(['POST'], $path, $handler, $options);
	}

	/** @param array{auth?: bool, tenant?: bool, permission?: string|null} $options */
	public function put(string $path, callable $handler, array $options = []): self
	{
		return $this->any(['PUT'], $path, $handler, $options);
	}

	/** @param array{auth?: bool, tenant?: bool, permission?: string|null} $options */
	public function patch(string $path, callable $handler, array $options = []): self
	{
		return $this->any(['PATCH'], $path, $handler, $options);
	}

	/** @param array{auth?: bool, tenant?: bool, permission?: string|null} $options */
	public function delete(string $path, callable $handler, array $options = []): self
	{
		return $this->any(['DELETE'], $path, $handler, $options);
	}

	/** @param array<int, string> $methods @param array{auth?: bool, tenant?: bool, permission?: string|null} $options */
	public function any(array $methods, string $path, callable $handler, array $options = []): self
	{
		$normalizedPath = $this->normalizePath($path);
		$normalizedMethods = array_values(array_unique(array_map(
			static fn (string $method): string => strtoupper($method),
			$methods,
		)));

		$permission = $options['permission'] ?? null;
		if ($permission !== null && !is_string($permission)) {
			throw new \InvalidArgumentException('Permission metadata must be a string or null.');
		}
		if (is_string($permission) && trim($permission) === '') {
			throw new \InvalidArgumentException('Permission metadata cannot be empty.');
		}
		$requiresAuth = (bool) ($options['auth'] ?? false) || $permission !== null;
		$requiresTenant = (bool) ($options['tenant'] ?? false);
		$requiresAuth = $requiresAuth || $requiresTenant;

		if ($normalizedMethods === []) {
			throw new \InvalidArgumentException('At least one HTTP method is required.');
		}

		[$regex, $parameters] = $this->compilePath($normalizedPath);

		foreach ($this->routes as $route) {
			if ($route['path'] === $normalizedPath && array_intersect($route['methods'], $normalizedMethods) !== []) {
				throw new \LogicException("A route is already registered for {$normalizedPath}.");
			}
		}

		$this->routes[] = [
			'methods' => $normalizedMethods,
			'path' => $normalizedPath,
			'regex' => $regex,
			'parameters' => $parameters,
			'handler' => $handler,
			'auth' => $requiresAuth,
			'tenant' => $requiresTenant,
			'permission' => $permission,
		];

		return $this;
	}

	public function dispatch(Request $request, ?callable $authentication = null, ?callable $authorization = null, ?callable $tenant = null): Response
	{
		$path = $this->normalizePath($request->path());
		$method = strtoupper($request->method());
		$allowedMethods = [];

		foreach ($this->routes as $route) {
			$matches = [];
			if (preg_match($route['regex'], $path, $matches) !== 1) {
				continue;
			}

			$allowedMethods = array_values(array_unique(array_merge($allowedMethods, $route['methods'])));
			if (!in_array($method, $route['methods'], true)) {
				continue;
			}

			$parameters = [];
			foreach ($route['parameters'] as $parameter) {
				$parameters[$parameter] = $matches[$parameter];
			}

			$handler = function (Request $dispatchedRequest) use ($route, $parameters): Response {
				return ($route['handler'])($dispatchedRequest, $parameters);
			};

			if ($route['auth']) {
				if ($authentication === null) {
					throw new \LogicException('An authentication middleware is required for protected routes.');
				}

				$afterAuthentication = $handler;
				if ($route['permission'] !== null) {
					if ($authorization === null) {
						throw new \LogicException('An authorization middleware is required for permission-protected routes.');
					}

					$afterAuthentication = static function (Request $authorizedRequest) use ($authorization, $handler, $route): Response {
						return $authorization($authorizedRequest, $handler, $route['permission']);
					};
				}

				if ($route['tenant']) {
					if ($tenant === null) {
						throw new \LogicException('A tenant middleware is required for tenant-scoped routes.');
					}

					$afterAuthentication = static function (Request $tenantRequest) use ($tenant, $afterAuthentication): Response {
						return $tenant($tenantRequest, $afterAuthentication);
					};
				}

				return $authentication($request, $afterAuthentication);
			}

			return $handler($request);
		}

		if ($allowedMethods !== []) {
			return Response::json(['success' => false, 'message' => 'Method Not Allowed'], 405)
				->withHeader('Allow', implode(', ', $allowedMethods));
		}

		return Response::json(['success' => false, 'message' => 'Not Found'], 404);
	}

	/** @return array<int, array{methods: array<int, string>, path: string, auth: bool, tenant: bool, permission: ?string}> */
	public function securityMetadata(): array
	{
		return array_map(
			static fn (array $route): array => [
				'methods' => $route['methods'],
				'path' => $route['path'],
				'auth' => $route['auth'],
				'tenant' => $route['tenant'],
				'permission' => $route['permission'],
			],
			$this->routes,
		);
	}

	private function normalizePath(string $path): string
	{
		$path = parse_url($path, PHP_URL_PATH);
		if (!is_string($path) || $path === '') {
			return '/';
		}

		$normalized = '/' . ltrim($path, '/');

		return $normalized === '/' ? '/' : rtrim($normalized, '/');
	}

	/** @return array{0: string, 1: array<int, string>} */
	private function compilePath(string $path): array
	{
		$segments = $path === '/' ? [] : explode('/', trim($path, '/'));
		$parameters = [];
		$pattern = '';

		foreach ($segments as $segment) {
			if (preg_match('/^\{([A-Za-z][A-Za-z0-9_]*)\}$/', $segment, $match) === 1) {
				$name = $match[1];
				if (in_array($name, $parameters, true)) {
					throw new \InvalidArgumentException("Duplicate route parameter: {$name}");
				}
				$parameters[] = $name;
				$pattern .= '/(?P<' . $name . '>[^/]+)';
				continue;
			}

			$pattern .= '/' . preg_quote($segment, '#');
		}

		return ['#^' . ($pattern === '' ? '/' : $pattern) . '/?$#', $parameters];
	}
}
