<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Application;
use App\Traits\JsonResponder;
use Psr\Http\Message\ServerRequestInterface;

class BaseMiddleware
{
    //Add the JsonResponder trait
    use JsonResponder;

    protected Application $app;
    protected ServerRequestInterface $request;

    public function __construct(Application $app, ServerRequestInterface $request)
    {
        $this->app = $app;
        $this->request = $request;
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
