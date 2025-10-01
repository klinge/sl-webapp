<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Config\RouteConfig;
use App\Utils\Session;
use App\Middleware\Contracts\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use League\Route\Route;

class AuthenticationMiddleware extends BaseMiddleware
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $routeName = $request->getAttribute('route_name');

        if (!$routeName) {
            $this->logger->error('No route name found in request attributes. Path: ' . $request->getUri()->getPath());
            throw new \RuntimeException('Route name not set on request. League Route integration issue.');
        }

        // Handle AJAX requests - user must be logged in
        if ($this->isAjaxRequest($request)) {
            if (!Session::get('user_id')) {
                $this->logger->warning('Ajax request, user not logged in. URI: ' .
                    $request->getUri()->__toString() .
                    ', Remote IP: ' .
                    $request->getServerParams()['REMOTE_ADDR']);

                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'Du måste vara inloggad för åtkomst till denna tjänst.'
                ], 401);
            }
        } elseif (!in_array($routeName, RouteConfig::$noLoginRequiredRoutes) && !Session::get('user_id')) {
            $this->logger->info('Request to protected page, user not logged in. URI: ' .
                $request->getUri()->__toString() .
                ', Remote IP: ' .
                $request->getServerParams()['REMOTE_ADDR']);

            // Store the current URL for redirect after login
            Session::set('redirect_url', $routeName);
            Session::setFlashMessage('error', 'Du måste vara inloggad för att se denna sida.');

            return new RedirectResponse('/login', 401);
        }

        // Continue to next middleware or handler
        return $handler->handle($request);
    }
}
