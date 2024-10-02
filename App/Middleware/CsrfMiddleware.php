<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Utils\Session;

class CsrfMiddleware extends BaseMiddleware implements MiddlewareInterface
{
    private $excludedPaths = ['/webhooks', '/home'];

    public function handle(): void
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Check if the current path is in the excluded list
        $currentPath = parse_url($this->request['REQUEST_URI'], PHP_URL_PATH);
        foreach ($this->excludedPaths as $excludedPath) {
            if (strpos($currentPath, $excludedPath) === 0) {
                $this->app->getLogger()->debug('Call to a path that excludes csrf protection: ' . $currentPath);
                return;
            }
        }

        if ($this->request['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'], $token)) {
                $this->app->getLogger()->warning('CSRF token mismatch. Uri was: ' . $currentPath . "Called by; " . $this->request['REMOTE_ADDR']);
                //Set different responses depending on if it was an ajax request or not
                if ($this->isAjaxRequest()) {
                    $this->sendJsonResponse(['status' => 'error', 'message' => 'Error validating csrf token'], 401);
                } else {
                    Session::setFlashMessage('error', 'Kunde inte validera CSFR-token..');
                    header('Location: ' . $this->app->getRouter()->generate('tech-error'));
                }
                exit;
            }
        }
    }
}
