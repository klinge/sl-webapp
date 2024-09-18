<?php

namespace App\Utils;

class Session
{
    public static function start(): bool
    {
        if (session_status() == PHP_SESSION_NONE) {
            return session_start();
        }
        return true;
    }

    public static function regenerateId(): bool
    {
        return session_regenerate_id(true);
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function setFlashMessage(string $type, string $message): void
    {
        $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
    }

    public static function get(string $key): string | null
    {
        return $_SESSION[$key] ?? null;
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        session_destroy();
    }

    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function isAdmin(): bool
    {
        return isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] === true;
    }

    public static function getSessionDataForViews(): array
    {
        return [
            'isLoggedIn' => Session::isLoggedIn(),
            'isAdmin' => Session::isAdmin(),
            'fornamn' => Session::get('fornamn'),
            'user_id' => self::get('user_id'),
            'flash_message' => self::get('flash_message')
        ];
    }
}
