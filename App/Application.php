<?php

declare(strict_types=1);

namespace App;

use Dotenv\Dotenv;
use League\Route\Router;
use Monolog\Logger;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\ServerRequestFactory;
use League\Container\Container;
use App\Config\RouteConfig;
use App\Config\ContainerConfigurator;
use App\Middleware\Contracts\MiddlewareInterface;
use App\Middleware\Contracts\MiddlewareStack;
use App\Middleware\ApplicationHandler;
use App\Middleware\AuthorizationMiddleware;
use App\Middleware\AuthenticationMiddleware;
use App\Middleware\CsrfMiddleware;
use App\Utils\Session;
use App\Utils\ResponseEmitter;
use Exception;

/**
 * The main Application class that bootstraps the application and handles routing.
 *
 * This class is responsible for:
 * - Loading the environment variables from the .env file
 * - Loading the application configuration
 * - Setting up the error reporting based on the application environment
 * - Setting up the routing using the League Route library
 * - Registering middleware to be executed for each request
 * - Dispatching to the appropriate controller action based on the current route
 * - Starting the session
 */
class Application
{
    private array $config = [];
    private $container;
    private ?Router $router = null;
    private MiddlewareStack $middlewareStack;
    private string $rootDir = '';
    private ServerRequestInterface $psrRequest;
    private Logger $logger;

    public function __construct()
    {
        $this->rootDir = dirname(__DIR__);
        $this->loadEnvironment();
        $this->loadConfig();
        $this->setErrorReporting($this->getAppEnv());
        $this->setupSession();
        $this->psrRequest = ServerRequestFactory::fromGlobals(
            $_SERVER,
            $_GET,
            $_POST,
            $_COOKIE
        );
        $this->setupContainer();
        $this->logger = $this->container->get(Logger::class);
        $this->setupRouter();
        $this->setupMiddlewareStack();

        // Add middlewares here
        $this->addMiddleware(new AuthenticationMiddleware($this->router, $this->logger));
        $this->addMiddleware(new AuthorizationMiddleware($this->router, $this->logger));
        $this->addMiddleware(new CsrfMiddleware($this->router, $this->logger));
    }

    private function setupContainer(): void
    {
        $this->container = new Container();
        ContainerConfigurator::registerServices($this->container, $this);
    }

    /**
     * Sets up the router for the application.
     *
     * This method initializes the League Router instance and calls the
     * `RouteConfig::createAppRoutes` static method to define the application routes.
     *
     * @return void
     */
    private function setupRouter(): void
    {
        $this->router = new Router();
        
        // Set up the application strategy with container for dependency injection
        $strategy = new \League\Route\Strategy\ApplicationStrategy();
        $strategy->setContainer($this->container);
        $this->router->setStrategy($strategy);

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
        $dotenv->safeLoad();
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
     * Returns the current request as a PSR-7 ServerRequestInterface object.
     *
     * @return ServerRequestInterface The current request
     */
    public function getPsrRequest(): ServerRequestInterface
    {
        return $this->psrRequest;
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
     * Returns the application environment as a string "DEV|PROD", defaults to PROD
     *
     * @return string "DEV"|"PROD""
     */
    public function getAppEnv(): string
    {
        //Only return DEV if the APP_ENV is set to DEV, otherwise default to PROD
        return ($this->config['APP_ENV'] === "DEV") ? "DEV" : "PROD";
    }

    /**
     * Returns the entire config or the value of a specific environment variable
     *
     * @param ?string $key The environment variable to retrieve
     *
     * @return array|string|bool|null The entire config array, the value of the environment variable or null if the key is not found
     */
    public function getConfig(?string $key): array|string|bool|null
    {
        if ($key === null) {
            return $this->config;
        }
        return $this->config[$key] ?? null;
    }

    /**
     * Returns the DI container used by the application.
     *
     * @return Container The DI container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Returns the League Router instance used by the application.
     *
     * @return Router The Router instance
     */
    public function getRouter(): Router
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
        //Only require a secure connection for production
        $isProduction = $this->getAppEnv() === 'PROD';

        // Only set cookie params if session is not already active
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => 3600,
                'secure' => $isProduction,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
        }

        Session::start();

        //Regenerate session id every 30 mins
        if (!isset($_SESSION['session_regeneration_time'])) {
            $_SESSION['session_regeneration_time'] = time();
        } elseif (time() - $_SESSION['session_regeneration_time'] > 1800) { // every 30 minutes
            session_regenerate_id(true);
            $_SESSION['session_regeneration_time'] = time();
        }
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
     * Sets up the PSR-15 compliant middleware stack.
     */
    private function setupMiddlewareStack(): void
    {
        $applicationHandler = new ApplicationHandler($this, $this->router);
        $this->middlewareStack = new MiddlewareStack($applicationHandler);
    }

    /**
     * Adds a middleware to the application stack.
     */
    public function addMiddleware(MiddlewareInterface $middleware): void
    {
        $this->middlewareStack->add($middleware);
    }

    /**
     * Runs the application through the PSR-15 middleware stack.
     */
    public function run(): void
    {
        $response = $this->middlewareStack->handle($this->psrRequest);

        $emitter = new ResponseEmitter();
        $emitter->emit($response);
    }
}
