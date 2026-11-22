<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Http\Requests\AbstractRequest;
use App\Http\Responses\ResponseModelInterface;
use App\Http\Responses\UserResponseModel;
use App\Http\Validation\Rules\EmailValidator;
use App\Http\Validation\Rules\MinLengthValidator;
use App\Http\Validation\Rules\RequiredValidator;
use App\Http\Validation\Rules\StringValidator;
use App\Models\User;
use App\Security\UserSessionAuth;

/**
 * Класс запроса регистрации нового пользователя.
 *
 * Реализует:
 * 1. Валидацию параметров запроса (имя, email, пароль).
 * 2. Валидацию бизнес-логики (проверка уникальности email в БД).
 * 3. Выполнение запроса (сохранение пользователя и авторизация в сессии).
 */
final class RegisterRequest extends AbstractRequest
{
    private ?UserSessionAuth $auth = null;

    /**
     * Сеттер сервиса авторизации (опционально для внедрения зависимостей).
     *
     * @param  UserSessionAuth  $auth  Сервис авторизации.
     */
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
     * Правила валидации параметров для регистрации.
     *
     * @return array<string, mixed> Набор правил.
     */
    protected function rules(): array
    {
        return [
            'name' => [
                new RequiredValidator,
                new StringValidator,
                new MinLengthValidator(2),
            ],
            'email' => [
                new RequiredValidator,
                new EmailValidator,
            ],
            'password' => [
                new RequiredValidator,
                new MinLengthValidator(6),
            ],
        ];
    }

    /**
     * Валидация логики: проверка уникальности Email в базе данных PostgreSQL.
     *
     * @return bool True, если пользователь с таким email еще не зарегистрирован.
     */
    public function validateLogic(): bool
    {
        $email = trim((string) $this->getParam('email', ''));

        if (User::where('email', $email)->exists()) {
            $this->errors['logic'] = 'Пользователь с таким Email уже зарегистрирован.';

            return false;
        }

        return true;
    }

    /**
     * Выполнение полезной нагрузки: создание пользователя и открытие сессии.
     *
     * @return ResponseModelInterface Модель ответа с данными созданного пользователя.
     */
    public function execute(): ResponseModelInterface
    {
        $name = trim((string) $this->getParam('name', ''));
        $email = trim((string) $this->getParam('email', ''));
        $password = (string) $this->getParam('password', '');

        $user = $this->getAuth()->register($name, $email, $password);

        return new UserResponseModel(
            $user,
            'Регистрация прошла успешно! Вы авторизованы.',
            201
        );
    }
}
