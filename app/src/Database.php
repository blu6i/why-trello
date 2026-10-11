<?php

declare(strict_types=1);

namespace App;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Events\Dispatcher;
use PDO;

final class Database
{
    // public static function connect(): PDO
    // {
    //     $host = getenv('DB_HOST') ?: 'postgres';
    //     $port = getenv('DB_PORT') ?: '5432';
    //     $name = getenv('DB_NAME') ?: 'app';
    //     $user = getenv('DB_USER') ?: 'app';
    //     $password = getenv('DB_PASSWORD') ?: '';

    //     $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $name);

    //     return new PDO($dsn, $user, $password, [
    //         PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    //         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    //         // Не ждём дольше 3 секунд, если БД недоступна
    //         PDO::ATTR_TIMEOUT => 3,
    //     ]);
    // }
    private static ?Capsule $capsule = null;

    public static function init(): Capsule
    {
        if (self::$capsule !== null) {
            return self::$capsule;
        }

        self::$capsule = new Capsule;
        self::$capsule->addConnection([
            'driver' => Config::get('database.driver', 'pgsql'),
            'host' => Config::get('database.host', 'postgres'),
            'port' => Config::get('database.port', 5432),
            'database' => Config::get('database.database', 'app'),
            'username' => Config::get('database.username', 'admin'),
            'password' => Config::get('database.password', 'admin'),
            'charset' => Config::get('database.charset', 'utf8'),
            'prefix' => Config::get('database.prefix', ''),
            'schema' => Config::get('database.schema', 'public'),
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Не ждём дольше 3 секунд, если БД недоступна
                PDO::ATTR_TIMEOUT => 3,
            ],
        ]);

        self::$capsule->setEventDispatcher(new Dispatcher(new Container));
        self::$capsule->setAsGlobal();
        self::$capsule->bootEloquent();

        return self::$capsule;
    }

    public static function connect(): PDO
    {
        self::init();

        return Capsule::connection()->getPdo();
    }
}
