<?php

namespace MangaDiyari\Core;

use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

use const JSON_THROW_ON_ERROR;

class RoleRepository
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        $stmt = $this->db->query('SELECT * FROM roles ORDER BY sort_order ASC, role_key ASC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(fn(array $row): array => $this->mapRow($row), $rows);
    }

    public function find(string $roleKey): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM roles WHERE role_key = :key LIMIT 1');
        $stmt->execute([':key' => $roleKey]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRow($row) : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): array
    {
        $key = strtolower(trim((string) ($data['role_key'] ?? '')));
        if ($key === '') {
            throw new InvalidArgumentException('Rol anahtarı gereklidir.');
        }
        if (!preg_match('/^[a-z0-9_\-]+$/', $key)) {
            throw new InvalidArgumentException('Rol anahtarı sadece küçük harf, sayı, tire ve alt çizgi içerebilir.');
        }
        if ($this->find($key)) {
            throw new InvalidArgumentException('Bu anahtara sahip rol zaten mevcut.');
        }

        $label = trim((string) ($data['label'] ?? ''));
        if ($label === '') {
            throw new InvalidArgumentException('Rol etiketi gereklidir.');
        }

        $capabilities = $this->normalizeCapabilities($data['capabilities'] ?? []);
        $now = new DateTimeImmutable();
        $stmt = $this->db->prepare('INSERT INTO roles (role_key, label, capabilities, is_system, sort_order, created_at, updated_at) VALUES (:key, :label, :capabilities, :is_system, :sort_order, :created_at, :updated_at)');
        $stmt->execute([
            ':key' => $key,
            ':label' => $label,
            ':capabilities' => json_encode($capabilities, JSON_UNESCAPED_UNICODE),
            ':is_system' => !empty($data['is_system']) ? 1 : 0,
            ':sort_order' => (int) ($data['sort_order'] ?? 0),
            ':created_at' => $now->format('Y-m-d H:i:s'),
            ':updated_at' => $now->format('Y-m-d H:i:s'),
        ]);

        $role = $this->find($key);
        if (!$role) {
            throw new InvalidArgumentException('Rol oluşturulamadı.');
        }

        return $role;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $roleKey, array $data): ?array
    {
        $role = $this->find($roleKey);
        if (!$role) {
            return null;
        }

        if ((int) $role['is_system'] === 1) {
            $data['role_key'] = $roleKey; // sistem rolleri anahtar değiştiremez
        }

        $newKey = strtolower(trim((string) ($data['role_key'] ?? $roleKey)));
        if ($newKey === '') {
            throw new InvalidArgumentException('Rol anahtarı gereklidir.');
        }
        if (!preg_match('/^[a-z0-9_\-]+$/', $newKey)) {
            throw new InvalidArgumentException('Rol anahtarı sadece küçük harf, sayı, tire ve alt çizgi içerebilir.');
        }
        if ($newKey !== $roleKey && $this->find($newKey)) {
            throw new InvalidArgumentException('Bu anahtara sahip başka bir rol mevcut.');
        }

        $label = trim((string) ($data['label'] ?? $role['label']));
        if ($label === '') {
            throw new InvalidArgumentException('Rol etiketi gereklidir.');
        }

        $capabilities = array_key_exists('capabilities', $data)
            ? $this->normalizeCapabilities($data['capabilities'])
            : $role['capabilities'];

        $now = new DateTimeImmutable();
        $stmt = $this->db->prepare('UPDATE roles SET role_key = :new_key, label = :label, capabilities = :capabilities, sort_order = :sort_order, updated_at = :updated_at WHERE role_key = :key');
        $stmt->execute([
            ':new_key' => $newKey,
            ':label' => $label,
            ':capabilities' => json_encode($capabilities, JSON_UNESCAPED_UNICODE),
            ':sort_order' => (int) ($data['sort_order'] ?? $role['sort_order']),
            ':updated_at' => $now->format('Y-m-d H:i:s'),
            ':key' => $roleKey,
        ]);

        return $this->find($newKey);
    }

    public function delete(string $roleKey): bool
    {
        $role = $this->find($roleKey);
        if (!$role) {
            return false;
        }
        if ((int) $role['is_system'] === 1) {
            throw new InvalidArgumentException('Sistem rolleri silinemez.');
        }

        $stmt = $this->db->prepare('DELETE FROM roles WHERE role_key = :key');
        return $stmt->execute([':key' => $roleKey]);
    }

    /**
     * @param array<int, string>|array<string, mixed> $capabilities
     * @return array<int, string>
     */
    public function normalizeCapabilities(mixed $capabilities): array
    {
        if (is_string($capabilities)) {
            $capabilities = array_filter(array_map('trim', explode(',', $capabilities)));
        }
        if (!is_array($capabilities)) {
            return [];
        }
        $normalized = [];
        foreach ($capabilities as $capability) {
            $capability = strtolower(trim((string) $capability));
            if ($capability !== '') {
                $normalized[] = $capability;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function mapRow(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['is_system'] = (int) $row['is_system'];
        $row['sort_order'] = (int) $row['sort_order'];
        $row['capabilities'] = $this->decodeCapabilities($row['capabilities'] ?? '[]');

        return $row;
    }

    /**
     * @return array<int, string>
     */
    private function decodeCapabilities(string $value): array
    {
        try {
            $decoded = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }
        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_map(static fn($cap): string => strtolower((string) $cap), $decoded));
    }
}
