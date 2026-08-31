<?php

declare(strict_types=1);

namespace App\Core;

final class Session
{
    public static function start(string $name = 'kraved_session'): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $https = Helpers::isHttps();
        $cookiePath = Helpers::cookiePath();

        if (!headers_sent()) {
            $expire = time() - 42000;
            foreach (['/public', '/public/'] as $legacyPath) {
                setcookie($name, '', [
                    'expires'  => $expire,
                    'path'     => $legacyPath,
                    'secure'   => $https,
                    'httponly' => true,
                    'samesite' => 'Lax',
                ]);
            }
        }

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        session_name($name);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => $cookiePath,
            'secure'   => $https,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function flash(string $key, mixed $value = null): mixed
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }

        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }

    public static function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'] ?? '', (bool)$p['secure'], (bool)$p['httponly']);
        }
        session_destroy();
    }
}
