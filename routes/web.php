<?php

declare(strict_types=1);

use Sentinel\Core\Response;
use Sentinel\Core\Request;
use Sentinel\Core\Container;
use Sentinel\Core\HttpException;
use Sentinel\Core\Router;

return static function (Router $router, Container $container): void {
	$publicPath = dirname(__DIR__) . '/public';
	$page = static function (string $filename) use ($publicPath): Response {
		$html = file_get_contents($publicPath . '/' . $filename);
		if ($html === false) {
			return Response::raw('Page unavailable', 503, ['Content-Type' => 'text/plain; charset=utf-8']);
		}

		return Response::raw($html, 200, [
			'Content-Type' => 'text/html; charset=utf-8',
			'Cache-Control' => 'public, max-age=300',
		]);
	};

	$router->get('/', static fn (): Response => $page('home.html'));
	$router->get('/pricing', static fn (): Response => $page('pricing.html'));
	$router->get('/about', static fn (): Response => $page('about.html'));
	$router->get('/security', static fn (): Response => $page('security.html'));
	$router->get('/terms', static fn (): Response => $page('terms.html'));
	$router->get('/privacy', static fn (): Response => $page('privacy.html'));

	$localSupportOnly = static function (Request $request) use ($container): void {
		$enabled = (bool) $container->make('config')->get('support.diagnostics_enabled', false);
		$remoteAddress = (string) $request->server('REMOTE_ADDR', '');
		if (!$enabled || !in_array($remoteAddress, ['127.0.0.1', '::1'], true)) {
			throw HttpException::notFound('Page not found.');
		}
	};

	$router->get('/support', static function (Request $request) use ($page, $localSupportOnly): Response {
		$localSupportOnly($request);
		$response = $page('support.html');

		return $response->withHeader('Cache-Control', 'no-store');
	});
	$router->get('/support/diagnostics', static function (Request $request) use ($container, $localSupportOnly): Response {
		$localSupportOnly($request);
		$report = $container->make('support_diagnostics_service')->report();

		return Response::json($report, $report['status'] === 'ready' ? 200 : 503)
			->withHeader('Cache-Control', 'no-store');
	});
};
