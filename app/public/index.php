<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use App\Database;
use App\Log;
use App\Models\ActivityLog;
use App\Models\User;
use App\Security\Auth;
use App\Security\SessionManager;
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

// Инициализация безопасной сессии с сохранением в PostgreSQL и отправкой куки в браузер
SessionManager::start();

// Обработка POST-запросов авторизации / регистрации / выхода (Post-Redirect-Get)
if ($method === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = filter_var(trim((string) ($_POST['email'] ?? '')), FILTER_VALIDATE_EMAIL);
        $password = (string) ($_POST['password'] ?? '');

        if ($name === '' || ! $email || strlen($password) < 6) {
            $_SESSION['flash_error'] = 'Заполните все поля корректно (пароль от 6 символов).';
        } elseif (User::where('email', $email)->exists()) {
            $_SESSION['flash_error'] = 'Пользователь с таким Email уже зарегистрирован.';
        } else {
            try {
                Auth::register($name, $email, $password);
                $_SESSION['flash_success'] = 'Регистрация прошла успешно! Вы авторизованы.';
            } catch (Throwable $e) {
                $_SESSION['flash_error'] = 'Ошибка регистрации: '.$e->getMessage();
            }
        }

        header('Location: /', true, 303);
        exit;
    }

    if ($action === 'login') {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            $_SESSION['flash_error'] = 'Укажите Email и пароль.';
        } elseif (Auth::attempt($email, $password)) {
            $_SESSION['flash_success'] = 'Успешный вход в систему!';
        } else {
            $_SESSION['flash_error'] = 'Неверный Email или пароль.';
        }

        header('Location: /', true, 303);
        exit;
    }

    if ($action === 'logout') {
        Auth::logout();
        header('Location: /?logged_out=1', true, 303);
        exit;
    }
}

// Забираем и очищаем Flash-сообщения
$flashError = $_SESSION['flash_error'] ?? null;
$flashSuccess = $_SESSION['flash_success'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_success']);

if (isset($_GET['logged_out'])) {
    $flashSuccess = 'Вы успешно вышли из системы. Сессионная кука удалена.';
}

$currentUser = Auth::user();

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
        :root {
            --primary: #026aa7;
            --primary-hover: #055a8c;
            --bg: #f4f5f7;
            --card-bg: #ffffff;
            --text: #172b4d;
            --border: #dfe1e6;
            --success: #0a7a2f;
            --danger: #b00020;
            --info: #0747a6;
        }
        * { box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 960px; margin: 2rem auto; padding: 0 1rem; color: var(--text); background: var(--bg); line-height: 1.5; }
        header { display: flex; justify-content: space-between; align-items: center; background: var(--card-bg); padding: 1rem 1.5rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 1.5rem; }
        h1 { margin: 0; font-size: 1.5rem; color: var(--primary); }
        h2 { font-size: 1.15rem; margin-top: 1.5rem; margin-bottom: 0.75rem; }
        .card { background: var(--card-bg); padding: 1.25rem; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 1.5rem; border: 1px solid var(--border); }
        .alert { padding: 0.75rem 1rem; border-radius: 6px; margin-bottom: 1rem; font-weight: 500; font-size: 0.95rem; }
        .alert-success { background: #e3fcef; color: var(--success); border: 1px solid #abf5d1; }
        .alert-danger { background: #ffebe6; color: var(--danger); border: 1px solid #ffbdad; }
        
        .auth-bar { display: flex; align-items: center; gap: 1rem; }
        .auth-user-info { font-size: 0.95rem; }
        .auth-user-info strong { color: var(--primary); }
        
        .btn { display: inline-block; padding: 0.5rem 1rem; font-size: 0.9rem; font-weight: 600; border-radius: 4px; border: none; cursor: pointer; text-decoration: none; transition: background 0.2s; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { background: #8e0018; }
        .btn-secondary { background: #ebecf0; color: #172b4d; }
        .btn-secondary:hover { background: #dfe1e6; }
        
        .auth-forms-container { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        @media (max-width: 700px) {
            .auth-forms-container { grid-template-columns: 1fr; }
            header { flex-direction: column; align-items: flex-start; gap: 1rem; }
        }
        
        .form-group { margin-bottom: 0.9rem; }
        .form-group label { display: block; font-size: 0.85rem; font-weight: 600; margin-bottom: 0.3rem; }
        .form-group input { width: 100%; padding: 0.5rem 0.65rem; border: 1px solid var(--border); border-radius: 4px; font-size: 0.9rem; }
        .form-group input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 2px rgba(2, 106, 167, 0.2); }
        
        .badge { display: inline-block; padding: 0.2rem 0.5rem; border-radius: 12px; font-size: 0.75rem; font-weight: 600; text-transform: uppercase; }
        .badge-success { background: #e3fcef; color: var(--success); }
        .badge-info { background: #deebff; color: var(--info); }
        
        dl { display: grid; grid-template-columns: max-content 1fr; gap: 0.4rem 1.2rem; margin: 0; }
        dt { font-weight: 600; color: #5e6c84; }
        dd { margin: 0; }
        .ok { color: var(--success); font-weight: 600; }
        .fail { color: var(--danger); font-weight: 600; }
        
        table { border-collapse: collapse; width: 100%; font-size: 0.85rem; }
        th, td { border: 1px solid var(--border); padding: 0.5rem 0.75rem; text-align: left; vertical-align: top; }
        th { background: #f4f5f7; color: #5e6c84; font-weight: 600; }
        tr:nth-child(even) td { background: #fafbfc; }
        code { font-family: ui-monospace, Menlo, Monaco, monospace; font-size: 0.8rem; background: #ebecf0; padding: 2px 4px; border-radius: 3px; word-break: break-all; }
        .session-info { font-size: 0.85rem; color: #5e6c84; margin-top: 0.5rem; }
    </style>
</head>
<body>

    <header>
        <h1>why-trello</h1>
        <div class="auth-bar">
            <?php if ($currentUser !== null) { ?>
                <form method="POST" action="/" style="margin:0;">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="btn btn-danger">Выход</button>
                </form>
            <?php } ?>
        </div>
    </header>

    <?php if ($flashSuccess !== null) { ?>
        <div class="alert alert-success"><?= h($flashSuccess) ?></div>
    <?php } ?>

    <?php if ($flashError !== null) { ?>
        <div class="alert alert-danger"><?= h($flashError) ?></div>
    <?php } ?>

    <?php if ($currentUser === null) { ?>
        <!-- Формы Входа и Регистрации для неавторизованных пользователей -->
        <div class="auth-forms-container">
            <div class="card">
                <h2>Вход</h2>
                <form method="POST" action="/">
                    <input type="hidden" name="action" value="login">
                    <div class="form-group">
                        <label for="login-email">Email:</label>
                        <input type="email" id="login-email" name="email" required autocomplete="email">
                    </div>
                    <div class="form-group">
                        <label for="login-password">Пароль:</label>
                        <input type="password" id="login-password" name="password" required autocomplete="current-password">
                    </div>
                    <button type="submit" class="btn btn-primary">Войти</button>
                </form>
            </div>

            <div class="card">
                <h2>Регистрация</h2>
                <form method="POST" action="/">
                    <input type="hidden" name="action" value="register">
                    <div class="form-group">
                        <label for="reg-name">Имя:</label>
                        <input type="text" id="reg-name" name="name" required autocomplete="name">
                    </div>
                    <div class="form-group">
                        <label for="reg-email">Email:</label>
                        <input type="email" id="reg-email" name="email" required autocomplete="email">
                    </div>
                    <div class="form-group">
                        <label for="reg-password">Пароль (мин. 6 символов):</label>
                        <input type="password" id="reg-password" name="password" minlength="6" required autocomplete="new-password">
                    </div>
                    <button type="submit" class="btn btn-secondary">Зарегистрироваться</button>
                </form>
            </div>
        </div>
    <?php } else { ?>
        <!-- Карточка активной сессии пользователя -->
        <div class="card">
            <h2>Информация о текущей сессии</h2>
            <dl>
                <dt>Пользователь</dt>
                <dd><strong><?= h($currentUser->name) ?></strong> (ID: <?= h((string) $currentUser->user_id) ?>)</dd>

                <dt>Email</dt>
                <dd><?= h($currentUser->email) ?></dd>
            </dl>
        </div>
    <?php } ?>

    <!-- Системная диагностика -->
    <div class="card">
        <h2>Диагностика системы и окружения</h2>
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
    </div>

    <!-- Журнал аудита базы данных -->
    <div class="card">
        <h2>Журнал аудита базы данных (activity_logs)</h2>
        <?php if (count($activityLogs) > 0) { ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Сущность</th>
                        <th>Действие</th>
                        <th>ДО (old_values)</th>
                        <th>ПОСЛЕ (new_values)</th>
                        <th>Время</th>
                    </tr>
                </thead>
                <tbody>
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
                </tbody>
            </table>
        <?php } else { ?>
            <p>Записей аудита пока нет.</p>
        <?php } ?>
    </div>

</body>
</html>
