<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Utils\Session;

class CsrfMiddleware extends BaseMiddleware implements MiddlewareInterface
{
    public function handle(): void
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        if ($this->request['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['csrf_token'] ?? '';
            if (!hash_equals($_SESSION['csrf_token'], $token)) {
                $this->app->getLogger()->warning('CSRF token mismatch');
                //TODO set a flash message and redirect to an error page..
                exit;
            }
        }
        return;
    }
}
