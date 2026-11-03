<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use App\Database;
use App\Http\Request;
use App\Http\Router;
use App\Log;
use App\Security\SessionManager;

$startTime = microtime(true);
$request = Request::fromGlobals();

ob_start();

register_shutdown_function(function () use ($startTime, $request) {
    // Забираем сгенерированный HTML/JSON из буфера
    $responseBody = ob_get_clean();
    $status = http_response_code();
    $durationMs = round((microtime(true) - $startTime) * 1000, 2);

    // Логируем в Monolog со всеми деталями
    Log::get()->info("HTTP Request: {$request->getMethod()} {$request->getUri()} -> {$status}", [
        'method' => $request->getMethod(),
        'uri' => $request->getUri(),
        'status' => $status,
        'duration_ms' => $durationMs,
        'response_body' => $responseBody ?: '(empty body/redirect)',
        'ip' => $request->ip(),
    ]);

    // Отправляем тело клиенту в браузер
    echo $responseBody;
});

// Инициализация соединения с PostgreSQL
Database::init();

// Инициализация механизма сессий
SessionManager::start();

// Создание и конфигурация маршрутизатора
$router = new Router;

$routesFile = __DIR__.'/../config/routes.php';
if (file_exists($routesFile)) {
    require $routesFile;
}

// Диспетчеризация запроса и отправка ответа
$response = $router->dispatch($request);
$response->send();
