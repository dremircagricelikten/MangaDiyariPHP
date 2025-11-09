<?php

namespace MangaDiyari\Core;

use DateTimeImmutable;
use InvalidArgumentException;
use PDO;

class InteractionRepository
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listComments(int $mangaId, ?int $chapterId = null, int $limit = 50, ?int $currentUserId = null): array
    {
        $query = 'SELECT comments.*, users.username, users.avatar_url FROM comments INNER JOIN users ON users.id = comments.user_id WHERE comments.is_deleted = 0 AND (comments.manga_id = :manga)';
        $params = [':manga' => $mangaId];

        if ($chapterId !== null) {
            $query .= ' AND comments.chapter_id = :chapter';
            $params[':chapter'] = $chapterId;
        }

        $query .= ' ORDER BY comments.created_at DESC LIMIT :limit';

        $stmt = $this->db->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value, PDO::PARAM_INT);
        }
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();

        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$comments) {
            return [];
        }

        $commentIds = array_map(static fn(array $row): int => (int) $row['id'], $comments);
        $reactionMap = $this->getReactionSummary($commentIds);
        $userReactions = $currentUserId ? $this->getUserReactions($commentIds, $currentUserId) : [];

        return array_map(function (array $row) use ($reactionMap, $userReactions): array {
            $id = (int) $row['id'];
            $row['id'] = $id;
            $row['user_id'] = (int) $row['user_id'];
            $row['manga_id'] = $row['manga_id'] !== null ? (int) $row['manga_id'] : null;
            $row['chapter_id'] = $row['chapter_id'] !== null ? (int) $row['chapter_id'] : null;
            $row['reaction_summary'] = $reactionMap[$id] ?? [];
            $row['user_reaction'] = $userReactions[$id] ?? null;
            return $row;
        }, $comments);
    }

    public function createComment(int $userId, array $data): array
    {
        $body = trim($data['body'] ?? '');
        $mangaId = (int) ($data['manga_id'] ?? 0);
        $chapterId = isset($data['chapter_id']) ? (int) $data['chapter_id'] : null;

        if ($body === '' || $mangaId <= 0) {
            throw new InvalidArgumentException('Yorum metni zorunludur.');
        }

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $stmt = $this->db->prepare('INSERT INTO comments (user_id, manga_id, chapter_id, body, created_at, updated_at) VALUES (:user, :manga, :chapter, :body, :created, :updated)');
        $stmt->execute([
            ':user' => $userId,
            ':manga' => $mangaId,
            ':chapter' => $chapterId,
            ':body' => $body,
            ':created' => $now,
            ':updated' => $now,
        ]);

        return $this->findComment((int) $this->db->lastInsertId(), $userId);
    }

    public function findComment(int $id, ?int $currentUserId = null): array
    {
        $stmt = $this->db->prepare('SELECT comments.*, users.username, users.avatar_url FROM comments INNER JOIN users ON users.id = comments.user_id WHERE comments.id = :id');
        $stmt->execute([':id' => $id]);
        $comment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$comment) {
            throw new InvalidArgumentException('Yorum bulunamadı.');
        }

        $comment['id'] = (int) $comment['id'];
        $comment['user_id'] = (int) $comment['user_id'];
        $comment['manga_id'] = $comment['manga_id'] !== null ? (int) $comment['manga_id'] : null;
        $comment['chapter_id'] = $comment['chapter_id'] !== null ? (int) $comment['chapter_id'] : null;
        $comment['reaction_summary'] = $this->getReactionSummary([$comment['id']])[$comment['id']] ?? [];
        if ($currentUserId) {
            $comment['user_reaction'] = $this->getUserReactions([$comment['id']], $currentUserId)[$comment['id']] ?? null;
        } else {
            $comment['user_reaction'] = null;
        }

        return $comment;
    }

    public function toggleReaction(int $commentId, int $userId, string $reactionType): ?array
    {
        $reactionType = strtolower(trim($reactionType));
        $allowed = ['like', 'love', 'wow', 'sad', 'angry'];

        if (!in_array($reactionType, $allowed, true)) {
            throw new InvalidArgumentException('Geçersiz tepki.');
        }

        $check = $this->db->prepare('SELECT id, reaction_type FROM comment_reactions WHERE comment_id = :comment AND user_id = :user');
        $check->execute([
            ':comment' => $commentId,
            ':user' => $userId,
        ]);
        $existing = $check->fetch(PDO::FETCH_ASSOC);

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        if ($existing) {
            if ($existing['reaction_type'] === $reactionType) {
                $delete = $this->db->prepare('DELETE FROM comment_reactions WHERE id = :id');
                $delete->execute([':id' => $existing['id']]);
                return null;
            }

            $update = $this->db->prepare('UPDATE comment_reactions SET reaction_type = :reaction, created_at = :created WHERE id = :id');
            $update->execute([
                ':reaction' => $reactionType,
                ':created' => $now,
                ':id' => $existing['id'],
            ]);
        } else {
            $insert = $this->db->prepare('INSERT INTO comment_reactions (comment_id, user_id, reaction_type, created_at) VALUES (:comment, :user, :reaction, :created)');
            $insert->execute([
                ':comment' => $commentId,
                ':user' => $userId,
                ':reaction' => $reactionType,
                ':created' => $now,
            ]);
        }

        return $this->getReactionSummary([$commentId])[$commentId] ?? [];
    }

    /**
     * @return array<int, array<string, int>>
     */
    private function getReactionSummary(array $commentIds): array
    {
        if (!$commentIds) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($commentIds), '?'));
        $stmt = $this->db->prepare("SELECT comment_id, reaction_type, COUNT(*) AS total FROM comment_reactions WHERE comment_id IN ($placeholders) GROUP BY comment_id, reaction_type");
        $stmt->execute($commentIds);

        $summary = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $commentId = (int) $row['comment_id'];
            $reaction = (string) $row['reaction_type'];
            $summary[$commentId][$reaction] = (int) $row['total'];
        }

        return $summary;
    }

    /**
     * @return array<int, string>
     */
    private function getUserReactions(array $commentIds, int $userId): array
    {
        if (!$commentIds) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($commentIds), '?'));
        $params = array_merge($commentIds, [$userId]);
        $stmt = $this->db->prepare("SELECT comment_id, reaction_type FROM comment_reactions WHERE comment_id IN ($placeholders) AND user_id = ?");
        $stmt->execute($params);

        $result = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $result[(int) $row['comment_id']] = (string) $row['reaction_type'];
        }

        return $result;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listChatMessages(int $limit = 30): array
    {
        $stmt = $this->db->prepare('SELECT chat_messages.*, users.username, users.avatar_url FROM chat_messages INNER JOIN users ON users.id = chat_messages.user_id ORDER BY chat_messages.id DESC LIMIT :limit');
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();

        $rows = array_reverse($stmt->fetchAll(PDO::FETCH_ASSOC));

        return array_map(static function (array $row): array {
            $row['id'] = (int) $row['id'];
            $row['user_id'] = (int) $row['user_id'];
            return $row;
        }, $rows);
    }

    public function createChatMessage(int $userId, string $message): array
    {
        $message = trim($message);
        if ($message === '') {
            throw new InvalidArgumentException('Mesaj boş olamaz.');
        }

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $stmt = $this->db->prepare('INSERT INTO chat_messages (user_id, message, created_at) VALUES (:user, :message, :created)');
        $stmt->execute([
            ':user' => $userId,
            ':message' => $message,
            ':created' => $now,
        ]);

        return $this->getChatMessage((int) $this->db->lastInsertId());
    }

    public function getChatMessage(int $id): array
    {
        $stmt = $this->db->prepare('SELECT chat_messages.*, users.username, users.avatar_url FROM chat_messages INNER JOIN users ON users.id = chat_messages.user_id WHERE chat_messages.id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new InvalidArgumentException('Mesaj bulunamadı.');
        }

        $row['id'] = (int) $row['id'];
        $row['user_id'] = (int) $row['user_id'];
        return $row;
    }
}
