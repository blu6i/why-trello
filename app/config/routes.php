<?php

declare(strict_types=1);

use App\Http\Controllers\FrontendController;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\CsrfMiddleware;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Models\User;
use App\Security\Auth;

/** @var Router $router */

// Главная страница
$router->get('/', [FrontendController::class, 'indexAction']);

// Маршруты аутентификации и регистрации пользователя (все операции записи защищены CSRF)
$router->group(['middleware' => CsrfMiddleware::class], function (Router $csrf): void {
    $csrf->post('/register', [FrontendController::class, 'registerAction']);
    $csrf->post('/login', [FrontendController::class, 'loginAction']);
    $csrf->post('/logout', [FrontendController::class, 'logoutAction']);

    // Обратная совместимость для форм, отправляющих POST на "/" с полем action
    $csrf->post('/', function (Request $request): Response {
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
});

// Маршруты для досок и колонок (проверка авторизации и CSRF для операций записи)
$router->group(['prefix' => '/boards', 'middleware' => [AuthMiddleware::class, CsrfMiddleware::class]], function (Router $boards): void {
    $boards->get('/', [FrontendController::class, 'boardListAction']);
    $boards->post('/create', [FrontendController::class, 'createBoardAction']);
    $boards->post('/edit', [FrontendController::class, 'editBoardAction']);
    $boards->delete('/{board_id}', [FrontendController::class, 'deleteBoardAction']);
    $boards->get('/{board_id}/columns', [FrontendController::class, 'boardColumnsAction']);
});

// REST API с префиксом /api
$router->group(['prefix' => '/api'], function (Router $api): void {
    // Эндпоинты для React SPA (FrontendController)
    $api->group(['prefix' => '/frontend', 'middleware' => CsrfMiddleware::class], function (Router $frontendApi): void {
        $frontendApi->get('/user', [FrontendController::class, 'userAction']);
        $frontendApi->post('/login', [FrontendController::class, 'loginAction']);
        $frontendApi->post('/register', [FrontendController::class, 'registerAction']);
        $frontendApi->post('/logout', [FrontendController::class, 'logoutAction']);
    });

    $api->get('/health', function (): array {
        return [
            'status' => 'ok',
            'timestamp' => time(),
        ];
    });

    // Профиль пользователя: только для авторизованных и только свой собственный ID
    $api->get('/users/{id}', function (string $id): Response {
        if (Auth::id() !== (int) $id) {
            return Response::json(['error' => 'Forbidden'], 403);
        }

        $user = User::find((int) $id);
        if ($user === null) {
            return Response::json(['error' => 'User not found'], 404);
        }

        return Response::json([
            'id' => $user->user_id,
            'name' => $user->name,
            'email' => $user->email,
        ]);
    })->middleware(AuthMiddleware::class);
});
