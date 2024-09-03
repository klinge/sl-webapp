<?php

namespace App;

use Dotenv\Dotenv;
use AltoRouter;

class Application
{
    private $config;
    private $router;

    public function __construct()
    {
        $this->loadEnvironment();
        $this->loadConfig();
        $this->setupRouter();
    }

    private function loadEnvironment()
    {
        $dotenv = Dotenv::createImmutable(__DIR__);
        $dotenv->load();
    }

    private function loadConfig()
    {
        $this->config = [
            'app_path' => $_ENV['APP_PATH'],
            'db_host' => $_ENV['DB_HOST'],
            'db_name' => $_ENV['DB_NAME'],
            // Add other configuration items
        ];
    }

    private function setupRouter()
    {
        $this->router = new AltoRouter();
        // Setup your routes here
    }

    public function getConfig($key)
    {
        return $this->config[$key] ?? null;
    }

    public function getRouter()
    {
        return $this->router;
    }

    public function run()
    {
        // Match the current request
        $match = $this->router->match();
        // Handle the route match and execute the appropriate controller
    }
}
