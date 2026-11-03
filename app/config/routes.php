<?php

declare(strict_types=1);

use App\Http\Controllers\AuthController;
use App\Http\Controllers\HomeController;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Models\User;

/** @var Router $router */
$router->get('/', [HomeController::class, 'index']);

// Маршруты аутентификации
$router->post('/register', [AuthController::class, 'register']);
$router->post('/login', [AuthController::class, 'login']);
$router->post('/logout', [AuthController::class, 'logout']);

// Для обратной совместимости (если форма отправлена на / с action=login/register/logout)
$router->post('/', function (Request $request): Response {
    $action = $request->input('action');
    if ($action === 'register') {
        return (new AuthController)->register($request);
    }
    if ($action === 'login') {
        return (new AuthController)->login($request);
    }
    if ($action === 'logout') {
        return (new AuthController)->logout();
    }

    return Response::redirect('/', 303);
});

// Пример группировки REST API с префиксом и параметризованными маршрутами
$router->group(['prefix' => '/api'], function (Router $api): void {
    $api->get('/health', function (): array {
        return [
            'status' => 'ok',
            'timestamp' => time(),
        ];
    });

    $api->get('/users/{id}', function (string $id): array {
        $user = User::find((int) $id);
        if ($user === null) {
            return ['error' => 'User not found'];
        }

        return [
            'id' => $user->user_id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    });
});
