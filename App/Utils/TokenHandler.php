<?php

declare(strict_types=1);

namespace App\Utils;

use PDO;
use PDOException;
use Monolog\Logger;
use App\Utils\TokenType;

class TokenHandler
{
    private PDO $conn;
    private Logger $logger;

    /**
     * Initialize TokenHandler with database connection and logger.
     *
     * @param PDO $conn Database connection for token operations
     * @param Logger $logger Logger instance for error and info logging
     */
    public function __construct(PDO $conn, Logger $logger)
    {
        $this->conn = $conn;
        $this->logger = $logger;
    }

    /**
     * Generate a secure, URL-safe token.
     *
     * @return string A random alphanumeric token suitable for URLs
     */
    public function generateToken(): string
    {
        //$token = bin2hex(random_bytes(16));
        //Changed to make url-safe tokens only containing alphanumeric characters
        $token = preg_replace('/[^A-Za-z0-9]/', '', base64_encode(random_bytes(20)));
        return $token;
    }

    /**
     * Save a token to the database with associated metadata.
     *
     * @param string $token The token string to save
     * @param TokenType $tokenType The type of token (ACTIVATION or RESET)
     * @param string $email Email address associated with the token
     * @param string|null $hashedPassword Optional hashed password for activation tokens
     * @return bool True if token saved successfully, false otherwise
     */
    public function saveToken(string $token, TokenType $tokenType, string $email, ?string $hashedPassword = null): bool
    {
        //Activation has a password, reset does not..
        if ($tokenType === TokenType::ACTIVATION) {
            $stmt = $this->conn->prepare(
                "INSERT INTO AuthToken (email, token, token_type, password_hash) VALUES (:email, :token, :token_type, :password_hash)"
            );
        } else {
            $stmt = $this->conn->prepare("INSERT INTO AuthToken (email, token, token_type) VALUES (:email, :token, :token_type)");
        }

        try {
            //Add values to AuthToken table
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':token', $token);
            $stmt->bindValue(':token_type', $tokenType->value);
            if ($tokenType === TokenType::ACTIVATION) {
                $stmt->bindParam(':password_hash', $hashedPassword);
            }
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            $this->logger->error("TokenHandler::Error saving token to database: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Validate a token and check if it's not expired.
     *
     * @param string $token The token to validate
     * @param TokenType $tokenType The expected token type
     * @return array<string, mixed> Validation result with success status, message, email, and password hash
     */
    public function isValidToken(string $token, TokenType $tokenType): array
    {
        // Token validation logic
        $stmt = $this->conn->prepare("SELECT * FROM AuthToken WHERE token = :token AND token_type = :token_type");
        $stmt->bindParam(':token', $token);
        $stmt->bindValue(':token_type', $tokenType->value);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        //Check if we found a token, fail if empty
        if (!$result) {
            return ['success' => false, 'message' => 'Länken är inte giltig'];
        }

        $expirationTime = strtotime($result['created_at']) + (60 * 30); // 30 minutes in seconds
        //Check if token is expired, fail if so
        if (time() > $expirationTime) {
            return ['success' => false, 'message' => 'Länkens giltighetstid är 30 min. Den fungerar inte längre. Försök igen'];
        }
        $hashedPassword = $result['password_hash'] ?: '';
        return ['success' => true, 'email' => $result['email'], 'hashedPassword' => $hashedPassword];
    }

    /**
     * Delete a specific token from the database.
     *
     * @param string $token The token to delete
     * @return bool True if token deleted successfully, false otherwise
     */
    public function deleteToken(string $token): bool
    {
        try {
            $stmt = $this->conn->prepare("DELETE FROM AuthToken WHERE token = :token");
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            $this->logger->error("TokenHandler::Error deleting token from database: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clean up expired tokens from the database.
     *
     * @return int Number of expired tokens deleted
     */
    public function deleteExpiredTokens(): int
    {
        $stmt = $this->conn->prepare("DELETE FROM AuthToken WHERE created_at < datetime('now', '-1 hour')");
        $stmt->execute();
        //Return number of deleted rows
        $this->logger->info("TokenHandler::Deleted " . $stmt->rowCount() . " expired tokens");
        return $stmt->rowCount();
    }
}
