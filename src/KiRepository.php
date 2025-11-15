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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listPaymentMethods(bool $onlyActive = true): array
    {
        $query = 'SELECT * FROM payment_methods';
        if ($onlyActive) {
            $query .= ' WHERE is_active = 1';
        }
        $query .= ' ORDER BY sort_order ASC, name ASC';

        $stmt = $this->db->query($query);

        return array_map(static function (array $row): array {
            $row['id'] = (int) $row['id'];
            $row['is_active'] = (int) $row['is_active'];
            $row['sort_order'] = (int) $row['sort_order'];
            return $row;
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function savePaymentMethod(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $methodKey = trim((string) ($data['method_key'] ?? ''));
        $instructions = trim((string) ($data['instructions'] ?? ''));
        $isActive = !empty($data['is_active']) ? 1 : 0;
        $sortOrder = (int) ($data['sort_order'] ?? 0);
        $now = new DateTimeImmutable();

        if ($name === '') {
            throw new InvalidArgumentException('Ödeme yöntemi adı gereklidir.');
        }

        if ($methodKey === '') {
            $methodKey = Slugger::slugify($name);
        } else {
            $methodKey = Slugger::slugify($methodKey);
        }

        $id = isset($data['id']) ? (int) $data['id'] : null;
        $methodKey = $this->ensureUniquePaymentMethodKey($methodKey, $id);

        if ($id) {
            $stmt = $this->db->prepare('UPDATE payment_methods SET name = :name, method_key = :key, instructions = :instructions, is_active = :active, sort_order = :sort_order, updated_at = :updated WHERE id = :id');
            $stmt->execute([
                ':id' => $id,
                ':name' => $name,
                ':key' => $methodKey,
                ':instructions' => $instructions,
                ':active' => $isActive,
                ':sort_order' => $sortOrder,
                ':updated' => $now->format('Y-m-d H:i:s'),
            ]);

            return $this->findPaymentMethod($id);
        }

        $stmt = $this->db->prepare('INSERT INTO payment_methods (name, method_key, instructions, is_active, sort_order, created_at, updated_at) VALUES (:name, :key, :instructions, :active, :sort_order, :created, :updated)');
        $stmt->execute([
            ':name' => $name,
            ':key' => $methodKey,
            ':instructions' => $instructions,
            ':active' => $isActive,
            ':sort_order' => $sortOrder,
            ':created' => $now->format('Y-m-d H:i:s'),
            ':updated' => $now->format('Y-m-d H:i:s'),
        ]);

        return $this->findPaymentMethod((int) $this->db->lastInsertId());
    }

    public function deletePaymentMethod(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM payment_methods WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }

    public function findPaymentMethod(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM payment_methods WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $method = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$method) {
            throw new InvalidArgumentException('Ödeme yöntemi bulunamadı.');
        }

        $method['id'] = (int) $method['id'];
        $method['is_active'] = (int) $method['is_active'];
        $method['sort_order'] = (int) $method['sort_order'];

        return $method;
    }

    private function ensureUniquePaymentMethodKey(string $key, ?int $ignoreId = null): string
    {
        $base = $key !== '' ? $key : 'odeme';
        $candidate = $base;
        $suffix = 1;

        while ($this->paymentMethodKeyExists($candidate, $ignoreId)) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function paymentMethodKeyExists(string $key, ?int $ignoreId = null): bool
    {
        $query = 'SELECT id FROM payment_methods WHERE method_key = :key';
        $params = [':key' => $key];

        if ($ignoreId !== null) {
            $query .= ' AND id != :id';
            $params[':id'] = $ignoreId;
        }

        $stmt = $this->db->prepare($query . ' LIMIT 1');
        $stmt->execute($params);

        return $stmt->fetch(PDO::FETCH_ASSOC) !== false;
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

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listMarketOrders(array $filters = []): array
    {
        $query = 'SELECT o.*, mo.title AS offer_title, pm.name AS payment_method_name, u.username AS username'
            . ' FROM market_orders o'
            . ' LEFT JOIN market_offers mo ON mo.id = o.offer_id'
            . ' LEFT JOIN payment_methods pm ON pm.id = o.payment_method_id'
            . ' LEFT JOIN users u ON u.id = o.user_id';

        $conditions = [];
        $params = [];

        if (!empty($filters['status']) && is_string($filters['status'])) {
            $conditions[] = 'o.status = :status';
            $params[':status'] = $filters['status'];
        }

        if ($conditions) {
            $query .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $query .= ' ORDER BY o.created_at DESC';

        $stmt = $this->db->prepare($query);
        $stmt->execute($params);

        return array_map(static function (array $row): array {
            $row['id'] = (int) $row['id'];
            $row['offer_id'] = isset($row['offer_id']) ? (int) $row['offer_id'] : null;
            $row['user_id'] = isset($row['user_id']) ? (int) $row['user_id'] : null;
            $row['payment_method_id'] = isset($row['payment_method_id']) ? (int) $row['payment_method_id'] : null;
            $row['amount'] = (float) $row['amount'];
            $row['ki_amount'] = (int) $row['ki_amount'];
            return $row;
        }, $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function createMarketOrder(array $data): array
    {
        $offerId = isset($data['offer_id']) ? (int) $data['offer_id'] : null;
        $userId = isset($data['user_id']) ? (int) $data['user_id'] : null;
        $paymentMethodId = isset($data['payment_method_id']) ? (int) $data['payment_method_id'] : null;
        $buyerName = trim((string) ($data['buyer_name'] ?? '')) ?: null;
        $buyerEmail = trim((string) ($data['buyer_email'] ?? '')) ?: null;
        $amount = (float) ($data['amount'] ?? 0);
        $currency = strtoupper(trim((string) ($data['currency'] ?? 'TRY')));
        $kiAmount = max(0, (int) ($data['ki_amount'] ?? 0));
        $status = $this->sanitizeOrderStatus($data['status'] ?? 'pending');
        $reference = trim((string) ($data['reference'] ?? '')) ?: null;
        $notes = trim((string) ($data['notes'] ?? '')) ?: null;
        $now = new DateTimeImmutable();

        $offerId = $offerId ?: null;
        $userId = $userId ?: null;
        $paymentMethodId = $paymentMethodId ?: null;

        $completedAt = null;
        if ($status === 'completed') {
            $completedAt = $now->format('Y-m-d H:i:s');
        }

        $stmt = $this->db->prepare('INSERT INTO market_orders (offer_id, user_id, payment_method_id, buyer_name, buyer_email, amount, currency, ki_amount, status, reference, notes, created_at, updated_at, completed_at) VALUES (:offer_id, :user_id, :payment_method_id, :buyer_name, :buyer_email, :amount, :currency, :ki_amount, :status, :reference, :notes, :created_at, :updated_at, :completed_at)');
        $stmt->execute([
            ':offer_id' => $offerId,
            ':user_id' => $userId,
            ':payment_method_id' => $paymentMethodId,
            ':buyer_name' => $buyerName,
            ':buyer_email' => $buyerEmail,
            ':amount' => $amount,
            ':currency' => $currency,
            ':ki_amount' => $kiAmount,
            ':status' => $status,
            ':reference' => $reference,
            ':notes' => $notes,
            ':created_at' => $now->format('Y-m-d H:i:s'),
            ':updated_at' => $now->format('Y-m-d H:i:s'),
            ':completed_at' => $completedAt,
        ]);

        $orderId = (int) $this->db->lastInsertId();

        if ($status === 'completed' && $userId && $kiAmount > 0) {
            $this->adjustBalance($userId, $kiAmount, 'market_purchase', 'Market satın alımı', ['order_id' => $orderId]);
        }

        return $this->findMarketOrder($orderId);
    }

    public function updateMarketOrder(int $id, array $data): array
    {
        $existing = $this->findMarketOrder($id);

        $offerId = isset($data['offer_id']) ? (int) $data['offer_id'] : ($existing['offer_id'] ?? null);
        $userId = isset($data['user_id']) ? (int) $data['user_id'] : ($existing['user_id'] ?? null);
        $paymentMethodId = isset($data['payment_method_id']) ? (int) $data['payment_method_id'] : ($existing['payment_method_id'] ?? null);
        $buyerName = trim((string) ($data['buyer_name'] ?? $existing['buyer_name'] ?? '')) ?: null;
        $buyerEmail = trim((string) ($data['buyer_email'] ?? $existing['buyer_email'] ?? '')) ?: null;
        $amount = isset($data['amount']) ? (float) $data['amount'] : (float) $existing['amount'];
        $currency = isset($data['currency']) ? strtoupper(trim((string) $data['currency'])) : ($existing['currency'] ?? 'TRY');
        $kiAmount = isset($data['ki_amount']) ? max(0, (int) $data['ki_amount']) : (int) $existing['ki_amount'];
        $status = $this->sanitizeOrderStatus($data['status'] ?? $existing['status']);
        $reference = trim((string) ($data['reference'] ?? $existing['reference'] ?? '')) ?: null;
        $notes = trim((string) ($data['notes'] ?? $existing['notes'] ?? '')) ?: null;
        $now = new DateTimeImmutable();

        $offerId = $offerId ?: null;
        $userId = $userId ?: null;
        $paymentMethodId = $paymentMethodId ?: null;

        $completedAt = $existing['completed_at'] ?? null;
        $shouldCredit = false;
        if ($status === 'completed' && $existing['status'] !== 'completed') {
            $completedAt = $now->format('Y-m-d H:i:s');
            $shouldCredit = true;
        }

        if ($status !== 'completed') {
            $completedAt = null;
        }

        $stmt = $this->db->prepare('UPDATE market_orders SET offer_id = :offer_id, user_id = :user_id, payment_method_id = :payment_method_id, buyer_name = :buyer_name, buyer_email = :buyer_email, amount = :amount, currency = :currency, ki_amount = :ki_amount, status = :status, reference = :reference, notes = :notes, updated_at = :updated, completed_at = :completed_at WHERE id = :id');
        $stmt->execute([
            ':id' => $id,
            ':offer_id' => $offerId,
            ':user_id' => $userId,
            ':payment_method_id' => $paymentMethodId,
            ':buyer_name' => $buyerName,
            ':buyer_email' => $buyerEmail,
            ':amount' => $amount,
            ':currency' => $currency,
            ':ki_amount' => $kiAmount,
            ':status' => $status,
            ':reference' => $reference,
            ':notes' => $notes,
            ':updated' => $now->format('Y-m-d H:i:s'),
            ':completed_at' => $completedAt,
        ]);

        if ($shouldCredit && $userId && $kiAmount > 0) {
            $this->adjustBalance($userId, $kiAmount, 'market_purchase', 'Market satın alımı', ['order_id' => $id]);
        }

        return $this->findMarketOrder($id);
    }

    public function findMarketOrder(int $id): array
    {
        $stmt = $this->db->prepare('SELECT * FROM market_orders WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            throw new InvalidArgumentException('Sipariş bulunamadı.');
        }

        $order['id'] = (int) $order['id'];
        $order['offer_id'] = isset($order['offer_id']) ? (int) $order['offer_id'] : null;
        $order['user_id'] = isset($order['user_id']) ? (int) $order['user_id'] : null;
        $order['payment_method_id'] = isset($order['payment_method_id']) ? (int) $order['payment_method_id'] : null;
        $order['amount'] = (float) $order['amount'];
        $order['ki_amount'] = (int) $order['ki_amount'];

        return $order;
    }

    private function sanitizeOrderStatus(string $status): string
    {
        $status = strtolower(trim($status));
        $allowed = ['pending', 'processing', 'completed', 'cancelled'];

        return in_array($status, $allowed, true) ? $status : 'pending';
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
