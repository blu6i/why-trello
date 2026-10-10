<?php

declare(strict_types=1);

namespace App;

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

        $logger = new Logger($channel);

        // Папка для лог-файлов (app/storage/logs)
        $logDir = dirname(__DIR__).'/storage/logs';
        if (! is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        // Обработчик для записи в файл (уровень Debug и выше)
        $logger->pushHandler(new StreamHandler($logDir.'/app.log', Level::Debug));

        // Обработчик в поток php://stdout
        $logger->pushHandler(new StreamHandler('php://stdout', Level::Debug));

        self::$instance = $logger;

        return self::$instance;
    }
}
