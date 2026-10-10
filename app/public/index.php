<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use App\Database;
use App\Log;
use App\Models\Demo;
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

// Обработка формы добавления записи. Редиректим после POST (Post/Redirect/Get),
// чтобы повторная отправка формы не происходила при обновлении страницы
if ($method === 'POST' && isset($_POST['note'])) {
    try {
        // $pdo = Database::connect();
        // $stmt = $pdo->prepare('INSERT INTO demo (note) VALUES (:note)');
        // $stmt->execute(['note' => (string) $_POST['note']]);
        Demo::create(['note' => (string) $_POST['note']]);
    } catch (Throwable $e) {
        // Ошибка подключения/записи — статус БД и так будет виден на странице ниже
    }
    header('Location: /');
    exit;
}

$phpVersion = PHP_VERSION;
$hasPdoPgsql = extension_loaded('pdo_pgsql');
$hostname = gethostname();

$dbVersion = null;
$dbError = null;
$rows = [];

try {
    $dbVersion = (string) Capsule::connection()->selectOne('SELECT version()')->version;
    $rows = Demo::orderBy('id', 'desc')->get();
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
    <title>nginx + php-fpm + PostgreSQL</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; color: #1c1c1c; }
        h1 { font-size: 1.4rem; }
        h2 { font-size: 1.1rem; margin-top: 2rem; }
        dl { display: grid; grid-template-columns: max-content 1fr; gap: 0.3rem 1rem; background: #f6f6f6; padding: 1rem; border-radius: 6px; }
        dt { font-weight: 600; }
        dd { margin: 0; }
        .ok { color: #0a7a2f; }
        .fail { color: #b00020; }
        table { border-collapse: collapse; width: 100%; margin: 0.5rem 0 1rem; }
        th, td { border: 1px solid #ddd; padding: 0.4rem 0.6rem; text-align: left; font-size: 0.9rem; }
        th { background: #f0f0f0; }
        form { display: flex; gap: 0.5rem; }
        input[type=text] { flex: 1; padding: 0.5rem; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 0.5rem 1.2rem; border: 0; border-radius: 4px; background: #2563eb; color: #fff; cursor: pointer; }
        button:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <h1>nginx + php-fpm + PostgreSQL</h1>

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

    <h2>Таблица demo</h2>
    <?php if (count($rows) > 0) { ?>
        <table>
            <tr><th>id</th><th>создано</th><th>note</th></tr>
            <?php foreach ($rows as $row) { ?>
                <tr>
                    <td><?= h((string) $row['id']) ?></td>
                    <td><?= h((string) $row['created_at']) ?></td>
                    <td><?= h((string) $row['note']) ?></td>
                </tr>
            <?php } ?>
        </table>
    <?php } else { ?>
        <p>Нет данных (таблица пуста или БД недоступна).</p>
    <?php } ?>

    <form method="post" action="/">
        <input type="text" name="note" placeholder="Новая запись" required maxlength="500">
        <button type="submit">Добавить</button>
    </form>
</body>
</html>

<!-- $responseBody = ob_get_clean(); -->