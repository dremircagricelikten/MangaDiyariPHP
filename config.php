<?php
return [
    'database' => [
        'driver' => getenv('DB_DRIVER') ?: 'mysql',
        'mysql' => [
            'host' => getenv('DB_HOST') ?: '127.0.0.1',
            'port' => getenv('DB_PORT') ?: '3306',
            'database' => getenv('DB_NAME') ?: 'mangadiyari',
            'username' => getenv('DB_USER') ?: 'root',
            'password' => getenv('DB_PASS') ?: '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [],
            'fallback_sqlite_path' => __DIR__ . '/data/manga.sqlite',
        ],
        'sqlite' => [
            'path' => __DIR__ . '/data/manga.sqlite',
        ],
    ],
    'site' => [
        'name' => 'Manga Diyarı',
        'tagline' => 'Sevdiğiniz mangaları okuyun ve keşfedin',
    ],
    'admin' => [
        'username' => 'admin',
        'email' => 'admin@example.com',
        'password' => 'changeme',
    ],
];
