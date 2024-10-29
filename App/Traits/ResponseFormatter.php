<?php

declare(strict_types=1);

namespace App\Traits;

use App\Utils\Session;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use App\Utils\ResponseEmitter;

trait ResponseFormatter
{
    protected function redirectWithSuccess(string $route, string $message = ''): void
    {
        if (!empty($message)) {
            Session::setFlashMessage('success', $message);
        }

        $this->emitRedirect($route);
    }

    protected function redirectWithError(string $route, string $message): void
    {
        Session::setFlashMessage('error', $message);
        $this->emitRedirect($route);
    }

    protected function renderWithError(string $view, string $message, array $data = []): void
    {
        Session::setFlashMessage('error', $message);
        $this->view->render($view, $data);
    }

    private function emitRedirect(string $route): ResponseInterface
    {
        $response = new RedirectResponse($this->app->getRouter()->generate($route));

        $responseEmitter = new ResponseEmitter();
        $responseEmitter->emit($response);

        return $response;
    }
}
