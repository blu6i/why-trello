<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Http\Requests\AbstractRequest;
use App\Http\Responses\ResponseModelInterface;
use App\Http\Responses\UserResponseModel;
use App\Http\Validation\Rules\EmailValidator;
use App\Http\Validation\Rules\RequiredValidator;
use App\Models\User;
use App\Security\UserSessionAuth;

/**
 * Класс запроса авторизации (входа) пользователя по email и паролю.
 *
 * Реализует:
 * 1. Валидацию обязательности email и пароля.
 * 2. Валидацию логики (существование пользователя и проверка хэша пароля).
 * 3. Выполнение запроса (сохранение user_id в сессию и регенерация session ID).
 */
final class LoginRequest extends AbstractRequest
{
    private ?User $authenticatedUser = null;

    private ?UserSessionAuth $auth = null;

    public function setAuth(UserSessionAuth $auth): self
    {
        $this->auth = $auth;

        return $this;
    }

    private function getAuth(): UserSessionAuth
    {
        if ($this->auth === null) {
            $this->auth = new UserSessionAuth;
        }

        return $this->auth;
    }

    /**
     * Правила валидации входных параметров авторизации.
     *
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'email' => [
                new RequiredValidator,
                new EmailValidator,
            ],
            'password' => [
                new RequiredValidator,
            ],
        ];
    }

    /**
     * Валидация логики: поиск пользователя в БД и сверка пароля с хэшем bcrypt.
     *
     * @return bool True, если учетные данные верны.
     */
    public function validateLogic(): bool
    {
        $email = trim((string) $this->getParam('email', ''));
        $password = (string) $this->getParam('password', '');

        $user = User::where('email', $email)->first();

        if ($user === null || ! password_verify($password, (string) $user->password_hash)) {
            $this->errors['logic'] = 'Неверный Email или пароль.';

            return false;
        }

        $this->authenticatedUser = $user;

        return true;
    }

    /**
     * Выполнение полезной нагрузки: открытие сессии для пользователя.
     *
     * @return ResponseModelInterface Модель ответа с данными вошедшего пользователя.
     */
    public function execute(): ResponseModelInterface
    {
        $email = trim((string) $this->getParam('email', ''));
        $password = (string) $this->getParam('password', '');

        $this->getAuth()->login($email, $password);

        /** @var User $user */
        $user = $this->authenticatedUser ?? $this->getAuth()->user();

        return new UserResponseModel($user, 'Успешный вход в систему!');
    }
}
