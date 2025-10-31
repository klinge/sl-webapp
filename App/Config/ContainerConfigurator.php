<?php

declare(strict_types=1);

namespace App\Config;

use League\Container\Container;
use Psr\Http\Message\ServerRequestInterface;
use League\Route\Router;
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

        // Register middleware
        $container->add(\App\Middleware\RequireAdminMiddleware::class)
            ->addArgument(Logger::class);
        $container->add(\App\Middleware\RequireAuthenticationMiddleware::class)
            ->addArgument(Logger::class);

        // Register repositories
        $container->add(\App\Models\RollRepository::class)
            ->addArgument('PDO')
            ->addArgument(Logger::class);

        // Register services
        $container->add(\App\Services\MedlemService::class)
            ->addArgument(\App\Models\MedlemRepository::class)
            ->addArgument(\App\Models\BetalningRepository::class)
            ->addArgument(\App\Models\RollRepository::class)
            ->addArgument(\App\Services\MedlemDataValidatorService::class)
            ->addArgument(\App\Services\MailAliasService::class)
            ->addArgument(Application::class)
            ->addArgument(Logger::class);

        $container->add(\App\Services\BetalningService::class)
            ->addArgument(\App\Models\BetalningRepository::class)
            ->addArgument(\App\Models\MedlemRepository::class)
            ->addArgument(\App\Utils\Email::class)
            ->addArgument(Application::class)
            ->addArgument(Logger::class);

        $container->add(\App\Services\SeglingService::class)
            ->addArgument(\App\Models\SeglingRepository::class)
            ->addArgument(\App\Models\BetalningRepository::class)
            ->addArgument(\App\Models\MedlemRepository::class)
            ->addArgument(\App\Models\Roll::class)
            ->addArgument(Logger::class);

        $container->add(\App\Services\RollService::class)
            ->addArgument(\App\Models\RollRepository::class)
            ->addArgument(\App\Models\MedlemRepository::class);

        $container->add(\App\Services\MedlemDataValidatorService::class);
        $container->add(\App\Services\MailAliasService::class)
            ->addArgument(Logger::class)
            ->addArgument('config');

        // Manual registration for refactored controllers
        $container->add(\App\Controllers\MedlemController::class)
            ->addArgument(\App\Services\MedlemService::class)
            ->addArgument(\App\Utils\View::class)
            ->addArgument(Application::class);

        $container->add(\App\Controllers\BetalningController::class)
            ->addArgument(\App\Services\BetalningService::class)
            ->addArgument(\App\Utils\View::class)
            ->addArgument(Application::class);

        $container->add(\App\Controllers\SeglingController::class)
            ->addArgument(\App\Services\SeglingService::class)
            ->addArgument(\App\Utils\View::class)
            ->addArgument(Application::class);

        $container->add(\App\Controllers\RollController::class)
            ->addArgument(\App\Services\RollService::class)
            ->addArgument(\App\Utils\View::class)
            ->addArgument(Application::class);

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

            $manuallyRegistered = [
                'App\\Controllers\\MedlemController',
                'App\\Controllers\\BetalningController',
                'App\\Controllers\\SeglingController',
                'App\\Controllers\\RollController'
            ];

            if (class_exists($className) && !in_array($className, $manuallyRegistered)) {
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
