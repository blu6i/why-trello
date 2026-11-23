<?php

declare(strict_types=1);

namespace App\Security;

use App\Config;

/**
 * Управление CSRF-токенами в привязке к текущей PHP-сессии.
 */
final class Csrf
{
    private const SESSION_KEY = '_csrf_token';

    public const COOKIE_NAME = 'XSRF-TOKEN';

    /**
     * Возвращает текущий CSRF-токен сессии (при отсутствии генерирует новый)
     * и выставляет cookie XSRF-TOKEN для чтения SPA-клиентом.
     */
    public static function token(): string
    {
        SessionManager::start();

        $token = $_SESSION[self::SESSION_KEY] ?? null;

        if (! is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $_SESSION[self::SESSION_KEY] = $token;
        }

        self::sendCookie($token);

        return $token;
    }

    /**
     * Проверяет переданный CSRF-токен методом постоянного времени (hash_equals).
     *
     * @param  string|null  $token  Токен из поля формы или HTTP-заголовка.
     */
    public static function verify(?string $token): bool
    {
        SessionManager::start();

        $sessionToken = $_SESSION[self::SESSION_KEY] ?? null;

        if (! is_string($sessionToken) || $sessionToken === '' || ! is_string($token) || $token === '') {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Перевыпускает CSRF-токен (например, после входа или регистрации).
     */
    public static function rotate(): string
    {
        SessionManager::start();

        $token = bin2hex(random_bytes(32));
        $_SESSION[self::SESSION_KEY] = $token;
        self::sendCookie($token);

        return $token;
    }

    private static function sendCookie(string $token): void
    {
        if (headers_sent()) {
            return;
        }

        setcookie(
            self::COOKIE_NAME,
            $token,
            [
                'expires' => time() + (int) Config::get('app.session.lifetime', 7200),
                'path' => (string) Config::get('app.session.path', '/'),
                'domain' => (string) Config::get('app.session.domain', ''),
                'secure' => (bool) Config::get('app.session.secure', false),
                // SPA читает cookie из document.cookie и передаёт его в заголовке X-XSRF-TOKEN
                'httponly' => false,
                'samesite' => (string) Config::get('app.session.samesite', 'Lax'),
            ]
        );
    }
}
