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

        $stmt = $this->pdo->prepare('INSERT INTO users (username, email, password_hash, role, created_at, updated_at) VALUES (:username, :email, :password, :role, :created, :updated)');

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt->execute([
                ':username' => $username,
                ':email' => $email,
                ':password' => $passwordHash,
                ':role' => $role,
                ':created' => $now->format(DateTimeImmutable::ATOM),
                ':updated' => $now->format(DateTimeImmutable::ATOM),
            ]);
        } catch (PDOException $exception) {
            throw new InvalidArgumentException('Kullanıcı oluşturulamadı: ' . $exception->getMessage(), previous: $exception);
        }

        return $this->findById((int) $this->pdo->lastInsertId());
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, email, role, password_hash FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, email, role, password_hash FROM users WHERE LOWER(email) = LOWER(:email) LIMIT 1');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, username, email, role, password_hash FROM users WHERE username = :username LIMIT 1');
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    public function verifyCredentials(string $login, string $password): ?array
    {
        $user = filter_var($login, FILTER_VALIDATE_EMAIL)
            ? $this->findByEmail($login)
            : $this->findByUsername($login);

        if (!$user) {
            return null;
        }

        if (!password_verify($password, $user['password_hash'])) {
            return null;
        }

        return $user;
    }
}
