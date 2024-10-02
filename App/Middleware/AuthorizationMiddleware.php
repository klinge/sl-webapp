<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Utils\Session;
use App\Config\RouteConfig;

class AuthorizationMiddleware extends BaseMiddleware implements MiddlewareInterface
{
    public function handle(): void
    {
        $match = $this->app->getRouter()->match();
        if ($match) {
            $routeName = $match['name'];
            // Check 1. that route exists, 2. that it's not in routes that not require login
            // 3. that it's not a user route and 4. that user is not an admin
            // If all are true redirect to a page saying user can't access the required page
            if (!in_array($routeName, RouteConfig::$noLoginRequiredRoutes) && !$this->isUserRoute($routeName) && !Session::get('is_admin')) {
                if ($this->isAjaxRequest()) {
                    $this->sendJsonResponse(['success' => false, 'message' => 'Du måste vara administratör för att få komma åt denna resurs.']);
                } else {
                    Session::setFlashMessage('error', 'Du måste vara administratör för att se denna sida.');
                    header('Location: ' . $this->app->getRouter()->generate('user-home'));
                }
            }
        }
    }

    private function isUserRoute($routeName): bool
    {
        return strpos($routeName, 'user-') !== false;
    }
}
