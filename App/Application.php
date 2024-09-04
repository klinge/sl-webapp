<?php

namespace App;

use Dotenv\Dotenv;
use AltoRouter; //https://dannyvankooten.github.io/AltoRouter/
use Exception;
use App\Utils\Session;

class Application
{
    private $config;
    private $router;

    public function __construct()
    {
        $this->loadEnvironment();
        $this->loadConfig();
        $this->setupRouter();
        $this->setupSession();
    }

    private function setupRouter()
    {
        $this->router = new AltoRouter();
        $this->router->setBasePath('/sl-webapp');

        // Setup your routes here
        $this->router->map('GET', '/', 'HomeController#index', 'home');

        $this->router->map('GET', '/medlem', 'MedlemController#list', 'medlem-list');
        $this->router->map('GET', '/medlem/[i:id]', 'MedlemController#edit', 'medlem-edit');
        $this->router->map('POST', '/medlem/[i:id]', 'MedlemController#save', 'medlem-save');
        $this->router->map('GET', '/medlem/new', 'MedlemController#new', 'medlem-new');
        $this->router->map('POST', '/medlem/new', 'MedlemController#insertNew', 'medlem-create');
        $this->router->map('POST', '/medlem/delete', 'MedlemController#delete', 'medlem-delete');

        $this->router->map('GET', '/betalning', 'BetalningController#list', 'betalning-list');
        $this->router->map('GET', '/betalning/[i:id]', 'BetalningController#getBetalning', 'betalning-edit');
        $this->router->map('GET', '/betalning/medlem/[i:id]', 'BetalningController#getMedlemBetalning', 'betalning-medlem');
        $this->router->map('POST', '/betalning/create', 'BetalningController#createBetalning', 'betalning-create');
        $this->router->map('POST', '/betalning/delete/[i:id]', 'BetalningController#deleteBetalning', 'betalning-delete');

        $this->router->map('GET', '/segling', 'SeglingController#list', 'segling-list');
        $this->router->map('GET', '/segling/[i:id]', 'SeglingController#edit', 'segling-edit');
        $this->router->map('POST', '/segling/[i:id]', 'SeglingController#save', 'segling-save');

        $this->router->map('GET', '/login', 'AuthController#showLogin', 'show-login');
        $this->router->map('POST', '/login', 'AuthController#login', 'login');
        $this->router->map('GET', '/logout', 'AuthController#logout', 'logout');
        $this->router->map('POST', '/register', 'AuthController#register', 'register');
        $this->router->map('GET', '/register/[a:token]', 'AuthController#activate', 'register-activate');
        $this->router->map('GET', '/auth/bytlosenord', 'AuthController#showRequestPwd', 'show-request-password');
        $this->router->map('POST', '/auth/bytlosenord', 'AuthController#handleRequestPwd', 'handle-request-password');

        //Route all other urls to 404
        $this->router->map('GET|POST', '*', 'HomeController#PageNotFound', '404');
    }

    private function dispatch($match, $request, $router)
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
        }
        //Handle the case then the target is a closure
        else if (is_array($match) && is_callable($match['target'])) {
            call_user_func_array($match['target'], $match['params']);
        } else {
            header($_SERVER["SERVER_PROTOCOL"] . ' 404 Not Found');
        }
    }

    private function loadEnvironment()
    {
        $dotenv = Dotenv::createImmutable($_SERVER['DOCUMENT_ROOT'] . '/sl-webapp');
        $dotenv->load();
    }

    private function loadConfig()
    {

        $this->config = array_map(function ($value) {
            return $value === 'true' ? true : ($value === 'false' ? false : $value);
        }, $_ENV);
    }


    public function getAppDir()
    {
        return $_SERVER['DOCUMENT_ROOT'] . $this->config['APP_DIR'];
    }

    public function getConfig($key)
    {
        var_dump($this->config);
        return $this->config[$key] ?? null;
    }

    public function getRouter()
    {
        return $this->router;
    }

    private function setupSession()
    {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_lifetime', 1800);
        Session::start();
    }

    public function run()
    {
        // Match the current request
        $match = $this->router->match();
        // Handle the route match and execute the appropriate controller
        if ($match === false) {
            echo "Ingen mappning för denna url";
            // here you can handle 404
        } else {
            $request = $_SERVER;
            $this->dispatch($match, $request, $this->router);
        }
    }
}
