<?php

namespace App\Traits;

use Psr\Http\Message\ResponseInterface;
use App\Utils\ResponseEmitter;

trait JsonResponder
{
    protected function jsonResponse(array $data, int $statusCode = 200, array $headers = []): int
    {
        $response = new \Laminas\Diactoros\Response\JsonResponse($data, $statusCode, $headers);

        $this->emitJsonResponse($response);

        return $response->getStatusCode();
    }

    private function emitJsonResponse(ResponseInterface $response): void
    {
        $emitter = new ResponseEmitter();
        $emitter->emit($response);
    }
}
