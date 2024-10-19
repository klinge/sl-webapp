<?php

namespace Tests\Unit\Middleware;

class CsrfMiddlewareFake extends \App\Middleware\CsrfMiddleware
{
    public $exitCalled = false;

    protected function exit(): void
    {
        $this->exitCalled = true;
    }
}
