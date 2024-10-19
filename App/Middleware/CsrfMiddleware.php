<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Utils\Session;

/**
 * Middleware class for handling CSRF token validation.
 */
class CsrfMiddleware extends BaseMiddleware implements MiddlewareInterface
{
    /**
     * An array of paths that should be excluded from CSRF protection.
     *
     * @var string[]
     */
    private $excludedPaths = ['/webhooks', '/home'];

    /**
     * Handles the CSRF token validation process.
     *
     * Generates a new CSRF token if it doesn't exist, checks if the current path
     * is excluded from CSRF protection, and validates the CSRF token for POST requests.
     * If the token is invalid, it sends an appropriate response based on the request type.
     *
     * @return void
     */
    public function handle(): void
    {
        if (!isset($_SESSION['csrf_token'])) {
            Session::set('csrf_token', bin2hex(random_bytes(32)));
        }

        // Check if the current path is in the excluded list
        $currentPath =  $this->request->getUri()->getPath();
        foreach ($this->excludedPaths as $excludedPath) {
            if (strpos($currentPath, $excludedPath) === 0) {
                $this->app->getLogger()->debug('Call to a path that excludes csrf protection: ' . $currentPath);
                return;
            }
        }

        if ($this->request->getMethod() === 'POST') {
            $token = $this->request->getParsedBody()['csrf_token'] ?? '';
            $this->app->getLogger()->debug('In csrf middleware. Token in POST was: ' . $token);

            if (!hash_equals(Session::get('csrf_token'), $token)) {
                $this->app->getLogger()->warning('CSRF token mismatch. Uri was: ' . $currentPath
                    . ' Token in POST was: ' . $token
                    . ' Called by: ' . $this->request->getServerParams()['REMOTE_ADDR']);
                //Set different responses depending on if it was an ajax request or not
                if ($this->isAjaxRequest()) {
                    $this->sendJsonResponse(['status' => 'error', 'message' => 'Error validating csrf token'], 401);
                } else {
                    Session::setFlashMessage('error', 'Kunde inte validera CSFR-token..');
                    header('Location: ' . $this->app->getRouter()->generate('tech-error'));
                }
                $this->exit();
            }
        }
    }

    //Putting the call to exit in a function that can be overridden in tests
    protected function exit(): void
    {
        exit;
    }
}
