<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceProviders;

use App\ServiceProviders\LoggerServiceProvider;
use League\Container\Container;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class LoggerServiceProviderTest extends TestCase
{
    private LoggerServiceProvider $provider;
    private MockObject $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(Container::class);

        $this->provider = new LoggerServiceProvider();
        $this->provider->setContainer($this->container);
    }

    public function testProvidesLogger(): void
    {
        $this->assertTrue($this->provider->provides(Logger::class));
    }

    public function testDoesNotProvideOtherServices(): void
    {
        $this->assertFalse($this->provider->provides('SomeOtherService'));
        $this->assertFalse($this->provider->provides('PDO'));
    }

    public function testRegisterAddsLoggerService(): void
    {
        $this->container->expects($this->once())
            ->method('add')
            ->with(Logger::class, $this->isType('callable'));

        $this->provider->register();
    }
}
