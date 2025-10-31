<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Application;
use App\Controllers\MedlemController;
use League\Container\Container;
use Psr\Http\Message\ServerRequestInterface;

class MedlemControllerIntegrationTest extends TestCase
{
    private Application $app;
    private Container $container;

    protected function setUp(): void
    {
        $this->app = new Application();
        $this->container = $this->app->getContainer();
    }

    public function testContainerCanInstantiateMedlemController(): void
    {
        $controller = $this->container->get(MedlemController::class);

        $this->assertInstanceOf(MedlemController::class, $controller);
    }

    public function testMedlemServiceCanBeResolved(): void
    {
        $service = $this->container->get(\App\Services\MedlemService::class);

        $this->assertInstanceOf(\App\Services\MedlemService::class, $service);
    }

    public function testAllMedlemRelatedServicesCanBeResolved(): void
    {
        $services = [
            \App\Services\MedlemService::class,
            \App\Services\MedlemDataValidatorService::class,
            \App\Services\MailAliasService::class,
            \App\Models\MedlemRepository::class,
            \App\Models\BetalningRepository::class,
            \App\Models\RollRepository::class
        ];

        foreach ($services as $serviceClass) {
            $service = $this->container->get($serviceClass);
            $this->assertInstanceOf($serviceClass, $service);
        }
    }

    public function testMedlemControllerDependencyChain(): void
    {
        // This tests the full dependency chain: Controller -> Service -> Repository -> Model
        $controller = $this->container->get(MedlemController::class);

        // Use reflection to verify the controller has the expected dependencies
        $reflection = new \ReflectionClass($controller);
        $constructor = $reflection->getConstructor();
        $params = $constructor->getParameters();

        // MedlemController should have 2 parameters: MedlemService and View
        $this->assertCount(2, $params);
        $this->assertEquals(\App\Services\MedlemService::class, $params[0]->getType()->getName());
        $this->assertEquals(\App\Utils\View::class, $params[1]->getType()->getName());
    }
}
