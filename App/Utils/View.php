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

    /** @var array<string, mixed> Data to be passed to the view */
    private array $data = [];



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
    }

    /**
     * Render a view template
     *
     * @param string $template The name of the template file (without .php extension)
     * @param array<string, mixed> $data Additional data to be passed to the view
     * @return ResponseInterface The rendered HTML response
     * @throws \Exception If the view file is not found
     */
    public function render(string $template, array $data = []): ResponseInterface
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

        //Return the response object without emitting
        return new \Laminas\Diactoros\Response\HtmlResponse(
            $result,
            200
        );
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
