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

    /** @var Application Full path to the application base directory*/
    private string $appPath;

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
        $this->appPath = $this->app->getAppDir();
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
        $viewData['APP_DIR'] = $this->app->getAppDir();
        $viewData['BASE_URL'] = $this->app->getBasePath();
        $this->data = array_merge($this->data, ['viewData' => $viewData]);

        $filePath = $this->appPath . '/views/' . $template . '.php';

        if (!file_exists($filePath)) {
            throw new \Exception("View {$template} not found");
            return false;
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
