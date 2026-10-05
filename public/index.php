<?php

declare(strict_types=1);

use Sentinel\Core\Request;
use Sentinel\Core\HttpErrorResponder;

try {
	/** @var \Sentinel\Core\Application $application */
	$application = require dirname(__DIR__) . '/bootstrap/app.php';

	$response = $application->handle(Request::fromGlobals());
} catch (\Throwable $exception) {
	// The autoloader is registered by bootstrap before application creation.
	// If bootstrap fails before that point, the web server's own safe 500 page
	// remains the final fallback.
	$response = HttpErrorResponder::respond($exception);
}

$response->send();
