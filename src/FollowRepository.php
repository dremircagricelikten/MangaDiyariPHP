<?php

namespace MangaDiyari\Core;

use DateTimeImmutable;
use PDO;

class FollowRepository
{
    private string $driver;

    public function __construct(private PDO $db)
    {
        $this->driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function follow(int $userId, int $mangaId): bool
    {
        if ($this->isFollowing($userId, $mangaId)) {
            return false;
        }

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $sql = 'INSERT INTO manga_follows (user_id, manga_id, created_at) VALUES (:user, :manga, :created)';

        if ($this->driver === 'sqlite') {
            $sql = 'INSERT OR IGNORE INTO manga_follows (user_id, manga_id, created_at) VALUES (:user, :manga, :created)';
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user' => $userId,
            ':manga' => $mangaId,
            ':created' => $now,
        ]);

        return true;
    }

    public function unfollow(int $userId, int $mangaId): void
    {
        $stmt = $this->db->prepare('DELETE FROM manga_follows WHERE user_id = :user AND manga_id = :manga');
        $stmt->execute([
            ':user' => $userId,
            ':manga' => $mangaId,
        ]);
    }

    public function isFollowing(int $userId, int $mangaId): bool
    {
        $stmt = $this->db->prepare('SELECT 1 FROM manga_follows WHERE user_id = :user AND manga_id = :manga LIMIT 1');
        $stmt->execute([
            ':user' => $userId,
            ':manga' => $mangaId,
        ]);

        return (bool) $stmt->fetchColumn();
    }

    public function countFollowers(int $mangaId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM manga_follows WHERE manga_id = :manga');
        $stmt->execute([':manga' => $mangaId]);

        return (int) $stmt->fetchColumn();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listFollowedByUser(int $userId, int $limit = 50): array
    {
        $limit = max(1, $limit);
        $sql = 'SELECT mangas.*, manga_follows.created_at AS followed_at
            FROM manga_follows
            INNER JOIN mangas ON mangas.id = manga_follows.manga_id
            WHERE manga_follows.user_id = :user
            ORDER BY manga_follows.created_at DESC
            LIMIT :limit';

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':user', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
