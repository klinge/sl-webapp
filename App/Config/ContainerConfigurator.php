<?php

declare(strict_types=1);

namespace App\Config;

use League\Container\Container;
use Psr\Http\Message\ServerRequestInterface;
use Monolog\Logger;
use AltoRouter;
use PDO;
use App\Application;
use App\Utils\Database;
use App\Utils\Session;

class ContainerConfigurator
{
    public static function registerServices(Container $container, Application $app): void
    {
        // Core services registration
        $container->add(Application::class, $app);
        $container->add(ServerRequestInterface::class, $app->getPsrRequest());
        $container->add(Logger::class, $app->getLogger());
        $container->add(AltoRouter::class, $app->getRouter());
        $container->add(Session::class);

        // Add database and PDO connection to the container
        $container->add(Database::class, function () use ($container, $app) {
            return Database::getInstance(
                $app->getConfig('DB_PATH'),
                $container->get(Logger::class)
            );
        });
        $container->add(PDO::class, function () use ($container) {
            return $container->get(Database::class)->getConnection();
        });

        // Autoregister all controllers in the container
        self::registerControllers($container, $app);
    }

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
                    $definition
                        ->addArgument(Application::class)
                        ->addArgument(ServerRequestInterface::class);

                    // Additional dependencies
                    $constructor = $reflection->getConstructor();
                    if ($constructor) {
                        $params = $constructor->getParameters();
                        for ($i = 2; $i < count($params); $i++) {
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
