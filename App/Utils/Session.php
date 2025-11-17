<?php

declare(strict_types=1);

namespace App\Utils;

class Session
{
    /**
     * Start a new session or resume existing session.
     *
     * @return bool True if session started successfully, false otherwise
     */
    public static function start(): bool
    {
        if (session_status() == PHP_SESSION_NONE) {
            return session_start();
        }
        return true;
    }

    /**
     * Regenerate session ID for security purposes.
     *
     * @return bool True if session ID regenerated successfully
     */
    public static function regenerateId(): bool
    {
        return session_regenerate_id(true);
    }

    /**
     * Set a session variable.
     *
     * @param string $key The session variable name
     * @param mixed $value The value to store
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Set a flash message for one-time display.
     *
     * @param string $type The message type (success, error, warning, info)
     * @param string $message The message content
     */
    public static function setFlashMessage(string $type, string $message): void
    {
        $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
    }

    /**
     * Get a session variable value.
     *
     * @param string $key The session variable name
     * @return mixed The session variable value or null if not set
     */
    public static function get(string $key): mixed
    {
        return $_SESSION[$key] ?? null;
    }

    /**
     * Remove a session variable.
     *
     * @param string $key The session variable name to remove
     */
    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    /**
     * Destroy the current session and all session data.
     */
    public static function destroy(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Check if user is currently logged in.
     *
     * @return bool True if user is logged in, false otherwise
     */
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Check if current user has admin privileges.
     *
     * @return bool True if user is admin, false otherwise
     */
    public static function isAdmin(): bool
    {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }

    /**
     * Get session data formatted for use in views.
     *
     * @return array<string, mixed> Array of session data for template rendering
     */
    public static function getSessionDataForViews(): array
    {
        return [
            'isLoggedIn' => self::isLoggedIn(),
            'isAdmin' => self::isAdmin(),
            'fornamn' => self::get('fornamn'),
            'user_id' => self::get('user_id'),
            'flash_message' => self::get('flash_message'),
            'csrf_token' => self::get('csrf_token')
        ];
    }
}
