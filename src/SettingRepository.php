<?php

namespace MangaDiyari\Core;

use PDO;

class SettingRepository
{
    private string $driver;

    public function __construct(private PDO $db)
    {
        $this->driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function all(): array
    {
        $stmt = $this->db->query('SELECT `key`, value FROM settings');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $settings = [];
        foreach ($rows as $row) {
            $settings[$row['key']] = $row['value'];
        }

        return $settings;
    }

    public function get(string $key, ?string $default = null): ?string
    {
        $stmt = $this->db->prepare('SELECT value FROM settings WHERE `key` = :key LIMIT 1');
        $stmt->execute([':key' => $key]);
        $value = $stmt->fetchColumn();

        return $value === false ? $default : $value;
    }

    public function set(string $key, string $value): void
    {
        if ($this->driver === 'mysql') {
            $stmt = $this->db->prepare('INSERT INTO settings (`key`, value) VALUES (:key, :value)
                ON DUPLICATE KEY UPDATE value = VALUES(value)');
        } else {
            $stmt = $this->db->prepare('INSERT INTO settings (key, value) VALUES (:key, :value)
                ON CONFLICT(key) DO UPDATE SET value = excluded.value');
        }

        $stmt->execute([
            ':key' => $key,
            ':value' => $value,
        ]);
    }

    public function setMany(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->set((string) $key, (string) $value);
        }
    }
}
