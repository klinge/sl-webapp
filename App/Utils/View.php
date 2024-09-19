<?php

declare(strict_types=1);

namespace App\Utils;

use App\Application;

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
    private string $fullPath;

    /** @var array Data to be passed to the view */
    private $data = [];

    /**
     * Constructor
     *
     * @param Application $app An instance of the application object passed to the constructor
     */
    public function __construct(Application $app)
    {
        $this->app = $app;
        $this->appDir = $this->app->getAppDir();
        $this->fullPath = $this->app->getAbsolutePath();
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
        $filePath = $this->fullPath . '/views/' . $template . '.php';

        if (!file_exists($filePath)) {
            throw new \Exception("View \"{$filePath}\" not found");
        }

        extract($this->data);
        ob_start();
        include $filePath;
        echo ob_get_clean();
        return true;
    }

    /**
     * Assign a variable to the view
     *
     * @param string $key The variable name
     * @param mixed $value The variable value
     */
    public function assign(string $key, $value): void
    {
        $this->data[$key] = $value;
    }
}
