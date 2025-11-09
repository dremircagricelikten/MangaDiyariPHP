<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/MangaRepository.php';
require_once __DIR__ . '/../src/ChapterRepository.php';
require_once __DIR__ . '/../src/Slugger.php';
require_once __DIR__ . '/../src/WidgetRepository.php';

use MangaDiyari\Core\Database;
use MangaDiyari\Core\MangaRepository;
use MangaDiyari\Core\ChapterRepository;
use MangaDiyari\Core\WidgetRepository;

try {
    $pdo = Database::getConnection();
    $mangaRepo = new MangaRepository($pdo);
    $chapterRepo = new ChapterRepository($pdo);
    $widgetRepo = new WidgetRepository($pdo);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            $filters = [
                'search' => $_GET['search'] ?? '',
                'status' => $_GET['status'] ?? '',
            ];
            $mangas = $mangaRepo->list($filters);
            echo json_encode(['data' => $mangas]);
            break;
        case 'featured':
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 5;
            $mangas = $mangaRepo->getFeatured($limit);
            echo json_encode(['data' => $mangas]);
            break;
        case 'popular':
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 5;
            $sort = $_GET['sort'] ?? 'random';
            $status = $_GET['status'] ?? '';
            $mangas = $mangaRepo->getPopular([
                'limit' => $limit,
                'sort' => $sort,
                'status' => $status ?: null,
            ]);
            echo json_encode(['data' => $mangas]);
            break;
        case 'manga':
            $slug = $_GET['slug'] ?? '';
            $manga = $mangaRepo->findBySlug($slug);
            if (!$manga) {
                http_response_code(404);
                echo json_encode(['error' => 'Manga bulunamadı']);
                break;
            }
            $chapters = $chapterRepo->listByManga((int) $manga['id']);
            echo json_encode(['data' => $manga, 'chapters' => $chapters]);
            break;
        case 'chapter':
            $slug = $_GET['slug'] ?? '';
            $chapterNumber = $_GET['chapter'] ?? '';
            $manga = $mangaRepo->findBySlug($slug);
            if (!$manga) {
                http_response_code(404);
                echo json_encode(['error' => 'Manga bulunamadı']);
                break;
            }
            $chapter = $chapterRepo->findByMangaAndNumber((int) $manga['id'], $chapterNumber);
            if (!$chapter) {
                http_response_code(404);
                echo json_encode(['error' => 'Bölüm bulunamadı']);
                break;
            }
            $prev = $chapterRepo->getPreviousChapter((int) $manga['id'], (float) $chapter['number']);
            $next = $chapterRepo->getNextChapter((int) $manga['id'], (float) $chapter['number']);
            echo json_encode([
                'data' => $chapter,
                'manga' => $manga,
                'prev' => $prev,
                'next' => $next,
            ]);
            break;
        case 'latest-chapters':
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 8;
            $sort = $_GET['sort'] ?? 'newest';
            $status = $_GET['status'] ?? '';
            $chapters = $chapterRepo->getLatestChapters($limit, [
                'sort' => $sort,
                'status' => $status ?: null,
            ]);
            echo json_encode(['data' => $chapters]);
            break;
        case 'widgets':
            $widgets = $widgetRepo->getActive();
            echo json_encode(['data' => $widgets]);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Geçersiz istek']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
