<?php

declare(strict_types=1);

namespace App\Db\Scripts;

use App\Application;

/**
 * MedlemController handles operations related to members (medlemmar).
 *
 * This controller manages CRUD operations for members, including listing,
 * editing, creating, and deleting member records. It also handles related
 * operations such as managing roles and payments for members.
 */
class CreateDb
{
    /**
     * @var Application The application instance
     */
    private Application $app;
    private $db;
    private $logger;

    private function __construct()
    {
        $this->app = new Application();
        $this->db = $this->app->getRootDir() . '/db/sl-prod.sqlite';
        $this->logger = $this->app->getLogger();
    }
}
