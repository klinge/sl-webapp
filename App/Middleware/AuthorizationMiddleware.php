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
     * 3. If it's a route that is (non-admin) user-accessible
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
            //admins can access everything so just return..
            if (Session::isAdmin()) {
                return;
            }
            //Anyone can access user routes and routes that don't require login so just return;
            if ($this->isUserRoute($routeName) || $this->isOpenRoute($routeName)) {
                return;
            }
            // If we get here the user is not admin and it's a protected route
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
        } else {
            //If no route was found just exit, 404 errors are handled elsewhere
            $this->doExit();
        }
    }

    protected function isOpenRoute(string $routeName): bool
    {
        //The no-login routes are defined in RouteConfig
        $result = in_array($routeName, RouteConfig::$noLoginRequiredRoutes);
        $this->app->getLogger()->debug('>isOpenRoute: ' . $routeName . ':' . (string) $result);
        return $result;
    }

    protected function isUserRoute(string $routeName): bool
    {
        $result = strpos($routeName, 'user-') !== false;
        $this->app->getLogger()->debug('>isUserRoute: ' . $routeName . ':' . (string) $result);
        return $result;
    }
}
