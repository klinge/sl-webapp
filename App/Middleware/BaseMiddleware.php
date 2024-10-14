<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Application;
use Laminas\Diactoros\ServerRequest;

class BaseMiddleware
{
    protected Application $app;
    protected ServerRequest $request;

    public function __construct(Application $app, ServerRequest $request)
    {
        $this->app = $app;
        $this->request = $request;
    }

    protected function sendJsonResponse(array $data, int $statusCode = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        exit;
    }

    protected function isAjaxRequest(): bool
    {;
        if (
            $this->request->hasHeader('HTTP_X_REQUESTED_WITH')
            && strtolower($this->request->getHeader('HTTP_X_REQUESTED_WITH')[0]) === 'xmlhttprequest'
        ) {
            return true;
        } else {
            return false;
        }
    }
}
