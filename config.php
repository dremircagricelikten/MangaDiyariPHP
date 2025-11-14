<?php
return [
    'database' => [
        'driver' => getenv('DB_DRIVER') ?: 'mysql',
        'mysql' => [
            'host' => getenv('DB_HOST') ?: 'localhost',
            'port' => getenv('DB_PORT') ?: '3306',
            'database' => getenv('DB_NAME') ?: 'md_deneme',
            'username' => getenv('DB_USER') ?: 'md_deneme',
            'password' => getenv('DB_PASS') ?: '1Jankenguuu...!',
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
