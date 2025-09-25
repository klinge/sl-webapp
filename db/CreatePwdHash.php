<?php

require_once '../App/Services/Auth/PasswordService.php';

use App\Services\Auth\PasswordService;

$passwordService = new PasswordService();

// Generate hash for a password
$password = 'test123';
$hash = $passwordService->hashPassword($password);

echo "Password: $password\n";
echo "Hash: $hash\n";

// Verify it works
$isValid = $passwordService->verifyPassword($password, $hash);
echo "Verification: " . ($isValid ? 'SUCCESS' : 'FAILED') . "\n";
