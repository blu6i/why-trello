<?php

declare(strict_types=1);

use App\Http\Controllers\FrontendController;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;

/** @var Router $router */

// Главная страница
$router->get('/', [FrontendController::class, 'indexAction']);

// Маршруты аутентификации и регистрации пользователя
$router->post('/register', [FrontendController::class, 'registerAction']);
$router->post('/login', [FrontendController::class, 'loginAction']);
$router->post('/logout', [FrontendController::class, 'logoutAction']);

// Обратная совместимость для форм, отправляющих POST на "/" с полем action
$router->post('/', function (Request $request): Response {
    $action = $request->input('action');
    $frontend = new FrontendController;

    if ($action === 'register') {
        return $frontend->registerAction($request);
    }
    if ($action === 'login') {
        return $frontend->loginAction($request);
    }
    if ($action === 'logout') {
        return $frontend->logoutAction($request);
    }

    return Response::redirect('/', 303);
});

// Маршруты для досок и колонок (согласно спецификации)
$router->get('/boards', [FrontendController::class, 'boardListAction']);
$router->post('/boards/create', [FrontendController::class, 'createBoardAction']);
$router->post('/boards/edit', [FrontendController::class, 'editBoardAction']);
$router->get('/boards/delete', [FrontendController::class, 'deleteBoardAction']);
$router->get('/boards/{board_id}/columns', [FrontendController::class, 'boardColumnsAction']);

// Пример группировки REST API с префиксом и параметризованными маршрутами
$router->group(['prefix' => '/api'], function (Router $api): void {
    $api->get('/health', function (): array {
        return [
            'status' => 'ok',
            'timestamp' => time(),
        ];
    });

    $api->get('/users/{id}', function (string $id): array {
        $user = \App\Models\User::find((int) $id);
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
