<?php

declare(strict_types=1);

namespace App\Traits;

use Psr\Http\Message\ResponseInterface;
use App\Utils\ResponseEmitter;

/**
 * JsonResponder Trait
 *
 * Handles JSON API responses for AJAX calls and webhook endpoints.
 * Used for modern client-side interactions where structured JSON data
 * is returned with appropriate HTTP status codes.
 *
 * Use this trait for:
 * - AJAX endpoints that return data to JavaScript
 * - API endpoints for external integrations
 * - Webhook handlers that need to respond with status information
 * - Any endpoint where JSON response format is expected
 *
 * For traditional web form handling with redirects, use ResponseFormatter trait instead.
 */
trait JsonResponder
{
    /**
     * Create a JSON response without emitting it.
     *
     * @param mixed $data The data to encode as JSON
     * @param int $statusCode HTTP status code (default: 200)
     * @param array<string, string> $headers Additional HTTP headers
     * @return ResponseInterface The JSON response
     */
    protected function jsonResponse(mixed $data, int $statusCode = 200, array $headers = []): ResponseInterface
    {
        return new \Laminas\Diactoros\Response\JsonResponse($data, $statusCode, $headers);
    }
}
