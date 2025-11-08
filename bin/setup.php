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

use MangaDiyari\Core\Database;
use MangaDiyari\Core\UserRepository;

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

$config = require __DIR__ . '/../config.php';
$adminEmail = $config['admin']['email'] ?? 'admin@example.com';
$adminPassword = $config['admin']['password'] ?? 'changeme';
$adminUsername = $config['admin']['username'] ?? 'admin';

$userRepo = new UserRepository($pdo);

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

echo "Veritabanı tabloları hazır.\n";
