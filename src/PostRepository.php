<?php

namespace MangaDiyari\Core;

use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

use function array_map;

use const JSON_THROW_ON_ERROR;

class PostRepository
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

        if (!empty($criteria['status'])) {
            $where[] = 'status = :status';
            $params[':status'] = $criteria['status'];
        }

        if (!empty($criteria['search'])) {
            $where[] = '(title LIKE :search OR content LIKE :search)';
            $params[':search'] = '%' . $criteria['search'] . '%';
        }

        $sql = 'SELECT * FROM posts';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $order = $options['order_by'] ?? 'published_at DESC, created_at DESC';
        $sql .= ' ORDER BY ' . $order;

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

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRow($row) : null;
    }

    public function findBySlug(string $slug): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM posts WHERE slug = :slug LIMIT 1');
        $stmt->execute([':slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapRow($row) : null;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function create(array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        if ($title === '') {
            throw new InvalidArgumentException('Yazı başlığı gereklidir.');
        }

        $slug = Slugger::slugify((string) ($data['slug'] ?? ''));
        if ($slug === '') {
            $slug = Slugger::slugify($title);
        }

        $slug = $this->ensureUniqueSlug($slug);
        $status = $this->normalizeStatus($data['status'] ?? 'draft');
        $now = new DateTimeImmutable();
        $publishedAt = $status === 'published' ? $now->format('Y-m-d H:i:s') : null;

        $stmt = $this->db->prepare('INSERT INTO posts (title, slug, excerpt, content, status, featured_image, author_id, category_slugs, tag_slugs, published_at, created_at, updated_at) VALUES (:title, :slug, :excerpt, :content, :status, :featured_image, :author_id, :categories, :tags, :published_at, :created_at, :updated_at)');
        $stmt->execute([
            ':title' => $title,
            ':slug' => $slug,
            ':excerpt' => trim((string) ($data['excerpt'] ?? '')),
            ':content' => (string) ($data['content'] ?? ''),
            ':status' => $status,
            ':featured_image' => trim((string) ($data['featured_image'] ?? '')),
            ':author_id' => $data['author_id'] !== null ? (int) $data['author_id'] : null,
            ':categories' => json_encode($this->normalizeTerms($data['categories'] ?? []), JSON_UNESCAPED_UNICODE),
            ':tags' => json_encode($this->normalizeTerms($data['tags'] ?? []), JSON_UNESCAPED_UNICODE),
            ':published_at' => $publishedAt,
            ':created_at' => $now->format('Y-m-d H:i:s'),
            ':updated_at' => $now->format('Y-m-d H:i:s'),
        ]);

        $post = $this->find((int) $this->db->lastInsertId());
        if (!$post) {
            throw new InvalidArgumentException('Yazı oluşturulamadı.');
        }

        return $post;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(int $id, array $data): ?array
    {
        $post = $this->find($id);
        if (!$post) {
            return null;
        }

        $title = trim((string) ($data['title'] ?? $post['title']));
        if ($title === '') {
            throw new InvalidArgumentException('Yazı başlığı gereklidir.');
        }

        $status = array_key_exists('status', $data) ? $this->normalizeStatus($data['status']) : $post['status'];
        $slug = $data['slug'] ?? $post['slug'];
        $slug = Slugger::slugify((string) $slug);
        if ($slug === '') {
            $slug = Slugger::slugify($title);
        }
        if ($slug !== $post['slug']) {
            $slug = $this->ensureUniqueSlug($slug, $id);
        }

        $categories = array_key_exists('categories', $data) ? $this->normalizeTerms($data['categories']) : $post['categories'];
        $tags = array_key_exists('tags', $data) ? $this->normalizeTerms($data['tags']) : $post['tags'];

        $now = new DateTimeImmutable();
        $publishedAt = $post['published_at'];
        if ($status === 'published' && !$publishedAt) {
            $publishedAt = $now->format('Y-m-d H:i:s');
        } elseif ($status !== 'published') {
            $publishedAt = null;
        }

        $stmt = $this->db->prepare('UPDATE posts SET title = :title, slug = :slug, excerpt = :excerpt, content = :content, status = :status, featured_image = :featured_image, author_id = :author_id, category_slugs = :categories, tag_slugs = :tags, published_at = :published_at, updated_at = :updated_at WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':title' => $title,
            ':slug' => $slug,
            ':excerpt' => trim((string) ($data['excerpt'] ?? $post['excerpt'] ?? '')),
            ':content' => array_key_exists('content', $data) ? (string) $data['content'] : $post['content'],
            ':status' => $status,
            ':featured_image' => array_key_exists('featured_image', $data) ? trim((string) $data['featured_image']) : ($post['featured_image'] ?? ''),
            ':author_id' => array_key_exists('author_id', $data) ? ($data['author_id'] !== null ? (int) $data['author_id'] : null) : $post['author_id'],
            ':categories' => json_encode($categories, JSON_UNESCAPED_UNICODE),
            ':tags' => json_encode($tags, JSON_UNESCAPED_UNICODE),
            ':published_at' => $publishedAt,
            ':updated_at' => $now->format('Y-m-d H:i:s'),
        ]);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM posts WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    private function normalizeStatus(mixed $status): string
    {
        $status = (string) $status;
        return in_array($status, ['draft', 'published'], true) ? $status : 'draft';
    }

    private function normalizeTerms(mixed $terms): array
    {
        if (is_string($terms)) {
            $terms = array_filter(array_map('trim', explode(',', $terms)));
        }
        if (!is_array($terms)) {
            return [];
        }
        $normalized = [];
        foreach ($terms as $term) {
            $term = trim((string) $term);
            if ($term !== '') {
                $normalized[] = $term;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function ensureUniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = $slug;
        $suffix = 1;
        while ($this->slugExists($slug, $ignoreId)) {
            $slug = $base . '-' . ++$suffix;
        }

        return $slug;
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT id FROM posts WHERE slug = :slug';
        $params = [':slug' => $slug];
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
        $row['author_id'] = $row['author_id'] !== null ? (int) $row['author_id'] : null;
        $row['categories'] = $this->decodeTerms($row['category_slugs'] ?? null);
        $row['tags'] = $this->decodeTerms($row['tag_slugs'] ?? null);
        unset($row['category_slugs'], $row['tag_slugs']);

        return $row;
    }

    /**
     * @return array<int, string>
     */
    private function decodeTerms(?string $value): array
    {
        if (!$value) {
            return [];
        }

        try {
            $decoded = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            return [];
        }

        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_map(static fn($term): string => (string) $term, $decoded));
    }
}
