<?php

namespace App\Traits;

use Psr\Http\Message\ResponseInterface;
use App\Utils\ResponseEmitter;

trait JsonResponder
{
    protected function jsonResponse(array $data, int $statusCode = 200, array $headers = []): int
    {
        $jsonData = json_encode($data, JSON_PRETTY_PRINT);
        // Check for encoding errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            // Handle encoding errors gracefully
            $jsonData = json_encode(['success' => false, 'message' => 'Error encoding data']);
        }
        $response = new \Laminas\Diactoros\Response\JsonResponse($jsonData, $statusCode, $headers);

        $this->emitJsonResponse($response);

        return $response->getStatusCode();
    }

    private function emitJsonResponse(ResponseInterface $response): void
    {
        $emitter = new ResponseEmitter();
        $emitter->emit($response);
    }
}
