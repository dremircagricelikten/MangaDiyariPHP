<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/MangaRepository.php';
require_once __DIR__ . '/../src/ChapterRepository.php';
require_once __DIR__ . '/../src/Slugger.php';
require_once __DIR__ . '/../src/SettingRepository.php';
require_once __DIR__ . '/../src/WidgetRepository.php';
require_once __DIR__ . '/../src/UserRepository.php';

use MangaDiyari\Core\Auth;
use MangaDiyari\Core\Database;
use MangaDiyari\Core\MangaRepository;
use MangaDiyari\Core\ChapterRepository;
use MangaDiyari\Core\SettingRepository;
use MangaDiyari\Core\WidgetRepository;
use MangaDiyari\Core\UserRepository;

Auth::start();
if (!Auth::checkRole(['admin', 'editor'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Yetkisiz erişim']);
    exit;
}

try {
    $pdo = Database::getConnection();
    $mangaRepo = new MangaRepository($pdo);
    $chapterRepo = new ChapterRepository($pdo);
    $settingRepo = new SettingRepository($pdo);
    $widgetRepo = new WidgetRepository($pdo);
    $userRepo = new UserRepository($pdo);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'create-manga':
            $required = ['title'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new InvalidArgumentException('Eksik alan: ' . $field);
                }
            }

            $manga = $mangaRepo->create([
                'title' => trim($_POST['title']),
                'description' => trim($_POST['description'] ?? ''),
                'cover_image' => trim($_POST['cover_image'] ?? ''),
                'author' => trim($_POST['author'] ?? ''),
                'status' => $_POST['status'] ?? 'ongoing',
                'genres' => trim($_POST['genres'] ?? ''),
                'tags' => trim($_POST['tags'] ?? ''),
            ]);

            echo json_encode(['message' => 'Seri başarıyla oluşturuldu', 'manga' => $manga]);
            break;
        case 'create-chapter':
            $required = ['manga_id', 'number'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new InvalidArgumentException('Eksik alan: ' . $field);
                }
            }

            $chapter = $chapterRepo->create((int) $_POST['manga_id'], [
                'number' => trim($_POST['number']),
                'title' => trim($_POST['title'] ?? ''),
                'content' => trim($_POST['content'] ?? ''),
            ]);

            echo json_encode(['message' => 'Bölüm başarıyla oluşturuldu', 'chapter' => $chapter]);
            break;
        case 'get-settings':
            $settings = $settingRepo->all();
            echo json_encode(['data' => $settings]);
            break;
        case 'update-settings':
            $allowed = ['primary_color', 'accent_color', 'background_color', 'gradient_start', 'gradient_end'];
            $updates = [];
            foreach ($allowed as $key) {
                if (isset($_POST[$key])) {
                    $value = trim((string) $_POST[$key]);
                    $settingRepo->set($key, $value);
                    $updates[$key] = $value;
                }
            }

            echo json_encode(['message' => 'Tema ayarları güncellendi', 'data' => $updates]);
            break;
        case 'list-widgets':
            $widgets = $widgetRepo->all();
            echo json_encode(['data' => $widgets]);
            break;
        case 'update-widget':
            $widgetId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($widgetId <= 0) {
                throw new InvalidArgumentException('Geçersiz widget');
            }

            $configPayload = $_POST['config'] ?? '{}';
            if (is_array($configPayload)) {
                $config = $configPayload;
            } else {
                $config = json_decode((string) $configPayload, true);
                if (!is_array($config)) {
                    throw new InvalidArgumentException('Widget yapılandırması çözümlenemedi');
                }
            }

            $updated = $widgetRepo->update($widgetId, [
                'title' => trim((string) ($_POST['title'] ?? '')),
                'enabled' => isset($_POST['enabled']) ? (int) $_POST['enabled'] : 0,
                'sort_order' => isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0,
                'config' => $config,
            ]);

            if (!$updated) {
                throw new InvalidArgumentException('Widget güncellenemedi');
            }

            echo json_encode(['message' => 'Widget güncellendi', 'widget' => $updated]);
            break;
        case 'list-users':
            $users = array_map(static function (array $user): array {
                unset($user['password_hash']);
                return $user;
            }, $userRepo->all());
            echo json_encode(['data' => $users]);
            break;
        case 'create-user':
            $user = $userRepo->create([
                'username' => trim((string) ($_POST['username'] ?? '')),
                'email' => trim((string) ($_POST['email'] ?? '')),
                'password' => (string) ($_POST['password'] ?? ''),
                'role' => (string) ($_POST['role'] ?? 'member'),
                'is_active' => isset($_POST['is_active']) ? (int) $_POST['is_active'] : 1,
            ]);
            echo json_encode(['message' => 'Yeni üye oluşturuldu', 'user' => $user]);
            break;
        case 'update-user':
            $userId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($userId <= 0) {
                throw new InvalidArgumentException('Geçersiz üye');
            }

            $role = isset($_POST['role']) ? (string) $_POST['role'] : null;
            $isActive = isset($_POST['is_active']) ? (bool) (int) $_POST['is_active'] : null;
            $password = isset($_POST['password']) ? (string) $_POST['password'] : null;

            $updated = $userRepo->updateCredentials($userId, $role, $isActive, $password);
            echo json_encode(['message' => 'Üye bilgileri güncellendi', 'user' => $updated]);
            break;
        case 'get-ftp-settings':
            $ftpDefaults = [
                'ftp_host' => '',
                'ftp_port' => '21',
                'ftp_username' => '',
                'ftp_password' => '',
                'ftp_root' => '/',
                'ftp_passive' => '1',
            ];
            $settings = $settingRepo->all();
            $ftpSettings = [];
            foreach ($ftpDefaults as $key => $default) {
                $ftpSettings[$key] = $settings[$key] ?? $default;
            }
            echo json_encode(['data' => $ftpSettings]);
            break;
        case 'update-ftp-settings':
            $keys = ['ftp_host', 'ftp_port', 'ftp_username', 'ftp_password', 'ftp_root', 'ftp_passive'];
            $updates = [];
            foreach ($keys as $key) {
                if (isset($_POST[$key])) {
                    $value = $key === 'ftp_password' ? (string) $_POST[$key] : trim((string) $_POST[$key]);
                    $settingRepo->set($key, $value);
                    $updates[$key] = $value;
                }
            }
            echo json_encode(['message' => 'FTP ayarları güncellendi', 'data' => $updates]);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Geçersiz işlem']);
    }
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
