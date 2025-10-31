<?php

declare(strict_types=1);

namespace App\Services;

class BetalningServiceResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly ?int $paymentId = null
    ) {
    }
}
