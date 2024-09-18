<?php

namespace App;

use Dotenv\Dotenv;
use AltoRouter; //https://dannyvankooten.github.io/AltoRouter/
use App\Config\RouteConfig;
use Exception;
use App\Middleware\MiddlewareInterface;
use App\Middleware\AuthorizationMiddleware;
use App\Middleware\AuthenticationMiddleware;
use App\Utils\Session;

class Application
{
    private $config;
    private $router;
    private $middlewares = [];

    public function __construct()
    {
        $this->loadEnvironment();
        $this->loadConfig();
        $this->setupRouter();
        $this->setupSession();

        // Add middlewares here
        $this->addMiddleware(new AuthenticationMiddleware($this, $_SERVER));
        $this->addMiddleware(new AuthorizationMiddleware($this, $_SERVER));
    }

    private function setupRouter(): void
    {
        $this->router = new AltoRouter();
        $this->router->setBasePath('/sl-webapp');

        // Routes are created from the Config/RouteConfig class
        RouteConfig::createAppRoutes($this->router);
    }

    private function dispatch($match, $request, $router): void
    {
        //If we have a string with a # then it's a controller action pair
        if (is_string($match['target']) && strpos($match['target'], "#") !== false) {
            //Parse the match to get controller, action and params
            list($controller, $action) = explode('#', $match['target']);
            $params = $match['params'];

            //Autoloading does not work with dynamically created classes, manually load the class
            $controllerClass = "App\\Controllers\\{$controller}";
            if (!class_exists($controllerClass)) {
                throw new Exception("Controller class {$controllerClass} not found");
            }

            //Check that the controller has the requested method and call it
            if (method_exists($controllerClass, $action)) {
                $controllerInstance = new $controllerClass($this, $request, $router);
                $controllerInstance->{$action}($params);
            } else {
                echo 'Error: can not call ' . $controller . '#' . $action;
                //possibly throw a 404 error
            }
        } elseif (is_array($match) && is_callable($match['target'])) {
            //Handle the case then the target is a closure
            call_user_func_array($match['target'], $match['params']);
        } else {
            header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
        }
    }

    private function loadEnvironment(): void
    {
        $dotenv = Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT'] . '/sl-webapp');
        $dotenv->load();
    }

    private function loadConfig(): void
    {
        $this->config = array_map(function ($value) {
            return $value === 'true' ? true : ($value === 'false' ? false : $value);
        }, $_ENV);
    }


    public function getAppDir(): string
    {
        return $_SERVER['DOCUMENT_ROOT'] . $this->config['APP_DIR'];
    }

    public function getBaseUrl(): string
    {
        return $this->config['APP_DIR'];
    }

    public function getConfig(string $key): string
    {
        return $this->config[$key] ?? null;
    }

    public function getRouter(): AltoRouter
    {
        return $this->router;
    }

    private function setupSession(): void
    {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_lifetime', 1800);
        Session::start();
    }

    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    public function runMiddleware(): void
    {
        foreach ($this->middlewares as $middleware) {
            $middleware->handle();
        }
    }

    public function run(): void
    {
        // Match the current request
        $match = $this->router->match();
        // Handle the route match and execute the appropriate controller
        if ($match === false) {
            echo "404 - Ingen mappning för denna url. Och dessutom borde detta aldrig kunna hända!!";
            // here you can handle 404
        } else {
            $request = $_SERVER;
            $this->dispatch($match, $request, $this->router);
        }
    }
}
