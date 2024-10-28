<?php

declare(strict_types=1);

namespace App\Traits;

use App\Utils\Session;

trait ResponseFormatter
{
    protected function redirectWithSuccess(string $route, string $message = ''): void
    {
        if (!empty($message)) {
            Session::setFlashMessage('success', $message);
        }
        header('Location: ' . $this->app->getRouter()->generate($route));
    }

    protected function redirectWithError(string $route, string $message): void
    {
        Session::setFlashMessage('error', $message);
        header('Location: ' . $this->app->getRouter()->generate($route));
    }

    protected function renderWithError(string $view, string $message, array $data = []): void
    {
        Session::setFlashMessage('error', $message);
        $this->view->render($view, $data);
    }
}
