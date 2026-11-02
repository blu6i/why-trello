<?php

declare(strict_types=1);

use App\Log;
use App\Schedule\Scheduler;
use App\Schedule\Tasks\SessionGcTask;

return static function (Scheduler $schedule): void {
    // Очистка устаревших сессий в PostgreSQL каждые 15 минут
    $schedule->call(new SessionGcTask)->everyFifteenMinutes();

    // Heartbeat: периодическая проверка работоспособности планировщика (раз в час)
    $schedule->call(function (): void {
        Log::get()->debug('Cron daemon heartbeat: scheduler is active and working.');
    }, 'system:heartbeat')->hourly();
};
