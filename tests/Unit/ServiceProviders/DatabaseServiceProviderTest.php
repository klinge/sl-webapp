<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceProviders;

use App\ServiceProviders\DatabaseServiceProvider;
use App\Utils\Database;
use League\Container\Container;
use Monolog\Logger;
use PDO;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class DatabaseServiceProviderTest extends TestCase
{
    private DatabaseServiceProvider $provider;
    private MockObject $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(Container::class);

        $this->provider = new DatabaseServiceProvider();
        $this->provider->setContainer($this->container);
    }

    public function testProvidesDatabase(): void
    {
        $this->assertTrue($this->provider->provides(Database::class));
    }

    public function testProvidesPDO(): void
    {
        $this->assertTrue($this->provider->provides(PDO::class));
    }

    public function testDoesNotProvideOtherServices(): void
    {
        $this->assertFalse($this->provider->provides('SomeOtherService'));
        $this->assertFalse($this->provider->provides(Logger::class));
    }

    public function testRegisterAddsServices(): void
    {
        $this->container->expects($this->exactly(2))
            ->method('add')
            ->with($this->logicalOr(
                $this->equalTo(Database::class),
                $this->equalTo(PDO::class)
            ), $this->isType('callable'));

        $this->provider->register();
    }
}
