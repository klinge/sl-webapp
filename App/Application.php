<?php

declare(strict_types=1);

namespace App;

use Dotenv\Dotenv;
use AltoRouter; //https://dannyvankooten.github.io/AltoRouter/
use App\Config\RouteConfig;
use Exception;
use App\Middleware\MiddlewareInterface;
use App\Middleware\AuthorizationMiddleware;
use App\Middleware\AuthenticationMiddleware;
use App\Utils\Session;

/**
 * The main Application class that bootstraps the application and handles routing.
 *
 * This class is responsible for:
 * - Loading the environment variables from the .env file
 * - Loading the application configuration
 * - Setting up the error reporting based on the application environment
 * - Setting up the routing using the AltoRouter library
 * - Registering middleware to be executed for each request
 * - Dispatching to the appropriate controller action based on the current route
 * - Starting the session
 */
class Application
{
    private array $config = [];
    private ?AltoRouter $router = null;
    private array $middlewares = [];
    private string $rootDir = '';

    public function __construct()
    {
        $this->rootDir = dirname(__DIR__);
        $this->loadEnvironment();
        $this->loadConfig();
        $this->setErrorReporting($this->getAppEnv());
        $this->setupRouter();
        $this->setupSession();

        // Add middlewares here
        $this->addMiddleware(new AuthenticationMiddleware($this, $_SERVER));
        $this->addMiddleware(new AuthorizationMiddleware($this, $_SERVER));
    }

    /**
     * Sets up the router for the application.
     *
     * This method initializes the AltoRouter instance and sets the base path for
     * the router. It then calls the `RouteConfig::createAppRoutes` static method
     * to define the application routes using the router instance.
     *
     * @return void
     */
    private function setupRouter(): void
    {
        $this->router = new AltoRouter();
        $this->router->setBasePath($this->getConfig('APP_DIR'));

        // Routes are created from the Config/RouteConfig class
        RouteConfig::createAppRoutes($this->router);
    }

    /**
     * Loads the environment variables from the .env file using the Dotenv library.
     *
     * The path to the .env file is hardcoded in this method, as the base directory
     * of the application in in the .env file. After loading the .env file, the
     * environment variables can be accessed using Application::getConfig['key'].
     *
     * @return void
     */
    private function loadEnvironment(): void
    {
        $dotenv = Dotenv::createImmutable($this->rootDir);
        $dotenv->load();
    }

    /**
     * Loads the application configuration.
     *
     * This method loads the application configuration from the environment variables
     * and converts the string values to their appropriate data types (boolean, etc.).
     *
     * @return void
     */
    private function loadConfig(): void
    {
        $this->config = array_map(function ($value) {
            return $value === 'true' ? true : ($value === 'false' ? false : $value);
        }, $_ENV);
    }

    /**
     * Returns the path for the application relative to the servers document root,
     * returns an empty string if the APP_DIR is not set.
     *
     * @return ?string The base path for the application
     */
    public function getAppDir(): ?string
    {
        return $this->config['APP_DIR'];
    }

    /**
     * Returns the full path for the application root directory
     *
     * @return string The full path to the application root directory
     */
    public function getRootDir(): string
    {
        return $this->rootDir;
    }

    /**
     * Returns the full path for the application root directory
     *
     * @return string The full path to the application root directory
     */
    public function getAppEnv(): string
    {
        //Only return DEV if the APP_ENV is set to DEV, otherwise default to PROD
        return ($this->config['APP_ENV'] === "DEV") ? "DEV" : "PROD";
    }

    /**
     * Returns the value of a specific environment variable.
     *
     * @param string $key The environment variable to retrieve
     *
     * @return string|null The value of the environment variebale, or null if the key is not found
     */
    public function getConfig(string $key): string|null
    {
        return $this->config[$key] ?? null;
    }

    /**
     * Returns the AltoRouter instance used by the application.
     *
     * @return AltoRouter The AltoRouter instance
     */
    public function getRouter(): AltoRouter
    {
        return $this->router;
    }

    /**
     * Sets up the session for the application.
     *
     * This method configures the session settings, such as the cookie settings
     * and the session lifetime, and starts the session.
     *
     * @return void
     */
    private function setupSession(): void
    {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_lifetime', 1800);
        Session::start();
    }

    /**
     * Sets error reporting for the application.
     *
     * Turns error reporting on or off depending on the application environment.
     *
     * @return void
     */
    private function setErrorReporting(string $appEnv): void
    {
        if ($appEnv === 'DEV') {
            error_reporting(E_ALL);
            ini_set('display_errors', 'On');
        } else {
            error_reporting(0);
            ini_set('display_errors', 'Off');
        }
    }

    /**
     * Adds a middleware to the application. The middleware must implement
     * the MiddlewareInterface.
     *
     * @param MiddlewareInterface $middleware The middleware instance to add
     *
     * @return void
     */
    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Runs all registered middlewares.
     *
     * This method iterates over the registered middlewares and calls the `handle`
     * method on each middleware instance.
     *
     * @return void
     */
    public function runMiddleware(): void
    {
        foreach ($this->middlewares as $middleware) {
            $middleware->handle();
        }
    }

    /**
     * Dispatches the request to the appropriate controller and action.
     *
     * This method handles the dispatching of the request based on the matched route.
     * If the matched route target is a string in the format "controller#action", it
     * will instantiate the specified controller class, check if the action method
     * exists, and call it with the provided parameters.
     *
     * If the matched route target is a callable (closure), it will call the closure
     * with the provided parameters.
     *
     * @param array $match The matched route information
     * @param array $request The current request object
     *
     * @return void
     *
     * @throws Exception If the controller class is not found
     */
    private function dispatch(array $match, array $request): void
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
                $controllerInstance = new $controllerClass($this, $request);
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

    /**
     * Runs the application.
     *
     * This method is the entry point for the application. It matches the current
     * request against the defined routes, and if a match is found, it dispatches
     * the request to the appropriate controller and action. If no match is found,
     * it handles the 404 error.
     *
     * @return void
     */
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
            $this->dispatch($match, $request);
        }
    }
}
