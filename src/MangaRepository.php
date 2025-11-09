<?php

namespace MangaDiyari\Core;

use PDO;
use DateTimeImmutable;

class MangaRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function create(array $data): array
    {
        $slug = $data['slug'] ?? Slugger::slugify($data['title']);
        $now = (new DateTimeImmutable())->format('c');

        $stmt = $this->db->prepare('INSERT INTO mangas (title, slug, description, cover_image, author, status, genres, tags, created_at, updated_at)
            VALUES (:title, :slug, :description, :cover_image, :author, :status, :genres, :tags, :created_at, :updated_at)');

        $stmt->execute([
            ':title' => $data['title'],
            ':slug' => $slug,
            ':description' => $data['description'] ?? '',
            ':cover_image' => $data['cover_image'] ?? '',
            ':author' => $data['author'] ?? '',
            ':status' => $data['status'] ?? 'ongoing',
            ':genres' => $data['genres'] ?? '',
            ':tags' => $data['tags'] ?? '',
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        return $this->findById((int) $this->db->lastInsertId());
    }

    public function update(int $id, array $data): ?array
    {
        $existing = $this->findById($id);
        if (!$existing) {
            return null;
        }

        $stmt = $this->db->prepare('UPDATE mangas SET title = :title, description = :description, cover_image = :cover_image, author = :author, status = :status, genres = :genres, tags = :tags, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'] ?? $existing['title'],
            ':description' => $data['description'] ?? $existing['description'],
            ':cover_image' => $data['cover_image'] ?? $existing['cover_image'],
            ':author' => $data['author'] ?? $existing['author'],
            ':status' => $data['status'] ?? $existing['status'],
            ':genres' => $data['genres'] ?? $existing['genres'],
            ':tags' => $data['tags'] ?? $existing['tags'],
            ':updated_at' => (new DateTimeImmutable())->format('c'),
        ]);

        return $this->findById($id);
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

    public function getFeatured(int $limit = 5): array
    {
        $stmt = $this->db->prepare('SELECT * FROM mangas ORDER BY RANDOM() LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
