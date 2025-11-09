<?php

namespace MangaDiyari\Core;

class Auth
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function user(): ?array
    {
        self::start();
        return $_SESSION['user'] ?? null;
    }

    public static function login(array $user): void
    {
        self::start();
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];
    }

    public static function logout(): void
    {
        self::start();
        unset($_SESSION['user']);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function checkRole(array|string $roles): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }

        $roles = (array) $roles;

        return in_array($user['role'], $roles, true);
    }

    public static function requireRole(array|string $roles): void
    {
        if (!self::checkRole($roles)) {
            http_response_code(403);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Yetkisiz erişim']);
            exit;
        }
    }
}
