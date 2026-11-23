<?php

declare(strict_types=1);

namespace App\Security;

use App\Models\User;

/**
 * Класс, реализующий авторизацию пользователя из сессии, регистрацию и разлогин.
 */
class UserSessionAuth implements UserAuthInterface
{
    /** @var User|null Кэшированный экземпляр пользователя в рамках текущего запроса */
    private ?User $cachedUser = null;

    /**
     * Авторизация пользователя из активной сессии.
     *
     * Читает идентификатор пользователя из суперглобального массива $_SESSION['user_id'],
     * проверяет его существование в базе данных и кэширует результат.
     *
     * @return User|null Возвращает модель пользователя или null, если сессия не авторизована.
     */
    public function authorizeFromSession(): ?User
    {
        SessionManager::start();

        $userId = $this->id();

        if ($userId === null) {
            $this->cachedUser = null;

            return null;
        }

        if ($this->cachedUser !== null && $this->cachedUser->user_id === $userId) {
            return $this->cachedUser;
        }

        $user = User::find($userId);

        if ($user === null) {
            // Если пользователь был удален из БД, сбрасываем невалидную сессию
            unset($_SESSION['user_id']);
            $this->cachedUser = null;

            return null;
        }

        $this->cachedUser = $user;

        return $this->cachedUser;
    }

    /**
     * Регистрация нового пользователя с созданием хэша пароля и авторизацией в сессии.
     *
     * @param  string  $name  Имя пользователя.
     * @param  string  $email  Адрес электронной почты.
     * @param  string  $password  Пароль (хэшируется алгоритмом BCRYPT).
     * @return User Созданный пользователь.
     */
    public function register(string $name, string $email, string $password): User
    {
        SessionManager::start();

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password_hash' => password_hash($password, PASSWORD_BCRYPT),
        ]);

        $this->loginUser($user);

        return $user;
    }

    /**
     * Аутентификация и авторизация пользователя по связке email и пароля.
     *
     * @param  string  $email  Электронная почта.
     * @param  string  $password  Введенный пароль.
     * @return bool True при успешном входе, false при неверных реквизитах.
     */
    public function login(string $email, string $password): bool
    {
        SessionManager::start();

        $user = User::where('email', $email)->first();

        if ($user === null || ! password_verify($password, (string) $user->password_hash)) {
            return false;
        }

        $this->loginUser($user);

        return true;
    }

    /**
     * Выход из системы (разлогин): уничтожение сессии и очистка сессионной куки.
     */
    public function logout(): void
    {
        $this->cachedUser = null;
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
     * Проверка наличия активной авторизованной сессии.
     *
     * @return bool True, если в сессии сохранен валидный user_id.
     */
    public function check(): bool
    {
        return $this->id() !== null;
    }

    /**
     * Получение модели текущего пользователя.
     */
    public function user(): ?User
    {
        return $this->authorizeFromSession();
    }

    /**
     * Получение целочисленного ID текущего пользователя из сессии.
     */
    public function id(): ?int
    {
        SessionManager::start();

        if (isset($_SESSION['user_id']) && (int) $_SESSION['user_id'] > 0) {
            return (int) $_SESSION['user_id'];
        }

        return null;
    }

    /**
     * Авторизация переданной модели пользователя в сессии с защитой от Session Fixation.
     *
     * @param  User  $user  Модель пользователя.
     */
    private function loginUser(User $user): void
    {
        $this->regenerateSessionId();

        $_SESSION['user_id'] = $user->user_id;
        $this->cachedUser = $user;
    }

    /**
     * Безопасная регенерация ID сессии.
     */
    private function regenerateSessionId(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE && ! headers_sent()) {
            session_regenerate_id(true);
        }
    }
}
