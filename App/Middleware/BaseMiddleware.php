<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Application;
use Psr\Http\Message\ServerRequestInterface;

class BaseMiddleware
{
    protected Application $app;
    protected ServerRequestInterface $request;

    public function __construct(Application $app, ServerRequestInterface $request)
    {
        $this->app = $app;
        $this->request = $request;
    }

    protected function sendJsonResponse(array $data, int $statusCode = 200): int
    {
        header('Content-Type: application/json');
        http_response_code($statusCode);
        echo json_encode($data);
        return $statusCode;
    }

    protected function isAjaxRequest(): bool
    {
        $isAjax = $this->request->hasHeader('X-Requested-With') && strtolower($this->request->getHeader('X-Requested-With')[0]) === 'xmlhttprequest';
        return $isAjax;
    }

    protected function doExit(): void
    {
        exit;
    }
}
