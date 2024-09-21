<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap the application
$app = new App\Application();
// Then run any middleware
$app->runMiddleware();
// Finally run the main application
$app->run();
exit;
