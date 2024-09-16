<?php

namespace App\Utils;

class Session
{
    public static function start()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function regenerateId()
    {
        session_regenerate_id(true);
    }

    public static function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public static function setFlashMessage(string $type, string $message)
    {
        $_SESSION['flash_message'] = ['type' => $type, 'message' => $message];
    }

    public static function get($key)
    {
        return $_SESSION[$key] ?? null;
    }

    public static function remove($key)
    {
        unset($_SESSION[$key]);
    }

    public static function destroy()
    {
        session_destroy();
    }

    public static function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    public static function isAdmin()
    {
        return isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] === true;
    }
}
