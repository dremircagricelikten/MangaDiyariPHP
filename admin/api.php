<?php

declare(strict_types=1);

const CHAPTER_ASSET_MAX_COUNT = 200;
const CHAPTER_ASSET_MAX_SIZE = 15728640; // 15 MB

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/MangaRepository.php';
require_once __DIR__ . '/../src/ChapterRepository.php';
require_once __DIR__ . '/../src/Slugger.php';
require_once __DIR__ . '/../src/SettingRepository.php';
require_once __DIR__ . '/../src/WidgetRepository.php';
require_once __DIR__ . '/../src/UserRepository.php';
require_once __DIR__ . '/../src/MenuRepository.php';
require_once __DIR__ . '/../src/KiRepository.php';
require_once __DIR__ . '/../src/PageRepository.php';
require_once __DIR__ . '/../src/InteractionRepository.php';

use MangaDiyari\Core\Auth;
use MangaDiyari\Core\Database;
use MangaDiyari\Core\MangaRepository;
use MangaDiyari\Core\ChapterRepository;
use MangaDiyari\Core\SettingRepository;
use MangaDiyari\Core\WidgetRepository;
use MangaDiyari\Core\UserRepository;
use MangaDiyari\Core\MenuRepository;
use MangaDiyari\Core\KiRepository;
use MangaDiyari\Core\PageRepository;
use MangaDiyari\Core\InteractionRepository;
use MangaDiyari\Core\Slugger;

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
    $menuRepo = new MenuRepository($pdo);
    $kiRepo = new KiRepository($pdo);
    $pageRepo = new PageRepository($pdo);
    $interactionRepo = new InteractionRepository($pdo);
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
                'artist' => trim($_POST['artist'] ?? ''),
                'status' => $_POST['status'] ?? 'ongoing',
                'genres' => trim($_POST['type'] ?? ($_POST['genres'] ?? '')),
                'tags' => trim($_POST['tags'] ?? ''),
            ]);

            echo json_encode(['message' => 'Seri başarıyla oluşturuldu', 'manga' => $manga]);
            break;
        case 'update-manga':
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($id <= 0) {
                throw new InvalidArgumentException('Geçersiz manga');
            }

            $payload = [
                'title' => isset($_POST['title']) ? trim((string) $_POST['title']) : null,
                'description' => isset($_POST['description']) ? trim((string) $_POST['description']) : null,
                'cover_image' => isset($_POST['cover_image']) ? trim((string) $_POST['cover_image']) : null,
                'author' => isset($_POST['author']) ? trim((string) $_POST['author']) : null,
                'artist' => isset($_POST['artist']) ? trim((string) $_POST['artist']) : null,
                'status' => isset($_POST['status']) ? (string) $_POST['status'] : null,
                'genres' => isset($_POST['type']) ? trim((string) $_POST['type']) : (isset($_POST['genres']) ? trim((string) $_POST['genres']) : null),
                'tags' => isset($_POST['tags']) ? trim((string) $_POST['tags']) : null,
            ];

            $manga = $mangaRepo->update($id, array_filter($payload, fn($value) => $value !== null));
            if (!$manga) {
                throw new InvalidArgumentException('Manga bulunamadı');
            }

            echo json_encode(['message' => 'Manga güncellendi', 'manga' => $manga]);
            break;
        case 'delete-manga':
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($id <= 0) {
                throw new InvalidArgumentException('Geçersiz manga');
            }

            $mangaRepo->delete($id);
            echo json_encode(['message' => 'Manga silindi']);
            break;
        case 'list-manga':
            $search = trim((string) ($_GET['search'] ?? ''));
            $status = trim((string) ($_GET['status'] ?? ''));
            $filters = [];
            if ($search !== '') {
                $filters['search'] = $search;
            }
            if ($status !== '') {
                $filters['status'] = $status;
            }
            $mangas = $mangaRepo->list($filters);
            echo json_encode(['data' => $mangas]);
            break;
        case 'get-manga':
            $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if ($id <= 0) {
                throw new InvalidArgumentException('Geçersiz manga');
            }
            $manga = $mangaRepo->findById($id);
            if (!$manga) {
                throw new InvalidArgumentException('Manga bulunamadı');
            }
            echo json_encode(['data' => $manga]);
            break;
        case 'create-chapter':
            $required = ['manga_id', 'number'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new InvalidArgumentException('Eksik alan: ' . $field);
                }
            }

            $mangaId = (int) $_POST['manga_id'];
            $number = trim((string) $_POST['number']);
            $content = trim((string) ($_POST['content'] ?? ''));
            $title = trim((string) ($_POST['title'] ?? ''));
            $kiCost = isset($_POST['ki_cost']) ? max(0, (int) $_POST['ki_cost']) : 0;
            $premiumDuration = isset($_POST['premium_duration_hours']) ? max(0, (int) $_POST['premium_duration_hours']) : null;

            if ($premiumDuration === null && $kiCost > 0) {
                $defaultDuration = (int) ($settingRepo->get('ki_unlock_default_duration', '0') ?? 0);
                $premiumDuration = $defaultDuration > 0 ? $defaultDuration : null;
            }

            $premiumExpiresAt = null;
            if ($kiCost > 0 && $premiumDuration) {
                $premiumExpiresAt = (new DateTimeImmutable())->add(new DateInterval('PT' . $premiumDuration . 'H'))->format('Y-m-d H:i:s');
            }

            $storageTarget = $_POST['storage_target'] ?? '';
            $storageOptions = resolveChapterStorage($settingRepo, $storageTarget);

            $preparedAssets = prepareChapterAssets();

            $chapter = $chapterRepo->create($mangaId, [
                'number' => $number,
                'title' => $title,
                'content' => $content,
                'assets' => [],
                'ki_cost' => $kiCost,
                'premium_expires_at' => $premiumExpiresAt,
            ]);

            if (!empty($preparedAssets)) {
                $assets = persistChapterAssets($chapter['id'], $number, $preparedAssets, $storageOptions);
                $chapter = $chapterRepo->update($chapter['id'], [
                    'assets' => $assets,
                ]) ?? $chapter;
            }

            echo json_encode(['message' => 'Bölüm başarıyla oluşturuldu', 'chapter' => $chapter]);
            break;
        case 'list-chapters':
            $mangaId = isset($_GET['manga_id']) ? (int) $_GET['manga_id'] : 0;
            if ($mangaId <= 0) {
                throw new InvalidArgumentException('Geçersiz manga');
            }
            $order = strtolower((string) ($_GET['order'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
            $chapters = $chapterRepo->listByManga($mangaId);
            if ($order === 'asc') {
                $chapters = array_reverse($chapters);
            }
            echo json_encode(['data' => $chapters]);
            break;
        case 'get-chapter':
            $chapterId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if ($chapterId <= 0) {
                throw new InvalidArgumentException('Geçersiz bölüm');
            }
            $chapter = $chapterRepo->findById($chapterId);
            if (!$chapter) {
                throw new InvalidArgumentException('Bölüm bulunamadı');
            }
            echo json_encode(['data' => $chapter]);
            break;
        case 'update-chapter':
            $chapterId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($chapterId <= 0) {
                throw new InvalidArgumentException('Geçersiz bölüm');
            }
            $chapter = $chapterRepo->findById($chapterId);
            if (!$chapter) {
                throw new InvalidArgumentException('Bölüm bulunamadı');
            }

            $payload = [
                'number' => isset($_POST['number']) ? trim((string) $_POST['number']) : null,
                'title' => isset($_POST['title']) ? trim((string) $_POST['title']) : null,
                'content' => isset($_POST['content']) ? trim((string) $_POST['content']) : null,
                'ki_cost' => isset($_POST['ki_cost']) ? max(0, (int) $_POST['ki_cost']) : null,
                'premium_expires_at' => isset($_POST['premium_expires_at']) && $_POST['premium_expires_at'] !== ''
                    ? (string) $_POST['premium_expires_at']
                    : null,
            ];

            $storageOptions = resolveChapterStorage($settingRepo, $_POST['storage_target'] ?? null);
            $newAssets = prepareChapterAssets();
            if (!empty($newAssets)) {
                $payload['assets'] = persistChapterAssets($chapterId, $payload['number'] ?? $chapter['number'], $newAssets, $storageOptions);
            }

            $chapter = $chapterRepo->update($chapterId, array_filter($payload, fn($value) => $value !== null)) ?? $chapter;
            echo json_encode(['message' => 'Bölüm güncellendi', 'chapter' => $chapter]);
            break;
        case 'delete-chapter':
            $chapterId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($chapterId <= 0) {
                throw new InvalidArgumentException('Geçersiz bölüm');
            }
            $chapterRepo->delete($chapterId);
            echo json_encode(['message' => 'Bölüm silindi']);
            break;
        case 'bulk-create-chapters':
            $mangaId = isset($_POST['manga_id']) ? (int) $_POST['manga_id'] : 0;
            if ($mangaId <= 0) {
                throw new InvalidArgumentException('Geçersiz manga');
            }
            if (empty($_FILES['chapter_bundle']['tmp_name']) || ($_FILES['chapter_bundle']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                throw new InvalidArgumentException('Geçerli bir ZIP dosyası yükleyin.');
            }

            $kiCost = isset($_POST['ki_cost']) ? max(0, (int) $_POST['ki_cost']) : 0;
            $duration = isset($_POST['premium_duration_hours']) ? max(0, (int) $_POST['premium_duration_hours']) : null;
            if ($duration === null && $kiCost > 0) {
                $defaultDuration = (int) ($settingRepo->get('ki_unlock_default_duration', '0') ?? 0);
                $duration = $defaultDuration > 0 ? $defaultDuration : null;
            }

            $premiumExpiresAt = null;
            if ($kiCost > 0 && $duration) {
                $premiumExpiresAt = (new DateTimeImmutable())->add(new DateInterval('PT' . $duration . 'H'))->format('Y-m-d H:i:s');
            }

            $storageOptions = resolveChapterStorage($settingRepo, $_POST['storage_target'] ?? null);
            $titleTemplate = trim((string) ($_POST['title_template'] ?? ''));

            $bundle = prepareChapterBundle($_FILES['chapter_bundle']['tmp_name']);
            if (empty($bundle)) {
                throw new InvalidArgumentException('Zip dosyasında uygun bölüm klasörü bulunamadı.');
            }

            $created = [];
            foreach ($bundle as $folder => $assets) {
                $chapterNumber = $folder;
                if ($chapterRepo->findByMangaAndNumber($mangaId, $chapterNumber)) {
                    continue;
                }

                $chapter = $chapterRepo->create($mangaId, [
                    'number' => $chapterNumber,
                    'title' => $titleTemplate !== '' ? str_replace('{{number}}', $chapterNumber, $titleTemplate) : '',
                    'content' => '',
                    'assets' => [],
                    'ki_cost' => $kiCost,
                    'premium_expires_at' => $premiumExpiresAt,
                ]);

                $paths = persistChapterAssets($chapter['id'], $chapterNumber, $assets, $storageOptions);
                $chapter = $chapterRepo->update($chapter['id'], ['assets' => $paths]) ?? $chapter;
                $created[] = $chapter;
            }

            echo json_encode(['message' => 'Toplu yükleme tamamlandı', 'created' => $created]);
            break;
        case 'get-ki-settings':
            $settings = $settingRepo->all();
            $payload = [
                'currency_name' => $settings['ki_currency_name'] ?? 'Ki',
                'comment_reward' => (int) ($settings['ki_comment_reward'] ?? 0),
                'reaction_reward' => (int) ($settings['ki_reaction_reward'] ?? 0),
                'chat_reward_per_minute' => (int) ($settings['ki_chat_reward_per_minute'] ?? 0),
                'read_reward_per_minute' => (int) ($settings['ki_read_reward_per_minute'] ?? 0),
                'market_enabled' => (int) ($settings['ki_market_enabled'] ?? 1),
                'unlock_default_duration' => (int) ($settings['ki_unlock_default_duration'] ?? 0),
            ];

            echo json_encode(['data' => $payload]);
            break;
        case 'update-ki-settings':
            $updates = [];
            $map = [
                'currency_name' => 'ki_currency_name',
                'comment_reward' => 'ki_comment_reward',
                'reaction_reward' => 'ki_reaction_reward',
                'chat_reward_per_minute' => 'ki_chat_reward_per_minute',
                'read_reward_per_minute' => 'ki_read_reward_per_minute',
                'market_enabled' => 'ki_market_enabled',
                'unlock_default_duration' => 'ki_unlock_default_duration',
            ];

            foreach ($map as $input => $key) {
                if (!isset($_POST[$input])) {
                    continue;
                }

                $value = (string) $_POST[$input];
                $settingRepo->set($key, $value);
                $updates[$key] = $value;
            }

            echo json_encode(['message' => 'Ki ayarları güncellendi', 'data' => $updates]);
            break;
        case 'list-market-offers':
            $offers = $kiRepo->listMarketOffers(false);
            echo json_encode(['data' => $offers]);
            break;
        case 'save-market-offer':
            $offer = $kiRepo->saveMarketOffer($_POST);
            echo json_encode(['message' => 'Market teklifi kaydedildi', 'offer' => $offer]);
            break;
        case 'delete-market-offer':
            $offerId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($offerId <= 0) {
                throw new InvalidArgumentException('Geçersiz teklif');
            }

            $kiRepo->deleteMarketOffer($offerId);
            echo json_encode(['message' => 'Teklif silindi']);
            break;
        case 'dashboard-stats':
            $stats = [
                'manga_total' => $mangaRepo->count(),
                'chapter_total' => $chapterRepo->count(),
                'chapter_premium' => $chapterRepo->count(['premium_only' => true]),
                'active_members' => $userRepo->count(['active' => true]),
            ];

            echo json_encode(['data' => $stats]);
            break;
        case 'get-settings':
            $settings = $settingRepo->all();
            echo json_encode(['data' => $settings]);
            break;
        case 'update-site-settings':
            $updates = [];
            $siteFields = [
                'site_name' => 'site_name',
                'site_tagline' => 'site_tagline',
                'chapter_storage_driver' => 'chapter_storage_driver',
                'site_footer' => 'site_footer',
            ];
            foreach ($siteFields as $input => $key) {
                if (!isset($_POST[$input])) {
                    continue;
                }
                $value = trim((string) $_POST[$input]);
                if ($input === 'chapter_storage_driver' && !in_array($value, ['local', 'ftp'], true)) {
                    $value = 'local';
                }
                $settingRepo->set($key, $value);
                $updates[$key] = $value;
            }

            if (!empty($_FILES['site_logo']['tmp_name']) && ($_FILES['site_logo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $logoPath = persistLogoUpload($_FILES['site_logo']);
                if ($logoPath !== null) {
                    $settingRepo->set('site_logo', $logoPath);
                    $updates['site_logo'] = $logoPath;
                }
            }

            echo json_encode(['message' => 'Site ayarları güncellendi', 'data' => $updates]);
            break;
        case 'update-storage-settings':
            $fields = [
                'ftp_host' => 'ftp_host',
                'ftp_port' => 'ftp_port',
                'ftp_username' => 'ftp_username',
                'ftp_password' => 'ftp_password',
                'ftp_passive' => 'ftp_passive',
                'ftp_root' => 'ftp_root',
                'ftp_base_url' => 'ftp_base_url',
            ];
            $updates = [];
            foreach ($fields as $input => $key) {
                if (!isset($_POST[$input])) {
                    continue;
                }
                $value = trim((string) $_POST[$input]);
                if ($input === 'ftp_passive') {
                    $value = $value === '0' ? '0' : '1';
                }
                $settingRepo->set($key, $value);
                $updates[$key] = $value;
            }

            echo json_encode(['message' => 'Depolama ayarları güncellendi', 'data' => $updates]);
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
                'area' => trim((string) ($_POST['area'] ?? 'main')) ?: 'main',
                'enabled' => isset($_POST['enabled']) ? (int) $_POST['enabled'] : 0,
                'sort_order' => isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0,
                'config' => $config,
            ]);

            if (!$updated) {
                throw new InvalidArgumentException('Widget güncellenemedi');
            }

            echo json_encode(['message' => 'Widget güncellendi', 'widget' => $updated]);
            break;
        case 'list-pages':
            $status = isset($_GET['status']) ? (string) $_GET['status'] : null;
            $search = isset($_GET['search']) ? (string) $_GET['search'] : null;
            $pages = $pageRepo->list([
                'status' => $status,
                'search' => $search,
            ]);
            echo json_encode(['data' => $pages]);
            break;
        case 'get-page':
            $pageId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if ($pageId <= 0) {
                throw new InvalidArgumentException('Geçersiz sayfa');
            }
            $page = $pageRepo->find($pageId);
            if (!$page) {
                throw new InvalidArgumentException('Sayfa bulunamadı');
            }
            echo json_encode(['data' => $page]);
            break;
        case 'create-page':
            $page = $pageRepo->create([
                'title' => $_POST['title'] ?? '',
                'slug' => $_POST['slug'] ?? '',
                'content' => $_POST['content'] ?? '',
                'status' => $_POST['status'] ?? 'draft',
            ]);
            echo json_encode(['message' => 'Sayfa oluşturuldu', 'data' => $page]);
            break;
        case 'update-page':
            $pageId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($pageId <= 0) {
                throw new InvalidArgumentException('Geçersiz sayfa');
            }
            $page = $pageRepo->update($pageId, [
                'title' => $_POST['title'] ?? null,
                'slug' => $_POST['slug'] ?? null,
                'content' => $_POST['content'] ?? null,
                'status' => $_POST['status'] ?? null,
            ]);
            if (!$page) {
                throw new InvalidArgumentException('Sayfa güncellenemedi');
            }
            echo json_encode(['message' => 'Sayfa güncellendi', 'data' => $page]);
            break;
        case 'delete-page':
            $pageId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($pageId <= 0) {
                throw new InvalidArgumentException('Geçersiz sayfa');
            }
            $pageRepo->delete($pageId);
            echo json_encode(['message' => 'Sayfa silindi']);
            break;
        case 'recent-comments':
            $comments = $interactionRepo->listRecentComments(10);
            echo json_encode(['data' => $comments]);
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
        case 'get-branding':
            $settings = $settingRepo->all();
            $data = [
                'site_name' => $settings['site_name'] ?? '',
                'site_tagline' => $settings['site_tagline'] ?? '',
                'site_logo' => $settings['site_logo'] ?? '',
            ];
            echo json_encode(['data' => $data]);
            break;
        case 'update-branding':
            $updates = [];
            if (isset($_POST['site_name'])) {
                $updates['site_name'] = trim((string) $_POST['site_name']);
            }
            if (isset($_POST['site_tagline'])) {
                $updates['site_tagline'] = trim((string) $_POST['site_tagline']);
            }

            if (!empty($_FILES['site_logo']['tmp_name']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
                $logoPath = persistLogoUpload($_FILES['site_logo']);
                if ($logoPath) {
                    $updates['site_logo'] = $logoPath;
                }
            }

            if ($updates) {
                $settingRepo->setMany($updates);
            }

            echo json_encode(['message' => 'Marka ayarları güncellendi', 'data' => $updates]);
            break;
        case 'get-ad-settings':
            $settings = $settingRepo->all();
            $data = [
                'ad_header' => $settings['ad_header'] ?? '',
                'ad_sidebar' => $settings['ad_sidebar'] ?? '',
                'ad_footer' => $settings['ad_footer'] ?? '',
            ];
            echo json_encode(['data' => $data]);
            break;
        case 'update-ad-settings':
            $keys = ['ad_header', 'ad_sidebar', 'ad_footer'];
            $updates = [];
            foreach ($keys as $key) {
                if (isset($_POST[$key])) {
                    $settingRepo->set($key, trim((string) $_POST[$key]));
                    $updates[$key] = trim((string) $_POST[$key]);
                }
            }
            echo json_encode(['message' => 'Reklam alanları güncellendi', 'data' => $updates]);
            break;
        case 'get-analytics':
            $settings = $settingRepo->all();
            $data = [
                'analytics_google' => $settings['analytics_google'] ?? '',
                'analytics_search_console' => $settings['analytics_search_console'] ?? '',
            ];
            echo json_encode(['data' => $data]);
            break;
        case 'update-analytics':
            $keys = ['analytics_google', 'analytics_search_console'];
            $updates = [];
            foreach ($keys as $key) {
                if (isset($_POST[$key])) {
                    $settingRepo->set($key, trim((string) $_POST[$key]));
                    $updates[$key] = trim((string) $_POST[$key]);
                }
            }
            echo json_encode(['message' => 'Analitik kodlar güncellendi', 'data' => $updates]);
            break;
        case 'list-menus':
            $menus = $menuRepo->getMenusWithItems();
            echo json_encode(['data' => $menus]);
            break;
        case 'create-menu':
            $menu = $menuRepo->create([
                'name' => trim((string) ($_POST['name'] ?? '')),
                'location' => trim((string) ($_POST['location'] ?? '')),
            ]);
            echo json_encode(['message' => 'Menü oluşturuldu', 'menu' => $menu]);
            break;
        case 'update-menu':
            $menuId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($menuId <= 0) {
                throw new InvalidArgumentException('Geçersiz menü');
            }
            $menu = $menuRepo->update($menuId, [
                'name' => trim((string) ($_POST['name'] ?? '')),
                'location' => trim((string) ($_POST['location'] ?? '')),
            ]);
            echo json_encode(['message' => 'Menü güncellendi', 'menu' => $menu]);
            break;
        case 'delete-menu':
            $menuId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($menuId <= 0) {
                throw new InvalidArgumentException('Geçersiz menü');
            }
            $menuRepo->delete($menuId);
            echo json_encode(['message' => 'Menü silindi']);
            break;
        case 'save-menu-items':
            $menuId = isset($_POST['menu_id']) ? (int) $_POST['menu_id'] : 0;
            if ($menuId <= 0) {
                throw new InvalidArgumentException('Geçersiz menü');
            }

            $payload = $_POST['items'] ?? '[]';
            if (is_array($payload)) {
                $items = $payload;
            } else {
                $items = json_decode((string) $payload, true);
                if (!is_array($items)) {
                    throw new InvalidArgumentException('Menü öğeleri çözümlenemedi');
                }
            }

            $menu = $menuRepo->replaceItems($menuId, $items);
            echo json_encode(['message' => 'Menü öğeleri kaydedildi', 'menu' => $menu]);
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

/**
 * @return array<int, array{tmp_name: string, extension: string, uploaded: bool}>
 */
function prepareChapterAssets(): array
{
    $assets = [];
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

    if (!empty($_FILES['chapter_files']['name']) && is_array($_FILES['chapter_files']['name'])) {
        $names = $_FILES['chapter_files']['name'];
        $tmpNames = $_FILES['chapter_files']['tmp_name'];
        $errors = $_FILES['chapter_files']['error'];
        $sizes = $_FILES['chapter_files']['size'] ?? [];

        foreach ($names as $index => $name) {
            if (($errors[$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }
            $tmp = $tmpNames[$index] ?? null;
            if (!$tmp) {
                continue;
            }
            $extension = strtolower((string) pathinfo((string) $name, PATHINFO_EXTENSION));
            if (!in_array($extension, $allowed, true)) {
                continue;
            }
            $size = isset($sizes[$index]) ? (int) $sizes[$index] : null;
            if ($size !== null && $size > CHAPTER_ASSET_MAX_SIZE) {
                throw new InvalidArgumentException('Her bir dosya için maksimum boyut 15 MB olabilir.');
            }
            if (count($assets) >= CHAPTER_ASSET_MAX_COUNT) {
                throw new InvalidArgumentException(sprintf('En fazla %d dosya yüklenebilir.', CHAPTER_ASSET_MAX_COUNT));
            }
            $assets[] = [
                'tmp_name' => $tmp,
                'extension' => $extension,
                'uploaded' => true,
            ];
        }
    }

    if (!empty($_FILES['chapter_zip']['tmp_name']) && $_FILES['chapter_zip']['error'] === UPLOAD_ERR_OK) {
        $zip = new ZipArchive();
        if ($zip->open($_FILES['chapter_zip']['tmp_name']) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if (!$entry || str_ends_with($entry, '/')) {
                    continue;
                }
                $extension = strtolower((string) pathinfo($entry, PATHINFO_EXTENSION));
                if (!in_array($extension, $allowed, true)) {
                    continue;
                }
                if (count($assets) >= CHAPTER_ASSET_MAX_COUNT) {
                    throw new InvalidArgumentException(sprintf('En fazla %d dosya yüklenebilir.', CHAPTER_ASSET_MAX_COUNT));
                }
                $contents = $zip->getFromIndex($i);
                if ($contents === false) {
                    continue;
                }
                if (strlen($contents) > CHAPTER_ASSET_MAX_SIZE) {
                    throw new InvalidArgumentException('ZIP içindeki dosyalar 15 MB sınırını aşamaz.');
                }
                $tempPath = tempnam(sys_get_temp_dir(), 'chapter_');
                if ($tempPath === false) {
                    continue;
                }
                file_put_contents($tempPath, $contents);
                $assets[] = [
                    'tmp_name' => $tempPath,
                    'extension' => $extension,
                    'uploaded' => false,
                ];
            }
            $zip->close();
        }
    }

    return $assets;
}

/**
 * @param array<int, array{tmp_name: string, extension: string, uploaded: bool}> $assets
 * @return array<int, string>
 */
function persistChapterAssets(int $chapterId, string $chapterNumber, array $assets, array $storage): array
{
    $driver = $storage['driver'] ?? 'local';

    if ($driver === 'ftp') {
        return persistChapterAssetsFtp($chapterId, $chapterNumber, $assets, $storage['ftp'] ?? []);
    }

    return persistChapterAssetsLocal($chapterId, $chapterNumber, $assets, $storage['local_root'] ?? (__DIR__ . '/../public/uploads/chapters'));
}

function persistChapterAssetsLocal(int $chapterId, string $chapterNumber, array $assets, string $baseDir): array
{
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0775, true);
    }

    $chapterDir = rtrim($baseDir, '/') . '/' . $chapterId;
    if (!is_dir($chapterDir)) {
        mkdir($chapterDir, 0775, true);
    }

    $saved = [];
    $index = 1;
    foreach ($assets as $asset) {
        $extension = $asset['extension'];
        $filename = sprintf('%03d-%s.%s', $index++, Slugger::slugify($chapterNumber) ?: 'bolum', $extension);
        $target = $chapterDir . '/' . $filename;

        $moved = false;
        if (!empty($asset['uploaded'])) {
            $moved = move_uploaded_file($asset['tmp_name'], $target);
        } else {
            $moved = rename($asset['tmp_name'], $target);
        }

        if (!$moved) {
            continue;
        }

        $saved[] = 'uploads/chapters/' . $chapterId . '/' . $filename;
    }

    return $saved;
}

function persistChapterAssetsFtp(int $chapterId, string $chapterNumber, array $assets, array $config): array
{
    $host = trim($config['host'] ?? '');
    if ($host === '') {
        throw new InvalidArgumentException('FTP yapılandırması eksik.');
    }

    $port = (int) ($config['port'] ?? 21);
    $connection = @ftp_connect($host, $port, 30);
    if (!$connection) {
        throw new RuntimeException('FTP sunucusuna bağlanılamadı.');
    }

    $username = $config['username'] ?? '';
    $password = $config['password'] ?? '';
    if (!@ftp_login($connection, $username, $password)) {
        ftp_close($connection);
        throw new RuntimeException('FTP kimlik doğrulaması başarısız.');
    }

    if (array_key_exists('passive', $config)) {
        @ftp_pasv($connection, (bool) $config['passive']);
    }

    $remoteRoot = rtrim((string) ($config['root'] ?? ''), '/');
    $chapterFolder = ($remoteRoot !== '' ? $remoteRoot . '/' : '') . $chapterId;
    ftpEnsureDirectory($connection, $chapterFolder);

    $saved = [];
    $index = 1;
    foreach ($assets as $asset) {
        $extension = $asset['extension'];
        $filename = sprintf('%03d-%s.%s', $index++, Slugger::slugify($chapterNumber) ?: 'bolum', $extension);
        $remotePath = $chapterFolder . '/' . $filename;

        $success = @ftp_put($connection, $remotePath, $asset['tmp_name'], FTP_BINARY);
        if (!$success) {
            continue;
        }

        if (empty($asset['uploaded']) && is_file($asset['tmp_name'])) {
            @unlink($asset['tmp_name']);
        }

        $baseUrl = trim((string) ($config['base_url'] ?? ''));
        if ($baseUrl !== '') {
            $saved[] = trim(rtrim($baseUrl, '/') . '/' . $chapterId . '/' . $filename, '/');
        } else {
            $saved[] = 'uploads/chapters/' . $chapterId . '/' . $filename;
        }
    }

    ftp_close($connection);

    return $saved;
}

function ftpEnsureDirectory($connection, string $path): void
{
    $normalized = preg_replace('#/+#', '/', $path);
    $segments = array_filter(explode('/', $normalized), 'strlen');
    $current = '';
    foreach ($segments as $segment) {
        $current .= '/' . $segment;
        @ftp_mkdir($connection, $current);
    }
}

function resolveChapterStorage(SettingRepository $settings, ?string $overrideDriver): array
{
    $driver = strtolower((string) ($overrideDriver ?: ($settings->get('chapter_storage_driver', 'local') ?? 'local')));
    if (!in_array($driver, ['local', 'ftp'], true)) {
        $driver = 'local';
    }

    $ftpConfig = [
        'host' => (string) ($settings->get('ftp_host') ?? ''),
        'port' => (int) ($settings->get('ftp_port') ?? 21),
        'username' => (string) ($settings->get('ftp_username') ?? ''),
        'password' => (string) ($settings->get('ftp_password') ?? ''),
        'passive' => (int) ($settings->get('ftp_passive') ?? 1) === 1,
        'root' => (string) ($settings->get('ftp_root') ?? '/public_html/chapters'),
        'base_url' => (string) ($settings->get('ftp_base_url') ?? ''),
    ];

    return [
        'driver' => $driver,
        'local_root' => __DIR__ . '/../public/uploads/chapters',
        'ftp' => $ftpConfig,
    ];
}

function prepareChapterBundle(string $zipPath): array
{
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        throw new InvalidArgumentException('ZIP dosyası açılamadı.');
    }

    $chapters = [];
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif'];

    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entry = $zip->getNameIndex($i);
        if (!$entry || str_ends_with($entry, '/')) {
            continue;
        }

        $parts = explode('/', $entry, 2);
        $folder = count($parts) > 1 ? trim($parts[0]) : '';
        if ($folder === '') {
            $folder = '__single__';
        }

        $extension = strtolower((string) pathinfo($entry, PATHINFO_EXTENSION));
        if (!in_array($extension, $allowed, true)) {
            continue;
        }

        if (!isset($chapters[$folder])) {
            $chapters[$folder] = [];
        }

        if (count($chapters[$folder]) >= CHAPTER_ASSET_MAX_COUNT) {
            throw new InvalidArgumentException(sprintf('Her bölüm için en fazla %d dosya yüklenebilir.', CHAPTER_ASSET_MAX_COUNT));
        }

        $contents = $zip->getFromIndex($i);
        if ($contents === false) {
            continue;
        }
        if (strlen($contents) > CHAPTER_ASSET_MAX_SIZE) {
            throw new InvalidArgumentException('ZIP içindeki dosyalar 15 MB sınırını aşamaz.');
        }

        $tempPath = tempnam(sys_get_temp_dir(), 'bundle_');
        if ($tempPath === false) {
            continue;
        }
        file_put_contents($tempPath, $contents);
        $chapters[$folder][] = [
            'tmp_name' => $tempPath,
            'extension' => $extension,
            'uploaded' => false,
        ];
    }

    $zip->close();

    if (count($chapters) === 1 && isset($chapters['__single__'])) {
        throw new InvalidArgumentException('Toplu yükleme için zip dosyası klasörlere ayrılmalıdır.');
    }

    unset($chapters['__single__']);

    return $chapters;
}


function persistLogoUpload(array $file): ?string
{
    $allowed = ['jpg', 'jpeg', 'png', 'svg', 'webp'];
    $extension = strtolower((string) pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed, true)) {
        throw new InvalidArgumentException('Desteklenmeyen logo formatı.');
    }

    $brandingDir = __DIR__ . '/../public/uploads/branding';
    if (!is_dir($brandingDir)) {
        mkdir($brandingDir, 0775, true);
    }

    $filename = 'logo-' . date('YmdHis') . '.' . $extension;
    $target = $brandingDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return null;
    }

    return 'uploads/branding/' . $filename;
}
