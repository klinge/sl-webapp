<?php

declare(strict_types=1);

namespace App\Utils;

use PDO;
use PDOException;
use App\Application;
use App\Utils\TokenType;

class TokenHandler
{
    private PDO $conn;
    private Application $app;

    public function __construct(PDO $conn, Application $app)
    {
        $this->conn = $conn;
        $this->app = $app;
    }

    public function generateToken(): string
    {
        //$token = bin2hex(random_bytes(16));
        //Changed to make url-safe tokens only containing alphanumeric characters
        $token = preg_replace('/[^A-Za-z0-9]/', '', base64_encode(random_bytes(20)));
        return $token;
    }

    public function saveToken(string $token, TokenType $tokenType, string $email, ?string $hashedPassword = null): bool
    {
        //Activation has a password, reset does not..
        if ($tokenType === TokenType::ACTIVATION) {
            $stmt = $this->conn->prepare(
                "INSERT INTO AuthToken (email, token, token_type, password_hash) VALUES (:email, :token, :token_type, :password_hash)"
            );
        } elseif ($tokenType === TokenType::RESET) {
            $stmt = $this->conn->prepare("INSERT INTO AuthToken (email, token, token_type) VALUES (:email, :token, :token_type)");
        } else {
            $this->app->getLogger()->error("TokenHandler::Invalid token type: " . $tokenType->value);
            return false;
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
            $this->app->getLogger()->error("TokenHandler::Error saving token to database: " . $e->getMessage());
            return false;
        }
    }

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

    public function deleteToken(string $token): bool
    {
        try {
            $stmt = $this->conn->prepare("DELETE FROM AuthToken WHERE token = :token");
            $stmt->bindParam(':token', $token);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            $this->app->getLogger()->error("TokenHandler::Error deleting token from database: " . $e->getMessage());
            return false;
        }
    }

    public function deleteExpiredTokens(): int
    {
        $stmt = $this->conn->prepare("DELETE FROM AuthToken WHERE created_at < datetime('now', '-1 hour')");
        $stmt->execute();
        //Return number of deleted rows
        $this->app->getLogger()->info("TokenHandler::Deleted " . $stmt->rowCount() . " expired tokens");
        return $stmt->rowCount();
    }
}
