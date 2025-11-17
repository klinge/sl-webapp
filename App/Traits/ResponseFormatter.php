<?php

declare(strict_types=1);

namespace App\Traits;

use App\Utils\Session;
use Psr\Http\Message\ResponseInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use App\Utils\ResponseEmitter;

/**
 * ResponseFormatter Trait
 *
 * Handles traditional web form responses with redirects and flash messages.
 * Used for server-side form processing where users are redirected to different
 * pages after form submission with success/error messages displayed via session flash.
 *
 * Use this trait for:
 * - Traditional HTML form submissions
 * - User-facing web pages with redirects
 * - Success/error messages that persist across page loads
 *
 * For API/AJAX responses, use JsonResponder trait instead.
 */
trait ResponseFormatter
{
    /**
     * Redirect to a route with an optional success message.
     *
     * @param string $route The route name to redirect to
     * @param string $message Optional success message to display on the target page
     */
    protected function redirectWithSuccess(string $route, string $message = ''): ResponseInterface
    {
        if (!empty($message)) {
            Session::setFlashMessage('success', $message);
        }

        return $this->createRedirect($route);
    }

    /**
     * Redirect to a route with an error message.
     *
     * @param string $route The route name to redirect to
     * @param string $message Error message to display on the target page
     */
    protected function redirectWithError(string $route, string $message): ResponseInterface
    {
        Session::setFlashMessage('error', $message);
        return $this->createRedirect($route);
    }

    /**
     * Render a view with an error message (no redirect).
     *
     * @param string $view The view template to render
     * @param string $message Error message to display on the current page
     * @param array<string, mixed> $data The data to pass to the view
     * @return ResponseInterface The rendered view response
     */
    protected function renderWithError(string $view, string $message, array $data = []): ResponseInterface
    {
        Session::setFlashMessage('error', $message);
        return $this->view->render($view, $data);
    }

    /**
     * Internal method to emit a redirect response.
     *
     * @param string $route The route name to redirect to
     * @return ResponseInterface The redirect response
     */
    private function createRedirect(string $route): ResponseInterface
    {
        return new RedirectResponse($this->createUrl($route));
    }
}
