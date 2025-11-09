<?php

namespace MangaDiyari\Core;

use DateInterval;
use DateTimeImmutable;
use InvalidArgumentException;
use PDO;
use PDOException;

class KiRepository
{
    private string $driver;

    public function __construct(private PDO $db)
    {
        $this->driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
    }

    public function getBalance(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT ki_balance FROM users WHERE id = :id');
        $stmt->execute([':id' => $userId]);
        $balance = $stmt->fetchColumn();

        if ($balance === false) {
            throw new InvalidArgumentException('Kullanıcı bulunamadı.');
        }

        return (int) $balance;
    }

    public function adjustBalance(int $userId, int $amount, string $type, string $description = '', array $context = []): int
    {
        if ($amount === 0) {
            return $this->getBalance($userId);
        }

        $now = new DateTimeImmutable();

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT ki_balance FROM users WHERE id = :id FOR UPDATE');
            if ($this->driver !== 'mysql') {
                $stmt = $this->db->prepare('SELECT ki_balance FROM users WHERE id = :id');
            }
            $stmt->execute([':id' => $userId]);
            $current = $stmt->fetchColumn();
            if ($current === false) {
                throw new InvalidArgumentException('Kullanıcı bulunamadı.');
            }

            $newBalance = (int) $current + $amount;
            if ($newBalance < 0) {
                throw new InvalidArgumentException('Yetersiz Ki bakiyesi.');
            }

            $update = $this->db->prepare('UPDATE users SET ki_balance = :balance WHERE id = :id');
            $update->execute([
                ':balance' => $newBalance,
                ':id' => $userId,
            ]);

            $this->logTransaction($userId, $amount, $type, $description, $context, $now);

            $this->db->commit();
        } catch (PDOException $exception) {
            $this->db->rollBack();
            throw $exception;
        } catch (InvalidArgumentException $exception) {
            $this->db->rollBack();
            throw $exception;
        }

        return $this->getBalance($userId);
    }

    public function hasUnlocked(int $userId, int $chapterId): bool
    {
        $stmt = $this->db->prepare('SELECT expires_at FROM chapter_unlocks WHERE user_id = :user AND chapter_id = :chapter');
        $stmt->execute([
            ':user' => $userId,
            ':chapter' => $chapterId,
        ]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return false;
        }

        if (empty($row['expires_at'])) {
            return true;
        }

        return new DateTimeImmutable($row['expires_at']) > new DateTimeImmutable();
    }

    public function unlockChapter(int $userId, int $chapterId, int $cost, ?DateTimeImmutable $expiresAt = null): void
    {
        if ($cost < 0) {
            throw new InvalidArgumentException('Ki maliyeti geçersiz.');
        }

        $now = new DateTimeImmutable();

        $this->db->beginTransaction();
        try {
            $balanceStmt = $this->db->prepare('SELECT ki_balance FROM users WHERE id = :id');
            if ($this->driver === 'mysql') {
                $balanceStmt = $this->db->prepare('SELECT ki_balance FROM users WHERE id = :id FOR UPDATE');
            }
            $balanceStmt->execute([':id' => $userId]);
            $balance = $balanceStmt->fetchColumn();
            if ($balance === false) {
                throw new InvalidArgumentException('Kullanıcı bulunamadı.');
            }

            $balance = (int) $balance;
            if ($balance < $cost) {
                throw new InvalidArgumentException('Yetersiz Ki bakiyesi.');
            }

            $newBalance = $balance - $cost;
            $update = $this->db->prepare('UPDATE users SET ki_balance = :balance WHERE id = :id');
            $update->execute([
                ':balance' => $newBalance,
                ':id' => $userId,
            ]);

            $expiresAtValue = $expiresAt?->format('Y-m-d H:i:s');

            if ($this->driver === 'mysql') {
                $unlockSql = 'INSERT INTO chapter_unlocks (user_id, chapter_id, spent_ki, unlocked_at, expires_at) VALUES (:user, :chapter, :spent, :unlocked, :expires)
                    ON DUPLICATE KEY UPDATE spent_ki = VALUES(spent_ki), unlocked_at = VALUES(unlocked_at), expires_at = VALUES(expires_at)';
            } else {
                $unlockSql = 'INSERT INTO chapter_unlocks (user_id, chapter_id, spent_ki, unlocked_at, expires_at) VALUES (:user, :chapter, :spent, :unlocked, :expires)
                    ON CONFLICT(user_id, chapter_id) DO UPDATE SET spent_ki = excluded.spent_ki, unlocked_at = excluded.unlocked_at, expires_at = excluded.expires_at';
            }

            $unlockStmt = $this->db->prepare($unlockSql);
            $unlockStmt->execute([
                ':user' => $userId,
                ':chapter' => $chapterId,
                ':spent' => $cost,
                ':unlocked' => $now->format('Y-m-d H:i:s'),
                ':expires' => $expiresAtValue,
            ]);

            $this->logTransaction(
                $userId,
                -$cost,
                'chapter_unlock',
                'Bölüm kilidi açıldı',
                ['chapter_id' => $chapterId, 'expires_at' => $expiresAtValue],
                $now
            );

            $this->db->commit();
        } catch (PDOException $exception) {
            $this->db->rollBack();
            throw $exception;
        } catch (InvalidArgumentException $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function grantForComment(int $userId, int $amount, int $commentId): int
    {
        return $this->adjustBalance($userId, $amount, 'comment_reward', 'Yorum ödülü', ['comment_id' => $commentId]);
    }

    public function grantForReaction(int $userId, int $amount, int $commentId): int
    {
        return $this->adjustBalance($userId, $amount, 'reaction_reward', 'Tepki ödülü', ['comment_id' => $commentId]);
    }

    public function grantForSession(int $userId, int $amount, string $type): int
    {
        return $this->adjustBalance($userId, $amount, $type, ucfirst($type) . ' ödülü');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listMarketOffers(bool $onlyActive = true): array
    {
        $query = 'SELECT * FROM market_offers';
        if ($onlyActive) {
            $query .= ' WHERE is_active = 1';
        }
        $query .= ' ORDER BY ki_amount ASC';

        $stmt = $this->db->query($query);
        return array_map(static function (array $row): array {
            $row['id'] = (int) $row['id'];
            $row['ki_amount'] = (int) $row['ki_amount'];
            $row['is_active'] = (int) $row['is_active'];
            $row['price'] = (float) $row['price'];
            return $row;
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function saveMarketOffer(array $data): array
    {
        $title = trim($data['title'] ?? '');
        $kiAmount = (int) ($data['ki_amount'] ?? 0);
        $price = (float) ($data['price'] ?? 0);
        $currency = strtoupper(trim($data['currency'] ?? 'TRY'));
        $isActive = (int) (!empty($data['is_active']));
        $now = new DateTimeImmutable();

        if ($title === '' || $kiAmount <= 0) {
            throw new InvalidArgumentException('Geçerli bir market teklifi giriniz.');
        }

        if (!in_array($currency, ['TRY', 'USD', 'EUR'], true)) {
            $currency = 'TRY';
        }

        if (!empty($data['id'])) {
            $stmt = $this->db->prepare('UPDATE market_offers SET title = :title, ki_amount = :amount, price = :price, currency = :currency, is_active = :active, updated_at = :updated WHERE id = :id');
            $stmt->execute([
                ':id' => (int) $data['id'],
                ':title' => $title,
                ':amount' => $kiAmount,
                ':price' => $price,
                ':currency' => $currency,
                ':active' => $isActive,
                ':updated' => $now->format('Y-m-d H:i:s'),
            ]);

            return $this->findMarketOffer((int) $data['id']);
        }

        $stmt = $this->db->prepare('INSERT INTO market_offers (title, ki_amount, price, currency, is_active, created_at, updated_at) VALUES (:title, :amount, :price, :currency, :active, :created, :updated)');
        $stmt->execute([
            ':title' => $title,
            ':amount' => $kiAmount,
            ':price' => $price,
            ':currency' => $currency,
            ':active' => $isActive,
            ':created' => $now->format('Y-m-d H:i:s'),
            ':updated' => $now->format('Y-m-d H:i:s'),
        ]);

        return $this->findMarketOffer((int) $this->db->lastInsertId());
    }

    public function deleteMarketOffer(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM market_offers WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function findMarketOffer(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM market_offers WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $offer = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$offer) {
            throw new InvalidArgumentException('Market teklifi bulunamadı.');
        }

        $offer['id'] = (int) $offer['id'];
        $offer['ki_amount'] = (int) $offer['ki_amount'];
        $offer['price'] = (float) $offer['price'];
        $offer['is_active'] = (int) $offer['is_active'];

        return $offer;
    }

    public function getTransactions(int $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare('SELECT * FROM ki_transactions WHERE user_id = :id ORDER BY created_at DESC LIMIT :limit');
        $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
        $stmt->execute();

        return array_map(static function (array $row): array {
            $row['id'] = (int) $row['id'];
            $row['user_id'] = (int) $row['user_id'];
            $row['amount'] = (int) $row['amount'];
            $row['context'] = $row['context'] ? json_decode((string) $row['context'], true) ?: [] : [];
            return $row;
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function logTransaction(int $userId, int $amount, string $type, string $description, array $context, DateTimeImmutable $time): void
    {
        $stmt = $this->db->prepare('INSERT INTO ki_transactions (user_id, amount, type, description, context, created_at) VALUES (:user, :amount, :type, :description, :context, :created)');
        $stmt->execute([
            ':user' => $userId,
            ':amount' => $amount,
            ':type' => $type,
            ':description' => $description,
            ':context' => $context ? json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            ':created' => $time->format('Y-m-d H:i:s'),
        ]);
    }

    public function calculatePremiumExpiry(?int $durationHours): ?DateTimeImmutable
    {
        if ($durationHours === null || $durationHours <= 0) {
            return null;
        }

        return (new DateTimeImmutable())->add(new DateInterval('PT' . $durationHours . 'H'));
    }
}
