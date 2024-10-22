<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Utils\Session;
use App\Config\RouteConfig;

class AuthorizationMiddleware extends BaseMiddleware implements MiddlewareInterface
{
    /**
     * Middleware that handles authorization for incoming requests.
     *
     * This method checks if the current user has the necessary permissions to access
     * the requested route. It performs the following checks:
     * 1. If the user is an admin
     * 2. If the route doesn't require login
     * 3. If it's a user-specific route
     *
     * If none of these conditions are met, it handles the unauthorized access by either:
     * - Sending a JSON response for AJAX requests
     * - Setting a flash message and redirecting for regular requests
     *
     * @return void
     */
    public function handle(): void
    {
        $match = $this->app->getRouter()->match();
        if ($match) {
            $routeName = $match['name'];
            // Deny access if NOT: user is admin OR route does not require login OR it's a user route
            if (!(Session::get('is_admin') || in_array($routeName, RouteConfig::$noLoginRequiredRoutes) || $this->isUserRoute($routeName))) {
                if ($this->isAjaxRequest()) {
                    $this->jsonResponse(['success' => false, 'message' => 'Du måste vara administratör för att få komma åt denna resurs.', 401]);
                } else {
                    Session::setFlashMessage('error', 'Du måste vara administratör för att se denna sida.');
                    header('Location: ' . $this->app->getRouter()->generate('user-home'));
                }
                //Log the exception
                $this->app->getLogger()->info('Request to an admin page, user is not admin. URI: ' . $this->request->getUri()->__toString() .
                    ', Remote IP: ' . $this->request->getServerParams()['REMOTE_ADDR'] .
                    ', User ID: ' . Session::get('user_id'));
                $this->doExit();
            }
        }
    }

    protected function isUserRoute($routeName): bool
    {
        return strpos($routeName, 'user-') !== false;
    }
}
