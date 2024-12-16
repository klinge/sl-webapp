<?php

declare(strict_types=1);

namespace App\ServiceProviders;

use League\Container\ServiceProvider\AbstractServiceProvider;
use App\Models\Betalning;
use App\Models\BetalningRepository;
use App\Models\Medlem;
use App\Models\MedlemRepository;
use App\Models\Roll;
use App\Models\Segling;
use App\Models\SeglingRepository;
use PDO;
use Monolog\Logger;

class ModelServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return in_array($id, [
            Betalning::class,
            BetalningRepository::class,
            Medlem::class,
            MedlemRepository::class,
            Roll::class,
            Segling::class,
            SeglingRepository::class
        ]);
    }

    public function register(): void
    {
        $this->getContainer()->add(Betalning::class)
            ->addArgument(PDO::class)
            ->addArgument(Logger::class)
            ->addArgument([]); #optional payment data

        $this->getContainer()->add(BetalningRepository::class)
            ->addArgument(PDO::class)
            ->addArgument(Logger::class);

        $this->getContainer()->add(Medlem::class)
            ->addArgument(PDO::class)
            ->addArgument(Logger::class)
            ->addArgument(null); #an optional member id

        $this->getContainer()->add(MedlemRepository::class)
            ->addArgument(PDO::class)
            ->addArgument(Logger::class);

        $this->getContainer()->add(Roll::class)
            ->addArgument(PDO::class)
            ->addArgument(Logger::class);

        $this->getContainer()->add(Segling::class)
            ->addArgument(PDO::class)
            ->addArgument(Logger::class)
            ->addArgument(null)  // Default null for optional id
            ->addArgument(null); // Default null for optional withdeltagare

        $this->getContainer()->add(SeglingRepository::class)
            ->addArgument(PDO::class)
            ->addArgument(Logger::class);
    }
}
