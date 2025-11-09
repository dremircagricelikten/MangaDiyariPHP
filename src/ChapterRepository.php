<?php

namespace MangaDiyari\Core;

use PDO;
use DateTimeImmutable;

class ChapterRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function create(int $mangaId, array $data): array
    {
        $now = (new DateTimeImmutable())->format('c');

        $stmt = $this->db->prepare('INSERT INTO chapters (manga_id, number, title, content, created_at, updated_at)
            VALUES (:manga_id, :number, :title, :content, :created_at, :updated_at)');

        $stmt->execute([
            ':manga_id' => $mangaId,
            ':number' => $data['number'],
            ':title' => $data['title'] ?? '',
            ':content' => $data['content'] ?? '',
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

        $stmt = $this->db->prepare('UPDATE chapters SET title = :title, content = :content, number = :number, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':title' => $data['title'] ?? $existing['title'],
            ':content' => $data['content'] ?? $existing['content'],
            ':number' => $data['number'] ?? $existing['number'],
            ':updated_at' => (new DateTimeImmutable())->format('c'),
        ]);

        return $this->findById($id);
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM chapters WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $chapter = $stmt->fetch(PDO::FETCH_ASSOC);

        return $chapter ?: null;
    }

    public function listByManga(int $mangaId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM chapters WHERE manga_id = :manga_id ORDER BY number DESC');
        $stmt->execute([':manga_id' => $mangaId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByMangaAndNumber(int $mangaId, string $number): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM chapters WHERE manga_id = :manga_id AND number = :number');
        $stmt->execute([
            ':manga_id' => $mangaId,
            ':number' => $number,
        ]);

        $chapter = $stmt->fetch(PDO::FETCH_ASSOC);
        return $chapter ?: null;
    }

    public function getPreviousChapter(int $mangaId, float $number): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM chapters WHERE manga_id = :manga_id AND number < :number ORDER BY number DESC LIMIT 1');
        $stmt->execute([
            ':manga_id' => $mangaId,
            ':number' => $number,
        ]);

        $chapter = $stmt->fetch(PDO::FETCH_ASSOC);
        return $chapter ?: null;
    }

    public function getNextChapter(int $mangaId, float $number): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM chapters WHERE manga_id = :manga_id AND number > :number ORDER BY number ASC LIMIT 1');
        $stmt->execute([
            ':manga_id' => $mangaId,
            ':number' => $number,
        ]);

        $chapter = $stmt->fetch(PDO::FETCH_ASSOC);
        return $chapter ?: null;
    }
}
