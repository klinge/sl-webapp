<?php

declare(strict_types=1);

namespace App\Db\Scripts;

use App\Application;
use Monolog\Logger;

/**
 * CreateDb handles database creation and management operations.
 */
class CreateDb
{
    private Application $app;
    private string $db;
    private Logger $logger;

    /**
     * Private constructor to prevent direct instantiation.
     * This class should be used statically.
     */
    protected function __construct()
    {
        $this->app = new Application();
        $this->db = $this->app->getRootDir() . '/db/sl-prod.sqlite';
        $this->logger = $this->app->getLogger();
    }

    private function recreateDb()
    {
        $this->backup();
        $this->createDb();
        $this->moveDb();
    }

    private function backup()
    {
        $this->logger->debug('Backing up database');
        $backup = $this->app->getRootDir() . '/db/sl-prod.sqlite.bak';
        if (file_exists($backup)) {
            unlink($backup);
        }
        copy($this->db, $backup);
        if (file_exists($this->db)) {
            unlink($this->db);
        }
        $this->logger->info('Backup of database is now in: ' . $backup);
    }

    private function createDb()
    {
        //TODO
    }

    private function moveDb()
    {
        $this->logger->info('Moving new database in place');
        $newDb = $this->app->getRootDir() . '/db/sl-prod.sqlite';
        if (file_exists($newDb)) {
            //TODO
        }
    }
}
