<?php

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/MangaRepository.php';
require_once __DIR__ . '/../src/ChapterRepository.php';
require_once __DIR__ . '/../src/Slugger.php';
require_once __DIR__ . '/../src/WidgetRepository.php';
require_once __DIR__ . '/../src/SettingRepository.php';
require_once __DIR__ . '/../src/KiRepository.php';
require_once __DIR__ . '/../src/InteractionRepository.php';
require_once __DIR__ . '/../src/ReadingAnalyticsRepository.php';
require_once __DIR__ . '/../src/FollowRepository.php';

use MangaDiyari\Core\Auth;
use MangaDiyari\Core\Database;
use MangaDiyari\Core\MangaRepository;
use MangaDiyari\Core\ChapterRepository;
use MangaDiyari\Core\WidgetRepository;
use MangaDiyari\Core\SettingRepository;
use MangaDiyari\Core\KiRepository;
use MangaDiyari\Core\InteractionRepository;
use MangaDiyari\Core\ReadingAnalyticsRepository;
use MangaDiyari\Core\FollowRepository;

try {
    $pdo = Database::getConnection();
    $mangaRepo = new MangaRepository($pdo);
    $chapterRepo = new ChapterRepository($pdo);
    $widgetRepo = new WidgetRepository($pdo);
    $settingRepo = new SettingRepository($pdo);
    $kiRepo = new KiRepository($pdo);
    $interactionRepo = new InteractionRepository($pdo);
    $readingRepo = new ReadingAnalyticsRepository($pdo);
    $followRepo = new FollowRepository($pdo);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

$settings = $settingRepo->all();

Auth::start();
$sessionUser = Auth::user();
$currentUserId = $sessionUser['id'] ?? null;

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
            $mangaId = (int) $manga['id'];
            $chapters = $chapterRepo->listByManga($mangaId);
            $isFollowing = false;
            if ($currentUserId) {
                $isFollowing = $followRepo->isFollowing((int) $currentUserId, $mangaId);
            }
            $followers = $followRepo->countFollowers($mangaId);

            echo json_encode([
                'data' => $manga,
                'chapters' => $chapters,
                'follow' => [
                    'following' => $isFollowing,
                    'total' => $followers,
                ],
            ]);
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

            $access = resolveChapterAccess($chapter, $kiRepo, $currentUserId);
            if ($access['locked']) {
                http_response_code(402);
                echo json_encode([
                    'error' => 'Bölüm kilitli',
                    'access' => $access,
                    'chapter_id' => (int) $chapter['id'],
                    'manga' => $manga,
                ]);
                break;
            }
            $prev = $chapterRepo->getPreviousChapter((int) $manga['id'], (float) $chapter['number']);
            $next = $chapterRepo->getNextChapter((int) $manga['id'], (float) $chapter['number']);
            $readingRepo->recordChapterRead((int) $chapter['id'], (int) $manga['id'], $currentUserId, $_SERVER['REMOTE_ADDR'] ?? null, $_SERVER['HTTP_USER_AGENT'] ?? null);
            echo json_encode([
                'data' => $chapter,
                'manga' => $manga,
                'prev' => $prev,
                'next' => $next,
                'access' => $access,
            ]);
            break;
        case 'top-reads':
            $range = $_GET['range'] ?? 'weekly';
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 5;
            $limit = $limit > 0 ? $limit : 5;
            $chapters = $readingRepo->getTopChapters($range, $limit);
            $mangas = $readingRepo->getTopManga($range, $limit);
            echo json_encode(['data' => [
                'range' => $range,
                'chapters' => $chapters,
                'mangas' => $mangas,
            ]]);
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
        case 'follow-manga':
            requireLogin();
            $mangaId = isset($_POST['manga_id']) ? (int) $_POST['manga_id'] : 0;
            if ($mangaId <= 0) {
                throw new InvalidArgumentException('Geçersiz seri.');
            }

            $manga = $mangaRepo->findById($mangaId);
            if (!$manga) {
                throw new InvalidArgumentException('Seri bulunamadı.');
            }

            $followRepo->follow((int) $currentUserId, $mangaId);
            $followers = $followRepo->countFollowers($mangaId);

            echo json_encode([
                'message' => 'Seri takip listesine eklendi.',
                'followers' => $followers,
            ]);
            break;
        case 'unfollow-manga':
            requireLogin();
            $mangaId = isset($_POST['manga_id']) ? (int) $_POST['manga_id'] : 0;
            if ($mangaId <= 0) {
                throw new InvalidArgumentException('Geçersiz seri.');
            }

            $manga = $mangaRepo->findById($mangaId);
            if (!$manga) {
                throw new InvalidArgumentException('Seri bulunamadı.');
            }

            $followRepo->unfollow((int) $currentUserId, $mangaId);
            $followers = $followRepo->countFollowers($mangaId);

            echo json_encode([
                'message' => 'Seri takip listenizden kaldırıldı.',
                'followers' => $followers,
            ]);
            break;
        case 'reading-history':
            requireLogin();
            $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
            $limit = $limit > 0 ? min($limit, 50) : 10;
            $history = $readingRepo->getUserHistory((int) $currentUserId, $limit);
            echo json_encode(['data' => $history]);
            break;
        case 'widgets':
            $widgets = $widgetRepo->getActive();
            echo json_encode(['data' => $widgets]);
            break;
        case 'unlock-chapter':
            requireLogin();
            $chapterId = isset($_POST['chapter_id']) ? (int) $_POST['chapter_id'] : 0;
            if ($chapterId <= 0) {
                throw new InvalidArgumentException('Geçersiz bölüm.');
            }

            $chapter = $chapterRepo->findById($chapterId);
            if (!$chapter) {
                throw new InvalidArgumentException('Bölüm bulunamadı.');
            }

            $access = resolveChapterAccess($chapter, $kiRepo, $currentUserId);
            if (!$access['locked']) {
                echo json_encode(['message' => 'Bölüm zaten açık', 'access' => $access]);
                break;
            }

            $expiresAt = null;
            if (!empty($chapter['premium_expires_at'])) {
                $expiresAt = new DateTimeImmutable($chapter['premium_expires_at']);
            }

            $kiRepo->unlockChapter($currentUserId, (int) $chapter['id'], $access['required_ki'], $expiresAt);
            $balance = $kiRepo->getBalance($currentUserId);
            $_SESSION['user']['ki_balance'] = $balance;

            $access = resolveChapterAccess($chapter, $kiRepo, $currentUserId, forceUnlocked: true);

            echo json_encode([
                'message' => 'Bölüm kilidi açıldı',
                'balance' => $balance,
                'access' => $access,
            ]);
            break;
        case 'ki-context':
            requireLogin();
            $balance = $kiRepo->getBalance($currentUserId);
            $_SESSION['user']['ki_balance'] = $balance;

            $context = [
                'balance' => $balance,
                'currency' => $settings['ki_currency_name'] ?? 'Ki',
                'market_offers' => $kiRepo->listMarketOffers(true),
                'transactions' => $kiRepo->getTransactions($currentUserId, 20),
                'rewards' => [
                    'comment' => (int) ($settings['ki_comment_reward'] ?? 0),
                    'reaction' => (int) ($settings['ki_reaction_reward'] ?? 0),
                    'chat_per_minute' => (int) ($settings['ki_chat_reward_per_minute'] ?? 0),
                    'read_per_minute' => (int) ($settings['ki_read_reward_per_minute'] ?? 0),
                ],
            ];

            echo json_encode(['data' => $context]);
            break;
        case 'list-comments':
            $mangaId = isset($_GET['manga_id']) ? (int) $_GET['manga_id'] : 0;
            if ($mangaId <= 0) {
                throw new InvalidArgumentException('Manga seçiniz.');
            }
            $chapterId = isset($_GET['chapter_id']) ? (int) $_GET['chapter_id'] : null;
            if ($chapterId !== null && $chapterId <= 0) {
                $chapterId = null;
            }
            $comments = $interactionRepo->listComments($mangaId, $chapterId, limit: 100, currentUserId: $currentUserId);
            echo json_encode(['data' => $comments]);
            break;
        case 'post-comment':
            requireLogin();
            $comment = $interactionRepo->createComment($currentUserId, $_POST);
            $reward = (int) ($settings['ki_comment_reward'] ?? 0);
            if ($reward > 0) {
                $kiRepo->grantForComment($currentUserId, $reward, $comment['id']);
                $balance = $kiRepo->getBalance($currentUserId);
                $_SESSION['user']['ki_balance'] = $balance;
                $comment['reward'] = $reward;
                $comment['balance'] = $balance;
            }
            echo json_encode(['message' => 'Yorum eklendi', 'comment' => $comment]);
            break;
        case 'react-comment':
            requireLogin();
            $commentId = isset($_POST['comment_id']) ? (int) $_POST['comment_id'] : 0;
            $reaction = (string) ($_POST['reaction'] ?? '');
            if ($commentId <= 0) {
                throw new InvalidArgumentException('Geçersiz yorum.');
            }
            $summary = $interactionRepo->toggleReaction($commentId, $currentUserId, $reaction);
            $reward = (int) ($settings['ki_reaction_reward'] ?? 0);
            $balance = null;
            if ($summary !== null && $reward > 0) {
                $kiRepo->grantForReaction($currentUserId, $reward, $commentId);
                $balance = $kiRepo->getBalance($currentUserId);
                $_SESSION['user']['ki_balance'] = $balance;
            }

            echo json_encode([
                'message' => 'Tepki güncellendi',
                'summary' => $summary,
                'balance' => $balance,
            ]);
            break;
        case 'chat-history':
            $messages = $interactionRepo->listChatMessages(50);
            echo json_encode(['data' => $messages]);
            break;
        case 'chat-send':
            requireLogin();
            $message = $interactionRepo->createChatMessage($currentUserId, $_POST['message'] ?? '');
            $rewardRate = (int) ($settings['ki_chat_reward_per_minute'] ?? 0);
            if ($rewardRate > 0) {
                $kiRepo->grantForSession($currentUserId, $rewardRate, 'chat_reward');
                $balance = $kiRepo->getBalance($currentUserId);
                $_SESSION['user']['ki_balance'] = $balance;
                $message['balance'] = $balance;
            }
            echo json_encode(['message' => 'Mesaj gönderildi', 'data' => $message]);
            break;
        case 'award-activity':
            requireLogin();
            $type = (string) ($_POST['type'] ?? 'read');
            $minutes = isset($_POST['minutes']) ? max(0, (int) $_POST['minutes']) : 0;
            if ($minutes <= 0) {
                throw new InvalidArgumentException('Geçersiz süre.');
            }

            $rateKey = $type === 'chat' ? 'ki_chat_reward_per_minute' : 'ki_read_reward_per_minute';
            $rate = (int) ($settings[$rateKey] ?? 0);
            if ($rate <= 0) {
                echo json_encode(['message' => 'Ödül kapalı']);
                break;
            }

            $total = $rate * $minutes;
            $kiRepo->grantForSession($currentUserId, $total, $type . '_reward');
            $balance = $kiRepo->getBalance($currentUserId);
            $_SESSION['user']['ki_balance'] = $balance;

            echo json_encode(['message' => 'Ödül verildi', 'amount' => $total, 'balance' => $balance]);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Geçersiz istek']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function requireLogin(): void
{
    if (!Auth::check()) {
        http_response_code(401);
        echo json_encode(['error' => 'Giriş yapınız']);
        exit;
    }
}

function resolveChapterAccess(array $chapter, KiRepository $kiRepo, ?int $userId, bool $forceUnlocked = false): array
{
    $kiCost = (int) ($chapter['ki_cost'] ?? 0);
    $expiresAtRaw = $chapter['premium_expires_at'] ?? null;
    $expiresAt = null;
    $locked = false;

    if ($kiCost > 0) {
        if ($expiresAtRaw) {
            try {
                $expiresAt = new DateTimeImmutable($expiresAtRaw);
                if ($expiresAt > new DateTimeImmutable()) {
                    $locked = true;
                }
            } catch (Throwable) {
                $locked = true;
            }
        } else {
            $locked = true;
        }
    }

    if ($locked && $userId && !$forceUnlocked) {
        if ($kiRepo->hasUnlocked($userId, (int) $chapter['id'])) {
            $locked = false;
        }
    }

    $effectiveLocked = $locked && !$forceUnlocked;

    return [
        'locked' => $effectiveLocked,
        'required_ki' => $effectiveLocked ? $kiCost : 0,
        'premium_expires_at' => $expiresAtRaw,
    ];
}
