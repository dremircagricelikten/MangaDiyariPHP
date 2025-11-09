<?php

namespace MangaDiyari\Core;

use DateTimeImmutable;
use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;
    private static bool $bootstrapped = false;
    private static string $driver = 'mysql';

    public static function getConnection(): PDO
    {
        if (self::$connection === null) {
            $config = require __DIR__ . '/../config.php';
            $databaseConfig = $config['database'] ?? [];

            [$pdo, $driver] = self::connect($databaseConfig);
            self::$connection = $pdo;
            self::$driver = $driver;

            self::bootstrap(self::$connection, $config, $driver);
        }

        return self::$connection;
    }

    public static function getDriver(): string
    {
        return self::$driver;
    }

    /**
     * @param array<string, mixed> $databaseConfig
     * @return array{0: PDO, 1: string}
     */
    private static function connect(array $databaseConfig): array
    {
        $driver = strtolower($databaseConfig['driver'] ?? 'mysql');

        if ($driver === 'sqlite') {
            return [self::connectSqlite($databaseConfig['sqlite'] ?? []), 'sqlite'];
        }

        try {
            return [self::connectMysql($databaseConfig['mysql'] ?? []), 'mysql'];
        } catch (PDOException $exception) {
            $mysqlConfig = $databaseConfig['mysql'] ?? [];
            $fallbackPath = $mysqlConfig['fallback_sqlite_path'] ?? null;
            if ($fallbackPath) {
                return [self::connectSqlite(['path' => $fallbackPath]), 'sqlite'];
            }

            throw $exception;
        }
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function connectMysql(array $config): PDO
    {
        $host = $config['host'] ?? '127.0.0.1';
        $port = $config['port'] ?? '3306';
        $database = $config['database'] ?? 'mangadiyari';
        $charset = $config['charset'] ?? 'utf8mb4';
        $username = $config['username'] ?? 'root';
        $password = $config['password'] ?? '';
        $options = $config['options'] ?? [];

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $database, $charset);

        $defaults = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        $pdo = new PDO($dsn, $username, $password, $defaults + $options);
        $collation = $config['collation'] ?? 'utf8mb4_unicode_ci';
        $pdo->exec("SET NAMES '{$charset}' COLLATE '{$collation}'");

        return $pdo;
    }

    /**
     * @param array<string, mixed> $config
     */
    private static function connectSqlite(array $config): PDO
    {
        $path = $config['path'] ?? (__DIR__ . '/../data/manga.sqlite');
        $dir = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $pdo = new PDO('sqlite:' . $path);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        return $pdo;
    }

    private static function bootstrap(PDO $pdo, array $config, string $driver): void
    {
        if (self::$bootstrapped) {
            return;
        }

        self::$bootstrapped = true;

        if ($driver === 'mysql') {
            self::bootstrapMysql($pdo);
        } else {
            self::bootstrapSqlite($pdo);
        }

        self::seedDefaults($pdo, $config, $driver);
    }

    private static function bootstrapMysql(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(191) NOT NULL UNIQUE,
            email VARCHAR(191) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(32) NOT NULL DEFAULT "member",
            bio TEXT NULL,
            avatar_url TEXT NULL,
            website_url TEXT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $pdo->exec('CREATE TABLE IF NOT EXISTS mangas (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL UNIQUE,
            description TEXT NULL,
            cover_image TEXT NULL,
            author VARCHAR(255) NULL,
            status VARCHAR(32) NULL,
            genres TEXT NULL,
            tags TEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $pdo->exec('CREATE TABLE IF NOT EXISTS chapters (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            manga_id INT UNSIGNED NOT NULL,
            number VARCHAR(32) NOT NULL,
            title VARCHAR(255) NULL,
            content LONGTEXT NULL,
            assets LONGTEXT NULL,
            created_at DATETIME NULL,
            updated_at DATETIME NULL,
            CONSTRAINT fk_chapters_manga FOREIGN KEY (manga_id) REFERENCES mangas(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $pdo->exec('CREATE TABLE IF NOT EXISTS settings (
            `key` VARCHAR(191) NOT NULL PRIMARY KEY,
            value LONGTEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $pdo->exec('CREATE TABLE IF NOT EXISTS widgets (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(191) NOT NULL UNIQUE,
            title VARCHAR(255) NOT NULL,
            enabled TINYINT(1) NOT NULL DEFAULT 1,
            sort_order INT NOT NULL DEFAULT 0,
            config LONGTEXT NOT NULL DEFAULT "{}"
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $pdo->exec('CREATE TABLE IF NOT EXISTS menus (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(191) NOT NULL,
            location VARCHAR(191) NOT NULL UNIQUE,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');

        $pdo->exec('CREATE TABLE IF NOT EXISTS menu_items (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            menu_id INT UNSIGNED NOT NULL,
            label VARCHAR(255) NOT NULL,
            url TEXT NOT NULL,
            target VARCHAR(32) NULL,
            sort_order INT NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            CONSTRAINT fk_menu_items_menu FOREIGN KEY (menu_id) REFERENCES menus(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci');
    }

    private static function bootstrapSqlite(PDO $pdo): void
    {
        $pdo->exec('CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            email TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL,
            role TEXT NOT NULL DEFAULT "member",
            bio TEXT,
            avatar_url TEXT,
            website_url TEXT,
            is_active INTEGER NOT NULL DEFAULT 1,
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
            assets TEXT,
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
            type TEXT NOT NULL UNIQUE,
            title TEXT NOT NULL,
            enabled INTEGER NOT NULL DEFAULT 1,
            sort_order INTEGER NOT NULL DEFAULT 0,
            config TEXT NOT NULL DEFAULT "{}"
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS menus (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            location TEXT NOT NULL UNIQUE,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL
        )');

        $pdo->exec('CREATE TABLE IF NOT EXISTS menu_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            menu_id INTEGER NOT NULL,
            label TEXT NOT NULL,
            url TEXT NOT NULL,
            target TEXT,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL,
            updated_at TEXT NOT NULL,
            FOREIGN KEY(menu_id) REFERENCES menus(id) ON DELETE CASCADE
        )');
    }

    private static function seedDefaults(PDO $pdo, array $config, string $driver): void
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $adminConfig = $config['admin'] ?? [];
        $adminEmail = strtolower(trim($adminConfig['email'] ?? 'admin@example.com'));
        $adminUsername = trim($adminConfig['username'] ?? 'admin');
        $adminPassword = $adminConfig['password'] ?? 'changeme';

        if ($adminEmail && $adminUsername && $adminPassword) {
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE LOWER(email) = LOWER(:email)');
            $stmt->execute([':email' => $adminEmail]);

            if ((int) $stmt->fetchColumn() === 0) {
                $insert = $pdo->prepare('INSERT INTO users (username, email, password_hash, role, bio, avatar_url, website_url, is_active, created_at, updated_at)
                    VALUES (:username, :email, :password, :role, :bio, :avatar, :website, 1, :created, :updated)');

                $insert->execute([
                    ':username' => $adminUsername,
                    ':email' => $adminEmail,
                    ':password' => password_hash($adminPassword, PASSWORD_DEFAULT),
                    ':role' => 'admin',
                    ':bio' => '',
                    ':avatar' => '',
                    ':website' => '',
                    ':created' => $now,
                    ':updated' => $now,
                ]);
            }
        }

        $settings = [
            'primary_color' => '#5f2c82',
            'accent_color' => '#49a09d',
            'background_color' => '#05060c',
            'gradient_start' => '#5f2c82',
            'gradient_end' => '#49a09d',
            'site_name' => $config['site']['name'] ?? 'Manga Diyarı',
            'site_tagline' => $config['site']['tagline'] ?? 'Sevdiğiniz mangaları okuyun ve keşfedin',
            'ad_header' => '',
            'ad_sidebar' => '',
            'ad_footer' => '',
            'analytics_google' => '',
            'analytics_search_console' => '',
        ];

        foreach ($settings as $key => $value) {
            self::upsertSetting($pdo, $driver, (string) $key, (string) $value);
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

        $menuCheck = $pdo->prepare('SELECT id FROM menus WHERE location = :location LIMIT 1');
        $menuInsert = $pdo->prepare('INSERT INTO menus (name, location, created_at, updated_at) VALUES (:name, :location, :created_at, :updated_at)');
        $menuItemInsert = $pdo->prepare('INSERT INTO menu_items (menu_id, label, url, target, sort_order, created_at, updated_at)
            VALUES (:menu_id, :label, :url, :target, :sort_order, :created_at, :updated_at)');

        $defaultMenus = [
            'primary' => [
                'name' => 'Üst Menü',
                'items' => [
                    ['label' => 'Anasayfa', 'url' => '/', 'target' => '_self'],
                    ['label' => 'Koleksiyon', 'url' => '/#yeniler', 'target' => '_self'],
                ],
            ],
            'footer' => [
                'name' => 'Alt Menü',
                'items' => [
                    ['label' => 'İletişim', 'url' => 'mailto:admin@example.com', 'target' => '_self'],
                    ['label' => 'Gizlilik', 'url' => '#', 'target' => '_self'],
                ],
            ],
        ];

        foreach ($defaultMenus as $location => $menu) {
            $menuCheck->execute([':location' => $location]);
            $menuId = $menuCheck->fetchColumn();

            if ($menuId === false) {
                $menuInsert->execute([
                    ':name' => $menu['name'],
                    ':location' => $location,
                    ':created_at' => $now,
                    ':updated_at' => $now,
                ]);
                $menuId = (int) $pdo->lastInsertId();

                foreach ($menu['items'] as $index => $item) {
                    $menuItemInsert->execute([
                        ':menu_id' => $menuId,
                        ':label' => $item['label'],
                        ':url' => $item['url'],
                        ':target' => $item['target'],
                        ':sort_order' => $index + 1,
                        ':created_at' => $now,
                        ':updated_at' => $now,
                    ]);
                }
            }
        }
    }

    private static function upsertSetting(PDO $pdo, string $driver, string $key, string $value): void
    {
        if ($driver === 'mysql') {
            $stmt = $pdo->prepare('INSERT INTO settings (`key`, value) VALUES (:key, :value)
                ON DUPLICATE KEY UPDATE value = VALUES(value)');
        } else {
            $stmt = $pdo->prepare('INSERT INTO settings (key, value) VALUES (:key, :value)
                ON CONFLICT(key) DO UPDATE SET value = excluded.value');
        }

        $stmt->execute([
            ':key' => $key,
            ':value' => $value,
        ]);
    }
}
