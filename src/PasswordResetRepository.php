<?php

namespace MangaDiyari\Core;

use DateTimeImmutable;
use DateTimeInterface;
use PDO;

class PasswordResetRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function createToken(int $userId, string $token, DateTimeInterface $expiresAt): void
    {
        $this->deleteByUser($userId);

        $stmt = $this->db->prepare('INSERT INTO password_resets (user_id, token_hash, created_at, expires_at)
            VALUES (:user, :hash, :created, :expires)');

        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $stmt->execute([
            ':user' => $userId,
            ':hash' => $this->hashToken($token),
            ':created' => $now,
            ':expires' => $expiresAt->format('Y-m-d H:i:s'),
        ]);
    }

    public function findValidToken(string $token): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM password_resets WHERE token_hash = :hash LIMIT 1');
        $stmt->execute([':hash' => $this->hashToken($token)]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return null;
        }

        $expiresAt = new DateTimeImmutable($row['expires_at']);
        if ($expiresAt < new DateTimeImmutable()) {
            $this->deleteByToken($token);
            return null;
        }

        $row['id'] = (int) $row['id'];
        $row['user_id'] = (int) $row['user_id'];

        return $row;
    }

    public function deleteByUser(int $userId): void
    {
        $stmt = $this->db->prepare('DELETE FROM password_resets WHERE user_id = :user');
        $stmt->execute([':user' => $userId]);
    }

    public function deleteByToken(string $token): void
    {
        $stmt = $this->db->prepare('DELETE FROM password_resets WHERE token_hash = :hash');
        $stmt->execute([':hash' => $this->hashToken($token)]);
    }

    private function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }
}
