<?php

namespace MangaDiyari\Core;

use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

class TaxonomyRepository
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(string $taxonomy): array
    {
        $stmt = $this->db->prepare('SELECT * FROM taxonomies WHERE taxonomy = :taxonomy ORDER BY name');
        $stmt->execute([':taxonomy' => $taxonomy]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(fn(array $row): array => $this->mapRow($row), $rows);
    }

    public function find(string $taxonomy, int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM taxonomies WHERE taxonomy = :taxonomy AND id = :id');
        $stmt->execute([
            ':taxonomy' => $taxonomy,
            ':id' => $id,
        ]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRow($row) : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(string $taxonomy, array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        if ($name === '') {
            throw new InvalidArgumentException('İsim gereklidir.');
        }

        $slug = Slugger::slugify((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            $slug = Slugger::slugify($name);
        }
        $slug = $this->ensureUniqueSlug($taxonomy, $slug);

        $now = new DateTimeImmutable();
        $stmt = $this->db->prepare('INSERT INTO taxonomies (taxonomy, name, slug, description, created_at, updated_at) VALUES (:taxonomy, :name, :slug, :description, :created_at, :updated_at)');
        $stmt->execute([
            ':taxonomy' => $taxonomy,
            ':name' => $name,
            ':slug' => $slug,
            ':description' => trim((string) ($data['description'] ?? '')),
            ':created_at' => $now->format('Y-m-d H:i:s'),
            ':updated_at' => $now->format('Y-m-d H:i:s'),
        ]);

        $term = $this->find($taxonomy, (int) $this->db->lastInsertId());
        if (!$term) {
            throw new InvalidArgumentException('Terim oluşturulamadı.');
        }

        return $term;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(string $taxonomy, int $id, array $data): ?array
    {
        $term = $this->find($taxonomy, $id);
        if (!$term) {
            return null;
        }

        $name = trim((string) ($data['name'] ?? $term['name']));
        if ($name === '') {
            throw new InvalidArgumentException('İsim gereklidir.');
        }

        $slug = $data['slug'] ?? $term['slug'];
        $slug = Slugger::slugify((string) $slug);
        if ($slug === '') {
            $slug = Slugger::slugify($name);
        }

        if ($slug !== $term['slug']) {
            $slug = $this->ensureUniqueSlug($taxonomy, $slug, $id);
        }

        $now = new DateTimeImmutable();
        $stmt = $this->db->prepare('UPDATE taxonomies SET name = :name, slug = :slug, description = :description, updated_at = :updated_at WHERE id = :id AND taxonomy = :taxonomy');
        $stmt->execute([
            ':id' => $id,
            ':taxonomy' => $taxonomy,
            ':name' => $name,
            ':slug' => $slug,
            ':description' => array_key_exists('description', $data) ? trim((string) $data['description']) : $term['description'],
            ':updated_at' => $now->format('Y-m-d H:i:s'),
        ]);

        return $this->find($taxonomy, $id);
    }

    public function delete(string $taxonomy, int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM taxonomies WHERE taxonomy = :taxonomy AND id = :id');
        return $stmt->execute([
            ':taxonomy' => $taxonomy,
            ':id' => $id,
        ]);
    }

    private function ensureUniqueSlug(string $taxonomy, string $slug, ?int $ignoreId = null): string
    {
        $base = $slug;
        $suffix = 1;
        while ($this->slugExists($taxonomy, $slug, $ignoreId)) {
            $slug = $base . '-' . ++$suffix;
        }

        return $slug;
    }

    private function slugExists(string $taxonomy, string $slug, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT id FROM taxonomies WHERE taxonomy = :taxonomy AND slug = :slug';
        $params = [
            ':taxonomy' => $taxonomy,
            ':slug' => $slug,
        ];
        if ($ignoreId) {
            $sql .= ' AND id != :ignore';
            $params[':ignore'] = $ignoreId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }

    private function mapRow(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['description'] = $row['description'] ?? '';

        return $row;
    }
}
