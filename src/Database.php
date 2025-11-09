<?php

namespace MangaDiyari\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $connection = null;

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
            } catch (PDOException $e) {
                throw new PDOException('Veritabanı bağlantısı kurulamadı: ' . $e->getMessage(), (int) $e->getCode(), $e);
            }
        }

        return self::$connection;
    }
}
