<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Application;
use App\Traits\JsonResponder;
use Psr\Http\Message\ServerRequestInterface;
use Monolog\Logger;
use AltoRouter;

class BaseMiddleware
{
    //Add the JsonResponder trait
    use JsonResponder;

    protected ServerRequestInterface $request;
    protected AltoRouter $router;
    protected Logger $logger;

    public function __construct(ServerRequestInterface $request, AltoRouter $router, Logger $logger)
    {
        $this->request = $request;
        $this->router = $router;
        $this->logger = $logger;
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
