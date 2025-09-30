<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Utils\Session;
use App\Config\RouteConfig;
use App\Middleware\Contracts\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\RedirectResponse;

class AuthorizationMiddleware extends BaseMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $match = $this->router->match();
        if ($match) {
            $routeName = $match['name'];
            // Admins can access everything
            if (Session::isAdmin()) {
                return $handler->handle($request);
            }
            // Anyone can access user routes and routes that don't require login
            if ($this->isUserRoute($routeName) || $this->isOpenRoute($routeName)) {
                return $handler->handle($request);
            }
            // If we get here the user is not admin and it's a protected route
            $this->logger->info('Request to an admin page, user is not admin. URI: ' . $request->getUri()->__toString() .
                ', Remote IP: ' . $request->getServerParams()['REMOTE_ADDR'] .
                ', User ID: ' . Session::get('user_id'));

            if ($this->isAjaxRequest($request)) {
                return $this->jsonResponse(['success' => false, 'message' => 'Du måste vara administratör för att få komma åt denna resurs.'], 401);
            } else {
                Session::setFlashMessage('error', 'Du måste vara administratör för att se denna sida.');
                return new RedirectResponse($this->router->generate('user-home'));
            }
        }

        // If no route was found, continue to handler (404 errors are handled elsewhere)
        return $handler->handle($request);
    }

    protected function isOpenRoute(string $routeName): bool
    {
        //The no-login routes are defined in RouteConfig
        $result = in_array($routeName, RouteConfig::$noLoginRequiredRoutes);
        $this->logger->debug('>isOpenRoute: ' . $routeName . ':' . (string) $result);
        return $result;
    }

    protected function isUserRoute(string $routeName): bool
    {
        $result = strpos($routeName, 'user-') !== false;
        $this->logger->debug('>isUserRoute: ' . $routeName . ':' . (string) $result);
        return $result;
    }
}
