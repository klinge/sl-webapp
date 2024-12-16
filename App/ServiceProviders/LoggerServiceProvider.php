<?php

declare(strict_types=1);

namespace App\ServiceProviders;

use League\Container\ServiceProvider\AbstractServiceProvider;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use App\Application;

class LoggerServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return $id === Logger::class;
    }

    public function register(): void
    {
        $this->getContainer()->add(Logger::class, function () {
            $config = $this->getContainer()->get('config');
            $logger = new Logger($config['LOG_NAME']);

            try {
                if ($config['APP_ENV'] === 'DEV') {
                    $rootDir = $this->getContainer()->get(Application::class)->getRootDir();
                    $logPath = $rootDir . '/logs/app.log';

                    $logger->pushHandler(new StreamHandler($logPath, Level::Debug));
                } else {
                    $logger->pushHandler(new StreamHandler($config['LOG_DIR'] . '/app.log', $config['LOG_LEVEL']));
                }
            } catch (\Exception $e) {
                // Fallback to system logger or stderr
                $logger->pushHandler(new StreamHandler('php://stderr', Level::Warning));
            }

            return $logger;
        });
    }
}
