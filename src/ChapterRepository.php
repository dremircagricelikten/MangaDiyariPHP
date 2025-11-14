<?php

namespace MangaDiyari\Core;

use DateTimeImmutable;
use PDO;

class ChapterRepository
{
    private string $driver;

    public function __construct(private PDO $db)
    {
        $this->driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function create(int $mangaId, array $data): array
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $assets = $data['assets'] ?? [];

        $stmt = $this->db->prepare('INSERT INTO chapters (manga_id, number, title, content, assets, ki_cost, premium_expires_at, created_at, updated_at)
            VALUES (:manga_id, :number, :title, :content, :assets, :ki_cost, :premium_expires_at, :created_at, :updated_at)');

        $stmt->execute([
            ':manga_id' => $mangaId,
            ':number' => $data['number'],
            ':title' => $data['title'] ?? '',
            ':content' => $data['content'] ?? '',
            ':assets' => json_encode($assets, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':ki_cost' => (int) ($data['ki_cost'] ?? 0),
            ':premium_expires_at' => $data['premium_expires_at'] ?? null,
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

        $stmt = $this->db->prepare('UPDATE chapters SET title = :title, content = :content, number = :number, assets = :assets, ki_cost = :ki_cost, premium_expires_at = :premium_expires_at, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'] ?? $existing['title'],
            ':content' => $data['content'] ?? $existing['content'],
            ':number' => $data['number'] ?? $existing['number'],
            ':assets' => json_encode($data['assets'] ?? $existing['assets'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ':ki_cost' => isset($data['ki_cost']) ? (int) $data['ki_cost'] : (int) ($existing['ki_cost'] ?? 0),
            ':premium_expires_at' => $data['premium_expires_at'] ?? ($existing['premium_expires_at'] ?? null),
            ':updated_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        return $this->findById($id);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM chapters WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $chapter = $stmt->fetch(PDO::FETCH_ASSOC);

        return $chapter ? $this->hydrate($chapter) : null;
    }

    public function listByManga(int $mangaId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM chapters WHERE manga_id = :manga_id ORDER BY ' . $this->numericOrder('number') . ' DESC');
        $stmt->execute([':manga_id' => $mangaId]);

        return array_map(fn(array $row) => $this->hydrate($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function findByMangaAndNumber(int $mangaId, string $number): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM chapters WHERE manga_id = :manga_id AND number = :number');
        $stmt->execute([
            ':manga_id' => $mangaId,
            ':number' => $number,
        ]);

        $chapter = $stmt->fetch(PDO::FETCH_ASSOC);
        return $chapter ? $this->hydrate($chapter) : null;
    }

    public function getPreviousChapter(int $mangaId, float $number): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM chapters WHERE manga_id = :manga_id AND ' . $this->numericOrder('number') . ' < :number ORDER BY ' . $this->numericOrder('number') . ' DESC LIMIT 1');
        $stmt->execute([
            ':manga_id' => $mangaId,
            ':number' => $number,
        ]);

        $chapter = $stmt->fetch(PDO::FETCH_ASSOC);
        return $chapter ? $this->hydrate($chapter) : null;
    }

    public function getNextChapter(int $mangaId, float $number): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM chapters WHERE manga_id = :manga_id AND ' . $this->numericOrder('number') . ' > :number ORDER BY ' . $this->numericOrder('number') . ' ASC LIMIT 1');
        $stmt->execute([
            ':manga_id' => $mangaId,
            ':number' => $number,
        ]);

        $chapter = $stmt->fetch(PDO::FETCH_ASSOC);
        return $chapter ? $this->hydrate($chapter) : null;
    }

    public function getLatestChapters(int $limit = 8, array $options = []): array
    {
        $limit = max(1, $limit);
        $status = $options['status'] ?? null;
        $sort = $options['sort'] ?? 'newest';

        $orderBy = match ($sort) {
            'oldest' => 'chapters.created_at ASC',
            'chapter_desc' => $this->numericOrder('chapters.number') . ' DESC',
            'chapter_asc' => $this->numericOrder('chapters.number') . ' ASC',
            default => 'chapters.created_at DESC',
        };

        $query = 'SELECT chapters.*, mangas.title AS manga_title, mangas.slug AS manga_slug, mangas.cover_image '
            . 'FROM chapters INNER JOIN mangas ON mangas.id = chapters.manga_id';
        $conditions = [];
        $params = [];

        if ($status) {
            $conditions[] = 'mangas.status = :status';
            $params[':status'] = $status;
        }

        if ($conditions) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $query .= ' ORDER BY ' . $orderBy . ' LIMIT :limit';

        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map(fn(array $row) => $this->hydrate($row), $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function hydrate(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['manga_id'] = (int) $row['manga_id'];
        $row['assets'] = $row['assets'] ? json_decode($row['assets'], true) ?: [] : [];
        $row['ki_cost'] = isset($row['ki_cost']) ? (int) $row['ki_cost'] : 0;
        $row['premium_expires_at'] = $row['premium_expires_at'] ?? null;

        return $row;
    }


    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM chapters WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function count(array $filters = []): int
    {
        $query = 'SELECT COUNT(*) FROM chapters';
        $conditions = [];
        $params = [];

        if (!empty($filters['manga_id'])) {
            $conditions[] = 'manga_id = :manga_id';
            $params[':manga_id'] = (int) $filters['manga_id'];
        }

        if (!empty($filters['premium_only'])) {
            $conditions[] = 'ki_cost > 0';
        }

        if ($conditions) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }

    private function numericOrder(string $column): string
    {
        return $this->driver === 'mysql'
            ? "CAST({$column} AS DECIMAL(10,2))"
            : "CAST({$column} AS REAL)";
    }
}
