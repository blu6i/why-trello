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
    </style>
</head>
<body>

    <header>
        <h1>why-trello</h1>
        <div class="auth-bar">
            <?php if ($currentUser !== null) { ?>
                <form method="POST" action="/logout" style="margin:0;">
                    <button type="submit" class="btn btn-danger">Выход</button>
                </form>
            <?php } ?>
        </div>
    </header>

    <?php if (! empty($flashSuccess)) { ?>
        <div class="alert alert-success"><?= htmlspecialchars($flashSuccess, ENT_QUOTES, 'UTF-8') ?></div>
    <?php } ?>

    <?php if (! empty($flashError)) { ?>
        <div class="alert alert-danger"><?= htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php } ?>

    <?php if ($currentUser === null) { ?>
        <!-- Формы Входа и Регистрации для неавторизованных пользователей -->
        <div class="auth-forms-container">
            <div class="card">
                <h2>Вход</h2>
                <form method="POST" action="/login">
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
                <form method="POST" action="/register">
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
                <dd><strong><?= htmlspecialchars($currentUser->name, ENT_QUOTES, 'UTF-8') ?></strong> (ID: <?= htmlspecialchars((string) $currentUser->user_id, ENT_QUOTES, 'UTF-8') ?>)</dd>

                <dt>Email</dt>
                <dd><?= htmlspecialchars($currentUser->email, ENT_QUOTES, 'UTF-8') ?></dd>
            </dl>
        </div>
    <?php } ?>

    <!-- Системная диагностика -->
    <div class="card">
        <h2>Диагностика системы и окружения</h2>
        <dl>
            <dt>Версия PHP</dt>
            <dd><?= htmlspecialchars($phpVersion, ENT_QUOTES, 'UTF-8') ?></dd>

            <dt>Расширение pdo_pgsql</dt>
            <dd class="<?= $hasPdoPgsql ? 'ok' : 'fail' ?>"><?= $hasPdoPgsql ? 'подключено' : 'отсутствует' ?></dd>

            <dt>Хост контейнера</dt>
            <dd><?= htmlspecialchars((string) $hostname, ENT_QUOTES, 'UTF-8') ?></dd>

            <dt>Подключение к БД</dt>
            <dd class="<?= $dbError === null ? 'ok' : 'fail' ?>">
                <?= $dbError === null ? htmlspecialchars($dbVersion ?? '', ENT_QUOTES, 'UTF-8') : 'ошибка: '.htmlspecialchars($dbError, ENT_QUOTES, 'UTF-8') ?>
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
                            <td><?= htmlspecialchars((string) $log->id, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($log->entity_type.' #'.($log->entity_id ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($log->action, ENT_QUOTES, 'UTF-8') ?></td>
                            <td><code><?= htmlspecialchars(json_encode($log->old_values, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: 'null', ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td><code><?= htmlspecialchars(json_encode($log->new_values, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: 'null', ENT_QUOTES, 'UTF-8') ?></code></td>
                            <td><?= htmlspecialchars((string) $log->created_at, ENT_QUOTES, 'UTF-8') ?></td>
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
