<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

use App\Database;
use App\Log;
use App\Schedule\Scheduler;

Database::init();

$scheduler = new Scheduler;

$scheduleConfigFile = __DIR__.'/../config/schedule.php';
if (file_exists($scheduleConfigFile)) {
    $configurator = require $scheduleConfigFile;
    if (is_callable($configurator)) {
        $configurator($scheduler);
    }
}

$args = $argv ?? [];

// Вывод списка задач
if (in_array('--list', $args, true)) {
    echo "Registered cron tasks:\n";
    echo str_repeat('-', 50)."\n";
    printf("%-25s | %s\n", 'Task Name', 'Expression');
    echo str_repeat('-', 50)."\n";
    foreach ($scheduler->getTasks() as $task) {
        printf("%-25s | %s\n", $task->getName(), $task->getExpression());
    }
    echo str_repeat('-', 50)."\n";
    exit(0);
}

// Принудительный запуск конкретной задачи по имени: --run=task_name
foreach ($args as $arg) {
    if (str_starts_with($arg, '--run=')) {
        $taskName = substr($arg, 6);
        echo "Running task [{$taskName}] immediately...\n";
        try {
            $result = $scheduler->runByName($taskName);
            echo "Finished with status: {$result['status']} ({$result['duration_ms']}ms)\n";
            exit($result['status'] === 'success' ? 0 : 1);
        } catch (Throwable $e) {
            echo "Error: {$e->getMessage()}\n";
            exit(1);
        }
    }
}

// Режим непрерывного демона (подходит для запуска в отдельном Docker-сервисе)
if (in_array('--daemon', $args, true)) {
    echo '['.date('Y-m-d H:i:s')."] Cron daemon started. Running scheduler every minute...\n";
    Log::get()->info('Cron daemon started.');

    $running = true;

    // Graceful shutdown по сигналам SIGTERM / SIGINT (если доступно расширение pcntl)
    if (function_exists('pcntl_async_signals') && function_exists('pcntl_signal')) {
        pcntl_async_signals(true);
        $stopHandler = function () use (&$running) {
            echo "\n[".date('Y-m-d H:i:s')."] Received stop signal, shutting down cron daemon gracefully...\n";
            Log::get()->info('Cron daemon received termination signal and stopped.');
            $running = false;
        };
        pcntl_signal(SIGTERM, $stopHandler);
        pcntl_signal(SIGINT, $stopHandler);
    }

    while ($running) {
        $now = new DateTimeImmutable('now');
        $executed = $scheduler->run($now);

        if (count($executed) > 0) {
            foreach ($executed as $name => $meta) {
                echo '['.date('Y-m-d H:i:s')."] Executed [{$name}]: {$meta['status']} ({$meta['duration_ms']}ms)\n";
            }
        }

        // Вычисляем задержку до начала следующей минуты (синхронизация по 00 секундам)
        $second = (int) date('s');
        $sleepSeconds = 60 - $second;
        if ($sleepSeconds <= 0) {
            $sleepSeconds = 60;
        }

        // Спим с шагом в 1 секунду для быстрого отклика на сигналы остановки
        for ($i = 0; $i < $sleepSeconds && $running; $i++) {
            sleep(1);
        }
    }

    echo '['.date('Y-m-d H:i:s')."] Cron daemon stopped.\n";
    exit(0);
}

// Разовый запуск (по умолчанию)
$executed = $scheduler->run();

if (count($executed) === 0) {
    echo "No scheduled tasks were due at this time.\n";
} else {
    foreach ($executed as $name => $meta) {
        echo "Executed [{$name}]: {$meta['status']} ({$meta['duration_ms']}ms)\n";
    }
}

exit(0);
