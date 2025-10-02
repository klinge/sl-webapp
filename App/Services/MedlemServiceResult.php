<?php

declare(strict_types=1);

namespace App\Services;

class MedlemServiceResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $message,
        public readonly string $redirectRoute
    ) {
    }
}
