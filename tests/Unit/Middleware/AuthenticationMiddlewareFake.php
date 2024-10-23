<?php

namespace Tests\Unit\Middleware;

class AuthenticationMiddlewareFake extends \App\Middleware\AuthenticationMiddleware
{
    public $exitCalled = false;

    //Override doExit to make sure the test suite isn't aborted
    protected function doExit(): void
    {
        $this->exitCalled = true;
    }
}
