<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Request;
use App\Http\Response;
use App\Models\User;
use App\Security\Auth;
use Throwable;

final class AuthController extends AbstractController
{
    public function register(Request $request): Response
    {
        $name = trim((string) $request->input('name', ''));
        $email = filter_var(trim((string) $request->input('email', '')), FILTER_VALIDATE_EMAIL);
        $password = (string) $request->input('password', '');

        if ($name === '' || ! $email || strlen($password) < 6) {
            $this->flash('error', 'Заполните все поля корректно (пароль от 6 символов).');
        } elseif (User::where('email', $email)->exists()) {
            $this->flash('error', 'Пользователь с таким Email уже зарегистрирован.');
        } else {
            try {
                Auth::register($name, (string) $email, $password);
                $this->flash('success', 'Регистрация прошла успешно! Вы авторизованы.');
            } catch (Throwable $e) {
                $this->flash('error', 'Ошибка регистрации: '.$e->getMessage());
            }
        }

        return $this->redirect('/', 303);
    }

    public function login(Request $request): Response
    {
        $email = trim((string) $request->input('email', ''));
        $password = (string) $request->input('password', '');

        if ($email === '' || $password === '') {
            $this->flash('error', 'Укажите Email и пароль.');
        } elseif (Auth::attempt($email, $password)) {
            $this->flash('success', 'Успешный вход в систему!');
        } else {
            $this->flash('error', 'Неверный Email или пароль.');
        }

        return $this->redirect('/', 303);
    }

    public function logout(): Response
    {
        Auth::logout();

        return $this->redirect('/?logged_out=1', 303);
    }
}
