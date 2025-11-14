<?php

namespace MangaDiyari\Core;

use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

class PageRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function list(array $criteria = [], array $options = []): array
    {
        $where = [];
        $params = [];

        if (!empty($criteria['status'])) {
            $where[] = 'status = :status';
            $params[':status'] = $criteria['status'];
        }

        if (!empty($criteria['search'])) {
            $where[] = '(title LIKE :search OR content LIKE :search)';
            $params[':search'] = '%' . $criteria['search'] . '%';
        }

        $sql = 'SELECT * FROM pages';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $orderBy = $options['order_by'] ?? 'published_at DESC, created_at DESC';
        $sql .= ' ORDER BY ' . $orderBy;

        if (!empty($options['limit'])) {
            $sql .= ' LIMIT ' . (int) $options['limit'];
            if (!empty($options['offset'])) {
                $sql .= ' OFFSET ' . (int) $options['offset'];
            }
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM pages WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $page = $stmt->fetch(PDO::FETCH_ASSOC);

        return $page ?: null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM pages WHERE slug = :slug LIMIT 1');
        $stmt->execute([':slug' => $slug]);
        $page = $stmt->fetch(PDO::FETCH_ASSOC);

        return $page ?: null;
    }

    public function create(array $data): array
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $title = trim((string) ($data['title'] ?? ''));
        $content = (string) ($data['content'] ?? '');
        $status = in_array($data['status'] ?? 'draft', ['draft', 'published'], true) ? $data['status'] : 'draft';
        $slug = trim((string) ($data['slug'] ?? ''));

        if ($title === '') {
            throw new InvalidArgumentException('Sayfa başlığı gerekli');
        }

        if ($slug === '') {
            $slug = Slugger::slugify($title);
        } else {
            $slug = Slugger::slugify($slug);
        }

        $slug = $this->ensureUniqueSlug($slug);

        $publishedAt = null;
        if ($status === 'published') {
            $publishedAt = $now;
        }

        $stmt = $this->db->prepare('INSERT INTO pages (title, slug, content, status, published_at, created_at, updated_at)
            VALUES (:title, :slug, :content, :status, :published_at, :created_at, :updated_at)');
        $stmt->execute([
            ':title' => $title,
            ':slug' => $slug,
            ':content' => $content,
            ':status' => $status,
            ':published_at' => $publishedAt,
            ':created_at' => $now,
            ':updated_at' => $now,
        ]);

        $id = (int) $this->db->lastInsertId();

        return $this->find($id);
    }

    public function update(int $id, array $data): ?array
    {
        $page = $this->find($id);
        if (!$page) {
            return null;
        }

        $title = trim((string) ($data['title'] ?? $page['title']));
        if ($title === '') {
            throw new InvalidArgumentException('Sayfa başlığı gerekli');
        }

        $content = array_key_exists('content', $data) ? (string) $data['content'] : $page['content'];
        $status = $data['status'] ?? $page['status'];
        if (!in_array($status, ['draft', 'published'], true)) {
            $status = $page['status'];
        }

        $slug = $data['slug'] ?? $page['slug'];
        $slug = Slugger::slugify((string) $slug);
        if ($slug === '') {
            $slug = Slugger::slugify($title);
        }

        if ($slug !== $page['slug']) {
            $slug = $this->ensureUniqueSlug($slug, $id);
        }

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $publishedAt = $page['published_at'];
        if ($status === 'published' && !$publishedAt) {
            $publishedAt = $now;
        }
        if ($status === 'draft') {
            $publishedAt = null;
        }

        $stmt = $this->db->prepare('UPDATE pages SET title = :title, slug = :slug, content = :content, status = :status, published_at = :published_at, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':title' => $title,
            ':slug' => $slug,
            ':content' => $content,
            ':status' => $status,
            ':published_at' => $publishedAt,
            ':updated_at' => $now,
        ]);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM pages WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    private function ensureUniqueSlug(string $baseSlug, ?int $ignoreId = null): string
    {
        $slug = $baseSlug;
        $suffix = 1;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $baseSlug . '-' . ++$suffix;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT id FROM pages WHERE slug = :slug';
        $params = [':slug' => $slug];
        if ($ignoreId) {
            $sql .= ' AND id != :ignore';
            $params[':ignore'] = $ignoreId;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (bool) $stmt->fetchColumn();
    }
}
