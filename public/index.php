<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

// Bootstrap the application
$app = new App\Application();
// Run the application through PSR-15 middleware stack
$app->run();
exit;
