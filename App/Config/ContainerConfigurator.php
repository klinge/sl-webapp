<?php

declare(strict_types=1);

namespace App\Config;

use League\Container\Container;
use Psr\Http\Message\ServerRequestInterface;
use AltoRouter;
use App\Application;
use App\ServiceProviders\DatabaseServiceProvider;
use App\ServiceProviders\LoggerServiceProvider;
use App\ServiceProviders\AuthServiceProvider;
use App\ServiceProviders\ModelServiceProvider;
use App\Services\Github\GitHubService;
use App\Services\Github\GitRepositoryService;
use App\Services\Github\DeploymentService;
use App\Utils\Session;
use App\Utils\View;
use Monolog\Logger;

class ContainerConfigurator
{
    public static function registerServices(Container $container, Application $app): void
    {
        // Core services registration
        $container->add(Application::class, $app);
        $container->add(ServerRequestInterface::class, $app->getPsrRequest());
        $container->add(AltoRouter::class, $app->getRouter());
        $container->add('config', $app->getConfig(null));
        $container->add(Session::class);
        $container->add(\App\Utils\View::class)
            ->addArgument(Application::class);
        $container->add(\App\Utils\Email::class)
            ->addArgument(Application::class)
            ->addArgument(Logger::class);

        // Webhook services
        $container->add(GitHubService::class)
            ->addArgument($app->getConfig('GITHUB_WEBHOOK_SECRET'));
        $container->add(GitRepositoryService::class)
            ->addArgument($app->getConfig('REPO_BASE_DIRECTORY'))
            ->addArgument(Logger::class);
        $container->add(DeploymentService::class)
            ->addArgument($app->getConfig('TRIGGER_FILE_DIRECTORY'))
            ->addArgument(Logger::class);

        // Add service providers
        $container->addServiceProvider(new DatabaseServiceProvider());
        $container->addServiceProvider(new LoggerServiceProvider());
        $container->addServiceProvider(new AuthServiceProvider());
        $container->addServiceProvider(new ModelServiceProvider());


        // Autoregister all controllers in the container
        self::registerControllers($container, $app);

        // Add other services here as needed
    }


    /**
     * Automatically registers all controller classes from the App/Controllers directory into the DI container.
     *
     * This method scans the controllers directory recursively and registers each controller
     * with its required dependencies. All controllers receive base dependencies (Application,
     * ServerRequestInterface, Logger) plus any additional type-hinted constructor parameters.
     *
     * @param Container $container The DI container instance
     * @param Application $app The main application instance
     * @return void
     *
     * @throws \ReflectionException When class reflection fails
     */
    private static function registerControllers(Container $container, Application $app): void
    {
        $controllersPath = $app->getRootDir() . '/App/Controllers';
        $controllerNamespace = 'App\\Controllers\\';

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($controllersPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        $files = new \RegexIterator($iterator, '/^.+\.php$/i', \RecursiveRegexIterator::GET_MATCH);

        foreach ($files as $file) {
            $relativePath = str_replace($controllersPath . '/', '', $file[0]);
            // Build namespace including subdirectories
            $className = str_replace(['/', '.php'], ['\\', ''], $controllerNamespace . $relativePath);

            if (class_exists($className)) {
                $reflection = new \ReflectionClass($className);
                if (!$reflection->isAbstract()) {
                    $definition = $container->add($className);

                    // Base dependencies
                    $definition->addArgument(Application::class)
                        ->addArgument(ServerRequestInterface::class)
                        ->addArgument(Logger::class);

                    // Additional dependencies
                    $constructor = $reflection->getConstructor();
                    if ($constructor) {
                        $params = $constructor->getParameters();
                        for ($i = 3; $i < count($params); $i++) {
                            $type = $params[$i]->getType();
                            if ($type instanceof \ReflectionNamedType) {
                                $definition->addArgument($type->getName());
                            }
                        }
                    }
                }
            }
        }
    }
}
