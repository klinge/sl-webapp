<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Application;

class BaseMiddleware
{
    protected $app;
    protected $request;

    public function __construct(Application $app, array $request)
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
    {
        if (isset($this->request['HTTP_X_REQUESTED_WITH']) && strtolower($this->request['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            return true;
        } else {
            return false;
        }
    }
}
