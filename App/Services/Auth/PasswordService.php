<?php

declare(strict_types=1);

namespace App\Services\Auth;

class PasswordService
{
    /**
     * Validates a password against complexity requirements.
     *
     * @param string $password The password to validate
     * @param string $email The user's email address
     * @return array An array of error messages, or an empty array if valid
     */
    public function validatePassword(string $password, string $email): array
    {
        $errors = [];

        // Length check
        if (strlen($password) < 8) {
            $errors[] = "Lösenordet måste vara minst 8 tecken.";
        }

        // Character type checks
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = "Lösenordet måste innehålla minst en stor bokstav.";
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = "Lösenordet måste innehålla minst en liten bokstav.";
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = "Lösenordet måste innehålla minst en siffra";
        }

        // Email-based checks
        $username = strstr($email, '@', true);
        $lowercasePassword = strtolower($password);

        if (strpos($lowercasePassword, strtolower($username)) !== false) {
            $errors[] = "Lösenordet får inte innehålla delar från din mailadress.";
        }

        // Name checks if email contains a period
        if (strpos($username, '.') !== false) {
            list($firstName, $lastName) = explode('.', $username, 2);

            if (strpos($lowercasePassword, strtolower($firstName)) !== false) {
                $errors[] = "Lösenordet får inte innehålla ditt förnamn.";
            }
            if (strpos($lowercasePassword, strtolower($lastName)) !== false) {
                $errors[] = "Lösenordet får inte innehålla ditt efternamn.";
            }
        }

        return $errors;
    }

    /**
     * Verifies if two passwords match.
     *
     * @param string $password
     * @param string $confirmPassword
     * @return bool
     */
    public function passwordsMatch(string $password, string $confirmPassword): bool
    {
        return $password === $confirmPassword;
    }

    /**
     * Hashes a password using PHP's password_hash function.
     *
     * @param string $password
     * @return string
     */
    public function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    /**
     * Verifies a password against a hash.
     *
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
