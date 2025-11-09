#!/usr/bin/env php
<?php

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require $autoload;
}

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/UserRepository.php';
require_once __DIR__ . '/../src/Slugger.php';
require_once __DIR__ . '/../src/MangaRepository.php';
require_once __DIR__ . '/../src/ChapterRepository.php';
require_once __DIR__ . '/../src/SettingRepository.php';
require_once __DIR__ . '/../src/WidgetRepository.php';

use MangaDiyari\Core\Database;
use MangaDiyari\Core\UserRepository;
use MangaDiyari\Core\SettingRepository;
use MangaDiyari\Core\WidgetRepository;

$pdo = Database::getConnection();

$pdo->exec('CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT "member",
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
)');

$pdo->exec('CREATE TABLE IF NOT EXISTS mangas (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    slug TEXT NOT NULL UNIQUE,
    description TEXT,
    cover_image TEXT,
    author TEXT,
    status TEXT,
    genres TEXT,
    tags TEXT,
    created_at TEXT,
    updated_at TEXT
)');

$pdo->exec('CREATE TABLE IF NOT EXISTS chapters (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    manga_id INTEGER NOT NULL,
    number TEXT NOT NULL,
    title TEXT,
    content TEXT,
    created_at TEXT,
    updated_at TEXT,
    FOREIGN KEY(manga_id) REFERENCES mangas(id) ON DELETE CASCADE
)');

$pdo->exec('CREATE TABLE IF NOT EXISTS settings (
    key TEXT PRIMARY KEY,
    value TEXT NOT NULL
)');

$pdo->exec('CREATE TABLE IF NOT EXISTS widgets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT NOT NULL,
    title TEXT NOT NULL,
    enabled INTEGER NOT NULL DEFAULT 1,
    sort_order INTEGER NOT NULL DEFAULT 0,
    config TEXT NOT NULL DEFAULT "{}"
)');

$config = require __DIR__ . '/../config.php';
$adminEmail = $config['admin']['email'] ?? 'admin@example.com';
$adminPassword = $config['admin']['password'] ?? 'changeme';
$adminUsername = $config['admin']['username'] ?? 'admin';

$userRepo = new UserRepository($pdo);
$settingRepo = new SettingRepository($pdo);
$widgetRepo = new WidgetRepository($pdo);

$admin = $userRepo->findByEmail($adminEmail);
if (!$admin) {
    try {
        $userRepo->create([
            'username' => $adminUsername,
            'email' => $adminEmail,
            'password' => $adminPassword,
            'role' => 'admin',
        ]);
        echo "Varsayılan yönetici hesabı oluşturuldu (" . $adminEmail . ").\n";
    } catch (Throwable $e) {
        echo "Yönetici hesabı oluşturulamadı: " . $e->getMessage() . "\n";
    }
}

$defaultTheme = [
    'primary_color' => '#5f2c82',
    'accent_color' => '#49a09d',
    'background_color' => '#05060c',
    'gradient_start' => '#5f2c82',
    'gradient_end' => '#49a09d',
];

$settingRepo->setMany(array_diff_key($defaultTheme, $settingRepo->all()));

$defaultWidgets = [
    'popular_slider' => [
        'title' => 'Popüler Seriler',
        'sort_order' => 1,
        'config' => [
            'limit' => 6,
            'sort' => 'random',
            'status' => '',
        ],
    ],
    'latest_updates' => [
        'title' => 'Yeni Yüklenen Bölümler',
        'sort_order' => 2,
        'config' => [
            'limit' => 8,
            'sort' => 'newest',
            'status' => '',
        ],
    ],
];

foreach ($defaultWidgets as $type => $widget) {
    if (!$widgetRepo->findByType($type)) {
        $widgetRepo->create([
            'type' => $type,
            'title' => $widget['title'],
            'enabled' => 1,
            'sort_order' => $widget['sort_order'],
            'config' => $widget['config'],
        ]);
    }
}

echo "Veritabanı tabloları hazır.\n";
