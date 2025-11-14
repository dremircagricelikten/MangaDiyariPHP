<?php

namespace MangaDiyari\Core;

use PDO;

class WidgetRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function all(bool $onlyEnabled = false): array
    {
        $query = 'SELECT * FROM widgets';
        if ($onlyEnabled) {
            $query .= ' WHERE enabled = 1';
        }
        $query .= ' ORDER BY area ASC, sort_order ASC, id ASC';

        $stmt = $this->db->query($query);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_map(fn(array $row) => $this->hydrate($row), $rows);
    }

    public function getActive(): array
    {
        return $this->all(true);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM widgets WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrate($row) : null;
    }

    public function findByType(string $type): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM widgets WHERE type = :type LIMIT 1');
        $stmt->execute([':type' => $type]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->hydrate($row) : null;
    }

    public function create(array $data): array
    {
        $stmt = $this->db->prepare('INSERT INTO widgets (type, title, area, enabled, sort_order, config)
            VALUES (:type, :title, :area, :enabled, :sort_order, :config)');
        $stmt->execute([
            ':type' => $data['type'],
            ':title' => $data['title'],
            ':area' => $data['area'] ?? 'main',
            ':enabled' => !empty($data['enabled']) ? 1 : 0,
            ':sort_order' => $data['sort_order'] ?? 0,
            ':config' => json_encode($data['config'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);

        return $this->find((int) $this->db->lastInsertId());
    }

    public function update(int $id, array $data): ?array
    {
        $existing = $this->find($id);
        if (!$existing) {
            return null;
        }

        $stmt = $this->db->prepare('UPDATE widgets SET title = :title, area = :area, enabled = :enabled, sort_order = :sort_order, config = :config WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'] ?? $existing['title'],
            ':area' => $data['area'] ?? $existing['area'],
            ':enabled' => isset($data['enabled']) ? ((int) $data['enabled'] ? 1 : 0) : ($existing['enabled'] ? 1 : 0),
            ':sort_order' => $data['sort_order'] ?? $existing['sort_order'],
            ':config' => json_encode($data['config'] ?? $existing['config'], JSON_UNESCAPED_UNICODE),
        ]);

        return $this->find($id);
    }

    private function hydrate(array $row): array
    {
        $row['enabled'] = (int) $row['enabled'] === 1;
        $row['sort_order'] = (int) $row['sort_order'];
        $row['area'] = $row['area'] ?? 'main';
        $row['config'] = $row['config'] ? json_decode($row['config'], true) ?: [] : [];

        return $row;
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getActiveGroupedByArea(): array
    {
        $grouped = [];
        foreach ($this->getActive() as $widget) {
            $area = $widget['area'] ?? 'main';
            if (!isset($grouped[$area])) {
                $grouped[$area] = [];
            }
            $grouped[$area][] = $widget;
        }

        foreach ($grouped as &$widgets) {
            usort($widgets, static fn(array $a, array $b): int => $a['sort_order'] <=> $b['sort_order']);
        }

        return $grouped;
    }
}
