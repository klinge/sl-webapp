<?php

declare(strict_types=1);

namespace Tests\Unit\ServiceProviders;

use App\ServiceProviders\ModelServiceProvider;
use App\Models\Betalning;
use App\Models\BetalningRepository;
use App\Models\Medlem;
use App\Models\MedlemRepository;
use App\Models\Roll;
use App\Models\Segling;
use App\Models\SeglingRepository;
use League\Container\Container;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class ModelServiceProviderTest extends TestCase
{
    private ModelServiceProvider $provider;
    private MockObject $container;

    protected function setUp(): void
    {
        $this->container = $this->createMock(Container::class);

        $this->provider = new ModelServiceProvider();
        $this->provider->setContainer($this->container);
    }

    public function testProvidesAllModelServices(): void
    {
        $services = [
            Betalning::class,
            BetalningRepository::class,
            Medlem::class,
            MedlemRepository::class,
            Roll::class,
            Segling::class,
            SeglingRepository::class
        ];

        foreach ($services as $service) {
            $this->assertTrue($this->provider->provides($service));
        }
    }

    public function testDoesNotProvideOtherServices(): void
    {
        $this->assertFalse($this->provider->provides('SomeOtherService'));
        $this->assertFalse($this->provider->provides('PDO'));
    }

    public function testRegisterAddsAllServices(): void
    {
        $this->container->expects($this->exactly(7))
            ->method('add');

        $this->provider->register();
    }
}
