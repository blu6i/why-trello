<?php

declare(strict_types=1);

return [
    'dev' => [
        'driver' => 'pgsql',
        'host' => getenv('DB_HOST') ?: 'postgres',
        'port' => (int) (getenv('DB_PORT') ?: 5432),
        'database' => getenv('DB_NAME') ?: 'app',
        'username' => getenv('DB_USER') ?: 'admin',
        'password' => getenv('DB_PASSWORD') ?: 'admin',
        'charset' => 'utf8',
        'schema' => 'public',
    ],
];
