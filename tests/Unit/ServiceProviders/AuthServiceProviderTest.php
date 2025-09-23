<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceProviders;

use App\ServiceProviders\AuthServiceProvider;
use App\Services\Auth\UserAuthenticationService;
use App\Services\Auth\PasswordService;
use League\Container\Container;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class AuthServiceProviderTest extends TestCase
{
    private AuthServiceProvider $provider;
    private MockObject $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(Container::class);

        $this->provider = new AuthServiceProvider();
        $this->provider->setContainer($this->container);
    }

    public function testProvidesUserAuthenticationService(): void
    {
        $this->assertTrue($this->provider->provides(UserAuthenticationService::class));
    }

    public function testProvidesPasswordService(): void
    {
        $this->assertTrue($this->provider->provides(PasswordService::class));
    }

    public function testDoesNotProvideOtherServices(): void
    {
        $this->assertFalse($this->provider->provides('SomeOtherService'));
        $this->assertFalse($this->provider->provides('PDO'));
    }

    public function testRegisterAddsServices(): void
    {
        $this->container->expects($this->exactly(2))
            ->method('add')
            ->with($this->logicalOr(
                $this->equalTo(PasswordService::class),
                $this->equalTo(UserAuthenticationService::class)
            ));

        $this->provider->register();
    }
}
