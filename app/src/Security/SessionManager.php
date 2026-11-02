<?php

declare(strict_types=1);

namespace App\Security;

use App\Config;

final class SessionManager
{
    private static bool $started = false;

    /**
     * Запуск сессионного механизма с регистрацией обработчика БД.
     */
    public static function start(): void
    {
        if (self::$started || session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Регистрируем наш кастомный хэндлер сессий в базе данных
        session_set_save_handler(new DatabaseSessionHandler, true);

        // Имя куки сессии из конфига
        $sessionName = (string) Config::get('app.session.name', 'PHPSESSID');
        session_name($sessionName);

        // Безопасные параметры куки из конфига
        session_set_cookie_params([
            'lifetime' => (int) Config::get('app.session.lifetime', 7200),
            'path' => (string) Config::get('app.session.path', '/'),
            'domain' => (string) Config::get('app.session.domain', ''),
            'secure' => (bool) Config::get('app.session.secure', false),
            'httponly' => (bool) Config::get('app.session.httponly', true),
            'samesite' => (string) Config::get('app.session.samesite', 'Lax'),
        ]);

        session_start();
        self::$started = true;
    }
}
