<?php

declare(strict_types=1);

namespace App;

use DateTimeZone;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

final class Log
{
    private static ?Logger $instance = null;

    public static function get(string $channel = 'http'): Logger
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $timezone = new DateTimeZone(Config::get('app.timezone', 'UTC'));
        $logger = new Logger($channel, timezone: $timezone);

        $logPath = Config::get('app.log_path', dirname(__DIR__).'/storage/logs/app.log');
        $logDir = dirname($logPath);
        if (! is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $logLevelName = Config::get('app.log_level', 'debug');
        $level = Level::fromName($logLevelName);

        // Обработчик для записи в файл
        $logger->pushHandler(new StreamHandler($logPath, $level));

        // Обработчик в поток php://stdout
        $logger->pushHandler(new StreamHandler('php://stdout', $level));

        self::$instance = $logger;

        return self::$instance;
    }
}
