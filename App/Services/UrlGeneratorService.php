<?php

declare(strict_types=1);

namespace App\Services;

use App\Application;

class UrlGeneratorService
{
    public function __construct(private Application $app)
    {
    }

    public function createUrl(string $routeName, array $params = []): string
    {
        $route = $this->app->getRouter()->getNamedRoute($routeName);
        return $route->getPath($params);
    }
}