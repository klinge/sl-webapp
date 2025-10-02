<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Utils\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Monolog\Logger;

class RequireAdminMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Logger $logger
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!Session::get('user_id')) {
            $path = $request->getUri()->getPath();
            $this->logger->warning('Unauthenticated access attempt to admin page', [
                'path' => $path,
                'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown'
            ]);

            // Store the current URL path for redirect after login
            if ($path && $path !== '/') {
                Session::set('redirect_url', $path);
            }

            Session::setFlashMessage('error', 'Du måste vara inloggad för att se denna sida.');
            return new RedirectResponse('/login');
        }

        if (!Session::get('is_admin')) {
            $this->logger->warning('Non-admin user attempted to access admin page', [
                'user_id' => Session::get('user_id'),
                'path' => $request->getUri()->getPath(),
                'ip' => $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown'
            ]);
            return new RedirectResponse('/user');
        }

        return $handler->handle($request);
    }
}
