<?php

namespace MangaDiyari\Core;

use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

class MediaRepository
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * @param array<string, mixed> $criteria
     * @param array<string, mixed> $options
     * @return array<int, array<string, mixed>>
     */
    public function list(array $criteria = [], array $options = []): array
    {
        $where = [];
        $params = [];

        if (!empty($criteria['search'])) {
            $where[] = '(title LIKE :search OR filename LIKE :search)';
            $params[':search'] = '%' . $criteria['search'] . '%';
        }

        $sql = 'SELECT * FROM media_items';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY created_at DESC';

        if (!empty($options['limit'])) {
            $sql .= ' LIMIT ' . (int) $options['limit'];
            if (!empty($options['offset'])) {
                $sql .= ' OFFSET ' . (int) $options['offset'];
            }
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        return array_map(fn(array $row): array => $this->mapRow($row), $rows);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): array
    {
        $filename = trim((string) ($data['filename'] ?? ''));
        $path = trim((string) ($data['path'] ?? ''));
        $mime = trim((string) ($data['mime_type'] ?? ''));
        if ($filename === '' || $path === '' || $mime === '') {
            throw new InvalidArgumentException('Geçersiz medya dosyası.');
        }

        $now = new DateTimeImmutable();
        $stmt = $this->db->prepare('INSERT INTO media_items (filename, path, mime_type, size_bytes, title, alt_text, created_by, created_at) VALUES (:filename, :path, :mime_type, :size, :title, :alt_text, :created_by, :created_at)');
        $stmt->execute([
            ':filename' => $filename,
            ':path' => $path,
            ':mime_type' => $mime,
            ':size' => (int) ($data['size_bytes'] ?? 0),
            ':title' => trim((string) ($data['title'] ?? pathinfo($filename, PATHINFO_FILENAME))),
            ':alt_text' => trim((string) ($data['alt_text'] ?? '')),
            ':created_by' => $data['created_by'] !== null ? (int) $data['created_by'] : null,
            ':created_at' => $now->format('Y-m-d H:i:s'),
        ]);

        $media = $this->find((int) $this->db->lastInsertId());
        if (!$media) {
            throw new InvalidArgumentException('Medya kaydı oluşturulamadı.');
        }

        return $media;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM media_items WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRow($row) : null;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM media_items WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    private function mapRow(array $row): array
    {
        $row['id'] = (int) $row['id'];
        $row['size_bytes'] = (int) ($row['size_bytes'] ?? 0);
        $row['created_by'] = $row['created_by'] !== null ? (int) $row['created_by'] : null;
        $row['url'] = $row['path'];

        return $row;
    }
}
