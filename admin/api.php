<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/MangaRepository.php';
require_once __DIR__ . '/../src/ChapterRepository.php';
require_once __DIR__ . '/../src/Slugger.php';

use MangaDiyari\Core\Auth;
use MangaDiyari\Core\Database;
use MangaDiyari\Core\MangaRepository;
use MangaDiyari\Core\ChapterRepository;

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
