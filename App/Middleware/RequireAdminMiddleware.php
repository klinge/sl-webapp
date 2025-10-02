<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Utils\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\RedirectResponse;

class RequireAdminMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!Session::get('user_id')) {
            $routeName = $request->getAttribute('route_name');
            if ($routeName) {
                Session::set('redirect_url', $routeName);
            }
            Session::setFlashMessage('error', 'Du måste vara inloggad för att se denna sida.');
            return new RedirectResponse('/login');
        }

        if (!Session::get('is_admin')) {
            return new RedirectResponse('/user');
        }

        return $handler->handle($request);
    }
}
