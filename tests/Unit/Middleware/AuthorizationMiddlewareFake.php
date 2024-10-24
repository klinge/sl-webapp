<?php

namespace Tests\Unit\Middleware;

class AuthorizationMiddlewareFake extends \App\Middleware\AuthorizationMiddleware
{
    public $exitCalled = false;

    //Override doExit to make sure the test suite isn't aborted
    protected function doExit(): void
    {
        $this->exitCalled = true;
    }
}
