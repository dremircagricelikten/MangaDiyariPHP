<?php

namespace MangaDiyari\Core;

use DateTimeImmutable;
use InvalidArgumentException;
use PDO;
use PDOException;

class UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function create(array $data): array
    {
        $username = trim($data['username'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'member';
        $bio = trim($data['bio'] ?? '');
        $avatar = trim($data['avatar_url'] ?? '');
        $website = trim($data['website_url'] ?? '');
        $isActive = array_key_exists('is_active', $data) ? (int) $data['is_active'] : 1;

        if ($username === '' || $email === '' || $password === '') {
            throw new InvalidArgumentException('Kullanıcı adı, e-posta ve parola zorunludur.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Geçerli bir e-posta adresi giriniz.');
        }

        if ($this->findByEmail($email)) {
            throw new InvalidArgumentException('Bu e-posta adresi zaten kayıtlı.');
        }

        if ($this->findByUsername($username)) {
            throw new InvalidArgumentException('Bu kullanıcı adı zaten kullanılıyor.');
        }

        $now = new DateTimeImmutable();

        $stmt = $this->pdo->prepare('INSERT INTO users (username, email, password_hash, role, bio, avatar_url, website_url, is_active, created_at, updated_at) VALUES (:username, :email, :password, :role, :bio, :avatar, :website, :is_active, :created, :updated)');

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $passwordHash,
                ':role' => $role,
                ':bio' => $bio,
                ':avatar' => $avatar,
                ':website' => $website,
                ':is_active' => $isActive ? 1 : 0,
                ':created' => $now->format('Y-m-d H:i:s'),
                ':updated' => $now->format('Y-m-d H:i:s'),
            ]);
        } catch (PDOException $exception) {
            throw new InvalidArgumentException('Kullanıcı oluşturulamadı: ' . $exception->getMessage(), previous: $exception);
        }

        $user = $this->findById((int) $this->pdo->lastInsertId());
        if (!$user) {
            throw new InvalidArgumentException('Kullanıcı oluşturulamadı.');
        }

        unset($user['password_hash']);

        return $user;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, email, role, bio, avatar_url, website_url, is_active, password_hash, created_at, updated_at FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, email, role, bio, avatar_url, website_url, is_active, password_hash, created_at, updated_at FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, email, role, bio, avatar_url, website_url, is_active, password_hash, created_at, updated_at FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function verifyCredentials(string $login, string $password): ?array
    {
        $user = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? $this->findByEmail($login)
            : $this->findByUsername($login);

        if (!$user || (int) ($user['is_active'] ?? 1) !== 1) {
            return null;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        return $user;
    }

    public function all(): array
    {
        $stmt = $this->pdo->query('SELECT id, username, email, role, bio, avatar_url, website_url, is_active, created_at, updated_at FROM users ORDER BY created_at DESC');

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function updateProfile(int $id, array $data): array
    {
        $user = $this->findById($id);
        if (!$user) {
            throw new InvalidArgumentException('Kullanıcı bulunamadı.');
        }

        $email = strtolower(trim($data['email'] ?? $user['email'] ?? ''));
        $bio = trim($data['bio'] ?? ($user['bio'] ?? ''));
        $avatar = trim($data['avatar_url'] ?? ($user['avatar_url'] ?? ''));
        $website = trim($data['website_url'] ?? ($user['website_url'] ?? ''));

        if ($email === '') {
            throw new InvalidArgumentException('E-posta adresi boş olamaz.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Geçerli bir e-posta adresi giriniz.');
        }

        $existing = $this->findByEmail($email);
        if ($existing && (int) $existing['id'] !== $id) {
            throw new InvalidArgumentException('Bu e-posta adresi başka bir üyeye ait.');
        }

        $now = new DateTimeImmutable();
        $stmt = $this->pdo->prepare('UPDATE users SET email = :email, bio = :bio, avatar_url = :avatar, website_url = :website, updated_at = :updated WHERE id = :id');
        $stmt->execute([
            ':email' => $email,
            ':bio' => $bio,
            ':avatar' => $avatar,
            ':website' => $website,
            ':updated' => $now->format('Y-m-d H:i:s'),
            ':id' => $id,
        ]);

        $updated = $this->findById($id);
        if (!$updated) {
            throw new InvalidArgumentException('Profil güncellenemedi.');
        }

        unset($updated['password_hash']);

        return $updated;
    }

    public function updateRole(int $id, string $role): array
    {
        $allowed = ['admin', 'editor', 'member'];
        if (!in_array($role, $allowed, true)) {
            throw new InvalidArgumentException('Geçersiz rol seçimi.');
        }

        $user = $this->findById($id);
        if (!$user) {
            throw new InvalidArgumentException('Kullanıcı bulunamadı.');
        }

        $now = new DateTimeImmutable();
        $stmt = $this->pdo->prepare('UPDATE users SET role = :role, updated_at = :updated WHERE id = :id');
        $stmt->execute([
            ':role' => $role,
            ':updated' => $now->format('Y-m-d H:i:s'),
            ':id' => $id,
        ]);

        $updated = $this->findById($id);
        if (!$updated) {
            throw new InvalidArgumentException('Kullanıcı güncellenemedi.');
        }

        unset($updated['password_hash']);

        return $updated;
    }

    public function updateCredentials(int $id, ?string $role = null, ?bool $isActive = null, ?string $password = null): array
    {
        $user = $this->findById($id);
        if (!$user) {
            throw new InvalidArgumentException('Kullanıcı bulunamadı.');
        }

        $now = new DateTimeImmutable();
        $fields = ['updated_at = :updated'];
        $params = [
            ':updated' => $now->format('Y-m-d H:i:s'),
            ':id' => $id,
        ];

        if ($role !== null) {
            if (!in_array($role, ['admin', 'editor', 'member'], true)) {
                throw new InvalidArgumentException('Geçersiz rol seçimi.');
            }
            $fields[] = 'role = :role';
            $params[':role'] = $role;
        }

        if ($isActive !== null) {
            $fields[] = 'is_active = :is_active';
            $params[':is_active'] = $isActive ? 1 : 0;
        }

        if ($password !== null && $password !== '') {
            $fields[] = 'password_hash = :password';
            $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $updated = $this->findById($id);
        if (!$updated) {
            throw new InvalidArgumentException('Kullanıcı güncellenemedi.');
        }

        unset($updated['password_hash']);

        return $updated;
    }
}
