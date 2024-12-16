<?php

declare(strict_types=1);

namespace App\ServiceProviders;

use League\Container\ServiceProvider\AbstractServiceProvider;
use App\Utils\Database;
use PDO;
use Monolog\Logger;

class DatabaseServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return in_array($id, [Database::class, PDO::class]);
    }

    public function register(): void
    {
        $this->getContainer()->add(Database::class, function () {
            return Database::getInstance(
                $this->getContainer()->get('config')['DB_PATH'],
                $this->getContainer()->get(Logger::class)
            );
        });

        $this->getContainer()->add(PDO::class, function () {
            return $this->getContainer()->get(Database::class)->getConnection();
        });
    }
}
