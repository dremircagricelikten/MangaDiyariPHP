<?php

namespace MangaDiyari\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;
    private static bool $bootstrapped = false;

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $config = require __DIR__ . '/../config.php';
            $path = $config['database_path'] ?? (__DIR__ . '/../data/manga.sqlite');
            $dir = dirname($path);

            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            try {
                self::$connection = new PDO('sqlite:' . $path);
                self::$connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::bootstrap(self::$connection, $config);
            } catch (PDOException $e) {
                throw new PDOException('Veritabanı bağlantısı kurulamadı: ' . $e->getMessage(), (int) $e->getCode(), $e);
            }
        }

        return self::$connection;
    }

    private static function bootstrap(PDO $pdo, array $config): void
    {
        if (self::$bootstrapped) {
            return;
        }

        self::$bootstrapped = true;

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

        $adminConfig = $config['admin'] ?? [];
        $adminEmail = strtolower(trim($adminConfig['email'] ?? 'admin@example.com'));
        $adminUsername = trim($adminConfig['username'] ?? 'admin');
        $adminPassword = $adminConfig['password'] ?? 'changeme';

        if ($adminEmail !== '' && $adminUsername !== '' && $adminPassword !== '') {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE LOWER(email) = LOWER(:email)');
            $stmt->execute([':email' => $adminEmail]);

            if ((int) $stmt->fetchColumn() === 0) {
                $now = (new \DateTimeImmutable())->format(\DateTimeImmutable::ATOM);

                $insert = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, created_at, updated_at)
                    VALUES (:username, :email, :password, :role, :created, :updated)');

                $insert->execute([
                    ':username' => $adminUsername,
                    ':email' => $adminEmail,
                    ':password' => password_hash($adminPassword, PASSWORD_DEFAULT),
                    ':role' => 'admin',
                    ':created' => $now,
                    ':updated' => $now,
                ]);
            }
        }

        $defaultTheme = [
            'primary_color' => '#5f2c82',
            'accent_color' => '#49a09d',
            'background_color' => '#05060c',
            'gradient_start' => '#5f2c82',
            'gradient_end' => '#49a09d',
        ];

        $settingStmt = $pdo->prepare('INSERT OR IGNORE INTO settings (key, value) VALUES (:key, :value)');
        foreach ($defaultTheme as $key => $value) {
            $settingStmt->execute([
                ':key' => $key,
                ':value' => $value,
            ]);
        }

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

        $widgetCheck = $pdo->prepare('SELECT id FROM widgets WHERE type = :type LIMIT 1');
        $widgetInsert = $pdo->prepare('INSERT INTO widgets (type, title, enabled, sort_order, config)
            VALUES (:type, :title, 1, :sort_order, :config)');

        foreach ($defaultWidgets as $type => $widget) {
            $widgetCheck->execute([':type' => $type]);

            if ($widgetCheck->fetchColumn() === false) {
                $widgetInsert->execute([
                    ':type' => $type,
                    ':title' => $widget['title'],
                    ':sort_order' => $widget['sort_order'],
                    ':config' => json_encode($widget['config'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ]);
            }
        }
    }
}
