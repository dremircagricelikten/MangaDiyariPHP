<?php

namespace MangaDiyari\Core;

use DateTimeImmutable;
use PDO;

class MangaRepository
{
    private string $driver;

    public function __construct(private PDO $db)
    {
        $this->driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function create(array $data): array
    {
        $baseSlug = $data['slug'] ?? $data['title'];
        $slug = $this->ensureUniqueSlug(Slugger::slugify((string) $baseSlug));
        $timestamp = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare('INSERT INTO mangas (title, slug, description, cover_image, author, artist, status, genres, tags, created_at, updated_at)
            VALUES (:title, :slug, :description, :cover_image, :author, :artist, :status, :genres, :tags, :created_at, :updated_at)');

        $stmt->execute([
            ':title' => $data['title'],
            ':slug' => $slug,
            ':description' => $data['description'] ?? '',
            ':cover_image' => $data['cover_image'] ?? '',
            ':author' => $data['author'] ?? '',
            ':artist' => $data['artist'] ?? '',
            ':status' => $data['status'] ?? 'ongoing',
            ':genres' => $data['genres'] ?? '',
            ':tags' => $data['tags'] ?? '',
            ':created_at' => $timestamp,
            ':updated_at' => $timestamp,
        ]);

        return $this->findById((int) $this->db->lastInsertId());
    }

    public function update(int $id, array $data): ?array
    {
        $existing = $this->findById($id);
        if (!$existing) {
            return null;
        }

        $slug = $existing['slug'];
        if (array_key_exists('slug', $data) && trim((string) $data['slug']) !== '') {
            $candidate = Slugger::slugify((string) $data['slug']);
            if ($candidate !== $existing['slug']) {
                $slug = $this->ensureUniqueSlug($candidate, $id);
            }
        }

        $stmt = $this->db->prepare('UPDATE mangas SET title = :title, slug = :slug, description = :description, cover_image = :cover_image, author = :author, artist = :artist, status = :status, genres = :genres, tags = :tags, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'] ?? $existing['title'],
            ':slug' => $slug,
            ':description' => $data['description'] ?? $existing['description'],
            ':cover_image' => $data['cover_image'] ?? $existing['cover_image'],
            ':author' => $data['author'] ?? $existing['author'],
            ':artist' => $data['artist'] ?? ($existing['artist'] ?? ''),
            ':status' => $data['status'] ?? $existing['status'],
            ':genres' => $data['genres'] ?? $existing['genres'],
            ':tags' => $data['tags'] ?? $existing['tags'],
            ':updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        return $this->findById($id);
    }

    private function ensureUniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = $slug !== '' ? $slug : 'manga';
        $candidate = $base;
        $suffix = 1;

        while ($this->slugExists($candidate, $ignoreId)) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $query = 'SELECT id FROM mangas WHERE slug = :slug';
        $params = [':slug' => $slug];

        if ($ignoreId !== null) {
            $query .= ' AND id != :id';
            $params[':id'] = $ignoreId;
        }

        $stmt = $this->db->prepare($query . ' LIMIT 1');
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM mangas WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $manga = $stmt->fetch(PDO::FETCH_ASSOC);

        return $manga ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM mangas WHERE slug = :slug');
        $stmt->execute([':slug' => $slug]);

        $manga = $stmt->fetch(PDO::FETCH_ASSOC);
        return $manga ?: null;
    }

    public function list(array $filters = []): array
    {
        $query = 'SELECT * FROM mangas';
        $conditions = [];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = '(title LIKE :search OR author LIKE :search)';
            $params[':search'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['status'])) {
            $conditions[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }

        if ($conditions) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $query .= ' ORDER BY created_at DESC';

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPopular(array $options = []): array
    {
        $limit = isset($options['limit']) ? max(1, (int) $options['limit']) : 5;
        $status = $options['status'] ?? null;
        $sort = $options['sort'] ?? 'random';

        $randomFunction = $this->driver === 'mysql' ? 'RAND()' : 'RANDOM()';
        $orderBy = match ($sort) {
            'newest' => 'created_at DESC',
            'alphabetical' => 'LOWER(title) ASC',
            'updated' => 'updated_at DESC',
            default => $randomFunction,
        };

        $query = 'SELECT * FROM mangas';
        $params = [];

        if ($status) {
            $query .= ' WHERE status = :status';
            $params[':status'] = $status;
        }

        $query .= ' ORDER BY ' . $orderBy . ' LIMIT :limit';

        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getFeatured(int $limit = 5): array
    {
        return $this->getPopular(['limit' => $limit]);
    }


    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM mangas WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function count(array $filters = []): int
    {
        $query = 'SELECT COUNT(*) FROM mangas';
        $conditions = [];
        $params = [];

        if (!empty($filters['status'])) {
            $conditions[] = 'status = :status';
            $params[':status'] = $filters['status'];
        }

        if ($conditions) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }
}
