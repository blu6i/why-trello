<?php

declare(strict_types=1);

namespace App\Security;

use App\Models\User;

/**
 * Контракт сервиса аутентификации и авторизации пользователя.
 */
interface UserAuthInterface
{
    /**
     * Авторизация пользователя из текущей активной сессии PHP.
     *
     * @return User|null Возвращает модель пользователя, если сессия валидна, иначе null.
     */
    public function authorizeFromSession(): ?User;

    /**
     * Регистрация нового пользователя с автоматической авторизацией в сессии.
     *
     * @param  string  $name  Имя пользователя.
     * @param  string  $email  Уникальный адрес электронной почты.
     * @param  string  $password  Пароль в открытом виде (будет захеширован).
     * @return User Созданная и авторизованная модель пользователя.
     */
    public function register(string $name, string $email, string $password): User;

    /**
     * Авторизация (вход в систему) по учетным данным email и пароля.
     *
     * @param  string  $email  Email пользователя.
     * @param  string  $password  Введенный пароль.
     * @return bool True при успешной аутентификации, иначе false.
     */
    public function login(string $email, string $password): bool;

    /**
     * Разлогин (выход из системы) с очисткой данных сессии и куки.
     */
    public function logout(): void;

    /**
     * Проверка, авторизован ли текущий посетитель.
     *
     * @return bool True, если пользователь авторизован.
     */
    public function check(): bool;

    /**
     * Получение модели текущего авторизованного пользователя.
     *
     * @return User|null Модель пользователя или null.
     */
    public function user(): ?User;

    /**
     * Получение идентификатора текущего пользователя.
     *
     * @return int|null ID пользователя или null.
     */
    public function id(): ?int;
}
