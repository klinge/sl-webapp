<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Utils\Session;
use App\Middleware\Contracts\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\RedirectResponse;

/**
 * Middleware class for handling CSRF token validation.
 */
class CsrfMiddleware extends BaseMiddleware
{
    /**
     * An array of paths that should be excluded from CSRF protection.
     *
     * @var array<int, string>
     */
    private array $excludedPaths = ['/webhooks', '/home'];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!isset($_SESSION['csrf_token'])) {
            Session::set('csrf_token', bin2hex(random_bytes(32)));
        }

        // Check if the current path is in the excluded list
        $currentPath = $request->getUri()->getPath();
        foreach ($this->excludedPaths as $excludedPath) {
            if (strpos($currentPath, $excludedPath) === 0) {
                $this->logger->debug('Call to a path that excludes csrf protection: ' . $currentPath);
                return $handler->handle($request);
            }
        }

        if ($request->getMethod() === 'POST') {
            // Check if we got a json or a form request
            $contentType = $request->getHeaderLine('Content-Type');
            if (str_contains($contentType, 'application/json')) {
                $body = json_decode($request->getBody()->getContents(), true);
                $token = $body['csrf_token'] ?? '';
            } else {
                $token = $request->getParsedBody()['csrf_token'] ?? '';
            }
            $this->logger->debug('In csrf middleware. Token in POST was: ' . $token);

            if (!hash_equals(Session::get('csrf_token'), $token)) {
                $this->logger->warning('CSRF token mismatch. Uri was: ' . $currentPath
                    . ' Token in POST was: ' . $token
                    . ' Called by: ' . $request->getServerParams()['REMOTE_ADDR']);

                // Return different responses depending on if it was an ajax request or not
                if ($this->isAjaxRequest($request)) {
                    return $this->jsonResponse(['status' => 'fail', 'message' => 'Error validating csrf token'], 403);
                } else {
                    Session::setFlashMessage('error', 'Kunde inte validera CSFR-token..');
                    return new RedirectResponse('/error');
                }
            }
        }

        // Continue to next middleware or handler
        return $handler->handle($request);
    }
}
