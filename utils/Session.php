<?php

class Session {
    public static function start() {
        if (session_status() == PHP_SESSION_NONE) {
            session_regenerate_id(true);
            session_start();
        }
    }

    public static function set($key, $value) {
        $_SESSION[$key] = $value;
        print_r($_SESSION);
    }

    public static function get($key) {
        return $_SESSION[$key] ?? null;
    }

    public static function remove($key) {
        unset($_SESSION[$key]);
    }

    public static function destroy() {
        session_destroy();
    }

    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public static function isAdmin() {
        return isset($_SESSION['isAdmin']) && $_SESSION['isAdmin'] === true;
    }
}

?>