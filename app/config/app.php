<?php

declare(strict_types=1);

return [
    'dev' => [
        'name' => 'why-trello',
        'url' => 'http://127.0.0.1:8080',
        'timezone' => 'Europe/Moscow',
        'log_level' => 'debug',
        'log_path' => dirname(__DIR__).'/storage/logs/app.log',
        'session' => [
            'name' => 'PHPSESSID',
            'lifetime' => 7200, // 2 часа (в секундах)
            'path' => '/',
            'domain' => '',
            'secure' => false, // true для production с HTTPS
            'httponly' => true, // защита от XSS
            'samesite' => 'Lax', // защита от CSRF
        ],
    ],
];
