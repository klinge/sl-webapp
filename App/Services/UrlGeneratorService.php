<?php

declare(strict_types=1);

namespace App\Services;

use App\Application;

class UrlGeneratorService
{
    /**
     * Initialize UrlGeneratorService with application instance.
     *
     * @param Application $app Application instance for router access
     */
    public function __construct(private Application $app)
    {
    }

    /**
     * Generate URL for a named route with optional parameters.
     *
     * @param string $routeName The name of the route to generate URL for
     * @param array<string, mixed> $params Optional route parameters
     * @return string The generated URL path
     */
    public function createUrl(string $routeName, array $params = []): string
    {
        $route = $this->app->getRouter()->getNamedRoute($routeName);
        return $route->getPath($params);
    }
}
