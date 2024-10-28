<?php

declare(strict_types=1);

namespace App\Services\Auth;

class AuthenticationService
{
    public function __construct(
        private MemberRepository $memberRepo,
        private PasswordService $passwordService,
        private TokenHandler $tokenHandler,
        private Logger $logger
    ) {}

    public function authenticateUser(string $email, string $password): array
    {
        // Move core login logic here
        // Return result with success/failure and user data
    }

    public function registerUser(string $email, string $password): array
    {
        // Move core registration logic here
    }
}
