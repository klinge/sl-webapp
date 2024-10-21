<?php

declare(strict_types=1);

namespace App\Utils;

use App\Application;
use Psr\Http\Message\ResponseInterface;

/**
 * View Class
 *
 * This class is responsible for rendering views and managing view data.
 */
class View
{
    /** @var Application Instance of the application object */
    private Application $app;

    /** @var string Application directory relative to server root, used in the views */
    private string $appDir;

    /** @var string Full path to the application directory */
    private string $rootPath;

    /** @var array Data to be passed to the view */
    private $data = [];

    /** @var ResponseInterface Response to be emitted */
    private ResponseInterface $response;

    /** @var ResponseEmitter Helper class to emit the respose created */
    private ResponseEmitter $emitter;

    /**
     * Constructor
     *
     * @param Application $app An instance of the application object passed to the constructor
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->appDir = $this->app->getAppDir();
        $this->rootPath = $this->app->getRootDir();
        $this->emitter = new ResponseEmitter();
    }

    /**
     * Render a view template
     *
     * @param string $template The name of the template file (without .php extension)
     * @param array $data Additional data to be passed to the view
     * @return bool It the view was rendered successfully or not
     * @throws \Exception If the view file is not found
     */
    public function render(string $template, array $data = []): bool
    {
        //TODO Keeps having data in the viewData array even if it would be better to just juse key:value-pairs
        $viewData = array_merge($data, Session::getSessionDataForViews());
        $viewData['APP_DIR'] = $this->appDir;
        $this->data = array_merge($this->data, ['viewData' => $viewData]);

        //File path has to be the full path to the view file on the server
        $filePath = $this->rootPath . '/public/views/' . $template . '.php';

        if (!file_exists($filePath)) {
            throw new \Exception("View \"{$filePath}\" not found");
        }

        //save data and template to $result
        extract($this->data);
        ob_start();
        include $filePath;
        $result = ob_get_clean();

        //set properties on the response object
        $this->response = new \Laminas\Diactoros\Response\HtmlResponse(
            $result,
            200
        );

        //Finally emit the view in the response object
        $this->emitter->emit($this->response);
        return true;
    }

    /**
     * Assign a variable to the view
     *
     * @param string $key The variable name
     * @param mixed $value The variable value
     */
    public function assign(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }
}
