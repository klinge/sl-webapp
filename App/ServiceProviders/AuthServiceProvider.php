<?php

declare(strict_types=1);

namespace App\ServiceProviders;

use League\Container\ServiceProvider\AbstractServiceProvider;
use App\Services\Auth\UserAuthenticationService;
use App\Services\Auth\PasswordService;
use Monolog\Logger;
use AltoRouter;
use App\Utils\Email;
use PDO;

class AuthServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return in_array($id, [
            UserAuthenticationService::class,
            PasswordService::class
        ]);
    }

    public function register(): void
    {
        $this->getContainer()->add(PasswordService::class);

        $this->getContainer()->add(UserAuthenticationService::class)
            ->addArgument(PDO::class)
            ->addArgument(Logger::class)
            ->addArgument(AltoRouter::class)
            ->addArgument(Email::class)
            ->addArgument('config');
    }
}
