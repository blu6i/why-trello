<?php

declare(strict_types=1);

namespace App\Security;

use App\Models\User;

final class Auth
{
    private static ?User $cachedUser = null;

    /**
     * Попытка входа по email и паролю (Аутентификация).
     */
    public static function attempt(string $email, string $password): bool
    {
        SessionManager::start();

        $user = User::where('email', $email)->first();

        if ($user === null || ! password_verify($password, (string) $user->password_hash)) {
            return false;
        }

        self::regenerateSessionId();

        $_SESSION['user_id'] = $user->user_id;
        self::$cachedUser = $user;

        return true;
    }

    /**
     * Принудительный логин существующего пользователя по его модели.
     */
    public static function login(User $user): void
    {
        SessionManager::start();
        self::regenerateSessionId();

        $_SESSION['user_id'] = $user->user_id;
        self::$cachedUser = $user;
    }

    /**
     * Регистрация нового пользователя с автоматическим логином.
     */
    public static function register(string $name, string $email, string $password): User
    {
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        self::login($user);

        return $user;
    }

    /**
     * Проверка, авторизован ли текущий посетитель.
     */
    public static function check(): bool
    {
        return isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0;
    }

    /**
     * ID текущего авторизованного пользователя или null.
     */
    public static function id(): ?int
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
    }

    /**
     * Получить модель текущего пользователя (с кэшированием в рамках запроса).
     */
    public static function user(): ?User
    {
        $userId = self::id();

        if ($userId === null) {
            self::$cachedUser = null;

            return null;
        }

        if (self::$cachedUser !== null && self::$cachedUser->user_id === $userId) {
            return self::$cachedUser;
        }

        self::$cachedUser = User::find($userId);

        return self::$cachedUser;
    }

    /**
     * Выход из системы с очисткой данных и уничтожением сессии.
     */
    public static function logout(): void
    {
        self::$cachedUser = null;
        unset($_SESSION['user_id']);

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();

            if (! headers_sent()) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    [
                        'expires' => time() - 42000,
                        'path' => $params['path'] ?? '/',
                        'domain' => $params['domain'] ?? '',
                        'secure' => (bool) ($params['secure'] ?? false),
                        'httponly' => (bool) ($params['httponly'] ?? true),
                        'samesite' => $params['samesite'] ?? 'Lax',
                    ]
                );
            }
        }
    }

    /**
     * Безопасная регенерация ID сессии для защиты от Session Fixation.
     */
    private static function regenerateSessionId(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && ! headers_sent()) {
            session_regenerate_id(true);
        }
    }
}
