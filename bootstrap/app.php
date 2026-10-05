<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Sentinel\Core\Application;

return Application::create(dirname(__DIR__))->bootstrap();
