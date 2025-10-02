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
        $routeName = $request->getAttribute('route_name');

        if (!$routeName) {
            $this->logger->error('No route name found in request attributes. Path: ' . $request->getUri()->getPath());
            throw new \RuntimeException('Route name not set on request. League Route integration issue.');
        }

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
            return new RedirectResponse('/user');
        }
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
