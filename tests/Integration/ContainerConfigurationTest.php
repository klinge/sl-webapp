<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use App\Application;
use League\Container\Container;

class ContainerConfigurationTest extends TestCase
{
    private Application $app;
    private Container $container;

    protected function setUp(): void
    {
        $this->app = new Application();
        $this->container = $this->app->getContainer();
    }

    public function testAllRefactoredControllersFollowSamePattern(): void
    {
        $controllers = [
            \App\Controllers\MedlemController::class,
            \App\Controllers\BetalningController::class,
            \App\Controllers\SeglingController::class,
            \App\Controllers\RollController::class
        ];

        foreach ($controllers as $controllerClass) {
            $controller = $this->container->get($controllerClass);
            $this->assertInstanceOf($controllerClass, $controller);

            // Verify each controller has exactly 3 dependencies (Service + View + UrlGeneratorService)
            $reflection = new \ReflectionClass($controller);
            $constructor = $reflection->getConstructor();
            $params = $constructor->getParameters();

            $this->assertCount(3, $params, "Controller $controllerClass should have exactly 3 dependencies");
            $this->assertEquals(\App\Utils\View::class, $params[1]->getType()->getName(), "Second dependency should be View");
            $this->assertEquals(\App\Services\UrlGeneratorService::class, $params[2]->getType()->getName(), "Third dependency should be UrlGeneratorService");
        }
    }

    public function testAllServicesCanBeResolved(): void
    {
        $services = [
            \App\Services\MedlemService::class,
            \App\Services\BetalningService::class,
            \App\Services\SeglingService::class,
            \App\Services\RollService::class,
            \App\Services\MedlemDataValidatorService::class,
            \App\Services\MailAliasService::class
        ];

        foreach ($services as $serviceClass) {
            $service = $this->container->get($serviceClass);
            $this->assertInstanceOf($serviceClass, $service);
        }
    }

    public function testAllRepositoriesCanBeResolved(): void
    {
        $repositories = [
            \App\Models\MedlemRepository::class,
            \App\Models\BetalningRepository::class,
            \App\Models\SeglingRepository::class,
            \App\Models\RollRepository::class
        ];

        foreach ($repositories as $repositoryClass) {
            $repository = $this->container->get($repositoryClass);
            $this->assertInstanceOf($repositoryClass, $repository);
        }
    }
}
