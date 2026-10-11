<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use App\Database;
use App\Log;
use App\Models\ActivityLog;
use Illuminate\Database\Capsule\Manager as Capsule;

$startTime = microtime(true);
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri = $_SERVER['REQUEST_URI'] ?? '/';

ob_start();

register_shutdown_function(function () use ($startTime, $method, $uri) {
    // Забираем сгенерированный HTML из буфера
    $responseBody = ob_get_clean();
    $status = http_response_code();
    $durationMs = round((microtime(true) - $startTime) * 1000, 2);
    // Логируем в Monolog со всеми деталями
    Log::get()->info("HTTP Request: {$method} {$uri} -> {$status}", [
        'method' => $method,
        'uri' => $uri,
        'status' => $status,
        'duration_ms' => $durationMs,
        'response_body' => $responseBody ?: '(empty body/redirect)',
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
    ]);
    // Отправляем тело клиенту в браузер
    echo $responseBody;
});

Database::init();

$phpVersion = PHP_VERSION;
$hasPdoPgsql = extension_loaded('pdo_pgsql');
$hostname = gethostname();

$dbVersion = null;
$dbError = null;
$activityLogs = [];

try {
    $dbVersion = (string) Capsule::connection()->selectOne('SELECT version()')->version;
    $activityLogs = ActivityLog::orderBy('id', 'desc')->take(20)->get();
} catch (Throwable $e) {
    $dbError = $e->getMessage();
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>why-trello</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 900px; margin: 2rem auto; padding: 0 1rem; color: #1c1c1c; }
        h1 { font-size: 1.4rem; }
        h2 { font-size: 1.1rem; margin-top: 2rem; }
        dl { display: grid; grid-template-columns: max-content 1fr; gap: 0.3rem 1rem; background: #f6f6f6; padding: 1rem; border-radius: 6px; }
        dt { font-weight: 600; }
        dd { margin: 0; }
        .ok { color: #0a7a2f; }
        .fail { color: #b00020; }
        table { border-collapse: collapse; width: 100%; margin: 0.5rem 0 1rem; }
        th, td { border: 1px solid #ddd; padding: 0.4rem 0.6rem; text-align: left; font-size: 0.85rem; vertical-align: top; }
        th { background: #f0f0f0; }
        code { font-family: monospace; font-size: 0.8rem; background: #f8fafc; padding: 2px 4px; border-radius: 3px; display: block; white-space: pre-wrap; word-break: break-all; }
    </style>
</head>
<body>
    <h1>why-trello</h1>

    <dl>
        <dt>Версия PHP</dt>
        <dd><?= h($phpVersion) ?></dd>

        <dt>Расширение pdo_pgsql</dt>
        <dd class="<?= $hasPdoPgsql ? 'ok' : 'fail' ?>"><?= $hasPdoPgsql ? 'подключено' : 'отсутствует' ?></dd>

        <dt>Хост контейнера</dt>
        <dd><?= h((string) $hostname) ?></dd>

        <dt>Подключение к БД</dt>
        <dd class="<?= $dbError === null ? 'ok' : 'fail' ?>">
            <?= $dbError === null ? h($dbVersion ?? '') : 'ошибка: '.h($dbError) ?>
        </dd>
    </dl>

    <h2>Журнал аудита базы данных (activity_logs)</h2>
    <?php if (count($activityLogs) > 0) { ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Сущность</th>
                <th>Действие</th>
                <th>ДО (old_values)</th>
                <th>ПОСЛЕ (new_values)</th>
                <th>Время</th>
            </tr>
            <?php foreach ($activityLogs as $log) { ?>
                <tr>
                    <td><?= h((string) $log->id) ?></td>
                    <td><?= h($log->entity_type.' #'.($log->entity_id ?? '')) ?></td>
                    <td><?= h($log->action) ?></td>
                    <td><code><?= h(json_encode($log->old_values, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: 'null') ?></code></td>
                    <td><code><?= h(json_encode($log->new_values, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: 'null') ?></code></td>
                    <td><?= h((string) $log->created_at) ?></td>
                </tr>
            <?php } ?>
        </table>
    <?php } else { ?>
        <p>Записей аудита пока нет.</p>
    <?php } ?>
</body>
</html>

<!-- $responseBody = ob_get_clean(); -->