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
require_once __DIR__ . '/../src/Mailer.php';
require_once __DIR__ . '/../src/PostRepository.php';
require_once __DIR__ . '/../src/TaxonomyRepository.php';
require_once __DIR__ . '/../src/MediaRepository.php';
require_once __DIR__ . '/../src/RoleRepository.php';

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
use MangaDiyari\Core\Mailer;
use MangaDiyari\Core\PostRepository;
use MangaDiyari\Core\TaxonomyRepository;
use MangaDiyari\Core\MediaRepository;
use MangaDiyari\Core\RoleRepository;

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
    $postRepo = new PostRepository($pdo);
    $taxonomyRepo = new TaxonomyRepository($pdo);
    $mediaRepo = new MediaRepository($pdo);
    $roleRepo = new RoleRepository($pdo);
    $userRepo->setRoleRepository($roleRepo);
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

            $coverImage = trim((string) ($_POST['cover_image'] ?? ''));
            if (!empty($_FILES['cover_upload']['tmp_name']) && ($_FILES['cover_upload']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $uploadedCover = persistCoverUpload($_FILES['cover_upload']);
                if ($uploadedCover !== null) {
                    $coverImage = $uploadedCover;
                }
            }

            $manga = $mangaRepo->create([
                'title' => trim($_POST['title']),
                'description' => trim($_POST['description'] ?? ''),
                'cover_image' => $coverImage,
                'author' => trim($_POST['author'] ?? ''),
                'artist' => trim($_POST['artist'] ?? ''),
                'status' => $_POST['status'] ?? 'ongoing',
                'genres' => trim($_POST['type'] ?? ($_POST['genres'] ?? '')),
                'tags' => trim($_POST['tags'] ?? ''),
                'slug' => trim((string) ($_POST['slug'] ?? '')),
            ]);

            echo json_encode(['message' => 'Seri başarıyla oluşturuldu', 'manga' => $manga]);
            break;
        case 'update-manga':
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($id <= 0) {
                throw new InvalidArgumentException('Geçersiz manga');
            }

            $coverImage = null;
            if (!empty($_FILES['cover_upload']['tmp_name']) && ($_FILES['cover_upload']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
                $uploadedCover = persistCoverUpload($_FILES['cover_upload']);
                if ($uploadedCover !== null) {
                    $coverImage = $uploadedCover;
                }
            } elseif (array_key_exists('cover_image', $_POST)) {
                $coverImage = trim((string) $_POST['cover_image']);
            }

            $slugValue = null;
            if (array_key_exists('slug', $_POST)) {
                $slugValue = trim((string) $_POST['slug']);
            }

            $payload = [
                'title' => isset($_POST['title']) ? trim((string) $_POST['title']) : null,
                'description' => isset($_POST['description']) ? trim((string) $_POST['description']) : null,
                'cover_image' => $coverImage,
                'author' => isset($_POST['author']) ? trim((string) $_POST['author']) : null,
                'artist' => isset($_POST['artist']) ? trim((string) $_POST['artist']) : null,
                'status' => isset($_POST['status']) ? (string) $_POST['status'] : null,
                'genres' => isset($_POST['type']) ? trim((string) $_POST['type']) : (isset($_POST['genres']) ? trim((string) $_POST['genres']) : null),
                'tags' => isset($_POST['tags']) ? trim((string) $_POST['tags']) : null,
                'slug' => $slugValue !== null ? $slugValue : null,
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

            $mailSummary = null;
            $manga = $mangaRepo->findById($mangaId);
            if ($manga) {
                try {
                    $mailSummary = notifyChapterPublished($settingRepo, $userRepo, $chapter, $manga);
                } catch (Throwable $mailError) {
                    $mailSummary = ['error' => $mailError->getMessage()];
                }
            }

            $response = ['message' => 'Bölüm başarıyla oluşturuldu', 'chapter' => $chapter];
            if ($mailSummary) {
                $response['mail'] = $mailSummary;
            }

            echo json_encode($response);
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
            $mailSummary = ['total_sent' => 0, 'chapters' => []];
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

                $manga = $mangaRepo->findById($mangaId);
                if ($manga) {
                    try {
                        $result = notifyChapterPublished($settingRepo, $userRepo, $chapter, $manga);
                        if ($result) {
                            $mailSummary['total_sent'] += (int) ($result['sent'] ?? 0);
                            $mailSummary['chapters'][] = ['number' => $chapterNumber, 'result' => $result];
                        }
                    } catch (Throwable $mailError) {
                        $mailSummary['chapters'][] = ['number' => $chapterNumber, 'error' => $mailError->getMessage()];
                    }
                }
            }

            $response = ['message' => 'Toplu yükleme tamamlandı', 'created' => $created];
            if (!empty($mailSummary['chapters'])) {
                $response['mail'] = $mailSummary;
            }

            echo json_encode($response);
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
        case 'list-payment-methods':
            $methods = $kiRepo->listPaymentMethods(false);
            echo json_encode(['data' => $methods]);
            break;
        case 'save-payment-method':
            $method = $kiRepo->savePaymentMethod($_POST);
            echo json_encode(['message' => 'Ödeme yöntemi kaydedildi', 'method' => $method]);
            break;
        case 'delete-payment-method':
            $methodId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($methodId <= 0) {
                throw new InvalidArgumentException('Geçersiz ödeme yöntemi');
            }

            $kiRepo->deletePaymentMethod($methodId);
            echo json_encode(['message' => 'Ödeme yöntemi silindi']);
            break;
        case 'list-market-orders':
            $statusFilter = trim((string) ($_GET['status'] ?? ''));
            $filters = [];
            if ($statusFilter !== '') {
                $filters['status'] = $statusFilter;
            }
            $orders = $kiRepo->listMarketOrders($filters);
            echo json_encode(['data' => $orders]);
            break;
        case 'save-market-order':
            $order = $kiRepo->createMarketOrder($_POST);
            echo json_encode(['message' => 'Sipariş oluşturuldu', 'order' => $order]);
            break;
        case 'update-market-order':
            $orderId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($orderId <= 0) {
                throw new InvalidArgumentException('Geçersiz sipariş');
            }
            $order = $kiRepo->updateMarketOrder($orderId, $_POST);
            echo json_encode(['message' => 'Sipariş güncellendi', 'order' => $order]);
            break;
        case 'get-market-order':
            $orderId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if ($orderId <= 0) {
                throw new InvalidArgumentException('Geçersiz sipariş');
            }
            $order = $kiRepo->findMarketOrder($orderId);
            echo json_encode(['data' => $order]);
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
                'site_base_url' => 'site_base_url',
            ];
            foreach ($siteFields as $input => $key) {
                if (!isset($_POST[$input])) {
                    continue;
                }
                $value = trim((string) $_POST[$input]);
                if ($input === 'chapter_storage_driver' && !in_array($value, ['local', 'ftp'], true)) {
                    $value = 'local';
                }
                if ($input === 'site_base_url') {
                    $value = $value !== '' ? rtrim($value, '/') : '';
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
            $clearPassword = isset($_POST['ftp_password_clear']) && (string) $_POST['ftp_password_clear'] === '1';
            foreach ($fields as $input => $key) {
                if (!array_key_exists($input, $_POST)) {
                    continue;
                }
                $value = trim((string) $_POST[$input]);
                if ($input === 'ftp_passive') {
                    $value = $value === '0' ? '0' : '1';
                } elseif ($input === 'ftp_port') {
                    $port = (int) $value;
                    $value = (string) ($port > 0 ? $port : 21);
                } elseif ($input === 'ftp_base_url') {
                    $value = $value !== '' ? rtrim($value, '/') : '';
                } elseif ($input === 'ftp_password') {
                    if ($clearPassword) {
                        $value = '';
                    } elseif ($value === '') {
                        continue;
                    }
                }
                $settingRepo->set($key, $value);
                $updates[$key] = $value;
            }

            if ($clearPassword && !isset($updates['ftp_password'])) {
                $settingRepo->set('ftp_password', '');
                $updates['ftp_password'] = '';
            }

            echo json_encode(['message' => 'Depolama ayarları güncellendi', 'data' => $updates]);
            break;
        case 'test-ftp-connection':
            $host = trim((string) ($_POST['ftp_host'] ?? ''));
            if ($host === '') {
                throw new InvalidArgumentException('FTP sunucusu belirtilmelidir.');
            }

            $port = isset($_POST['ftp_port']) ? (int) $_POST['ftp_port'] : 21;
            if ($port <= 0) {
                $port = 21;
            }

            $username = (string) ($_POST['ftp_username'] ?? '');
            $password = array_key_exists('ftp_password', $_POST) ? (string) $_POST['ftp_password'] : '';
            $passive = !isset($_POST['ftp_passive']) || $_POST['ftp_passive'] !== '0';
            $root = trim((string) ($_POST['ftp_root'] ?? ''));

            $connection = @ftp_connect($host, $port, 10);
            if (!$connection) {
                throw new RuntimeException('FTP sunucusuna bağlanılamadı.');
            }

            if (!@ftp_login($connection, $username, $password)) {
                ftp_close($connection);
                throw new RuntimeException('FTP kimlik bilgileri kabul edilmedi.');
            }

            @ftp_pasv($connection, $passive);

            $summary = [];
            if ($root !== '') {
                $currentDir = @ftp_pwd($connection) ?: '.';
                if (!@ftp_chdir($connection, $root)) {
                    ftp_close($connection);
                    throw new RuntimeException('Belirtilen uzaktan klasöre erişilemiyor.');
                }
                $summary[] = 'Klasör erişimi doğrulandı';
                @ftp_chdir($connection, $currentDir);
            }

            $summary[] = $passive ? 'Pasif mod açık' : 'Pasif mod kapalı';

            ftp_close($connection);

            echo json_encode([
                'message' => 'FTP bağlantısı doğrulandı',
                'data' => [
                    'summary' => implode(' · ', $summary),
                ],
            ]);
            break;
        case 'update-smtp-settings':
            $enabled = isset($_POST['smtp_enabled']) && $_POST['smtp_enabled'] === '1' ? '1' : '0';
            $settingRepo->set('smtp_enabled', $enabled);

            $host = trim((string) ($_POST['smtp_host'] ?? ''));
            $settingRepo->set('smtp_host', $host);

            $port = isset($_POST['smtp_port']) ? (int) $_POST['smtp_port'] : 587;
            if ($port <= 0) {
                $port = 587;
            }
            $settingRepo->set('smtp_port', (string) $port);

            $encryption = strtolower(trim((string) ($_POST['smtp_encryption'] ?? '')));
            if (!in_array($encryption, ['ssl', 'tls'], true)) {
                $encryption = '';
            }
            $settingRepo->set('smtp_encryption', $encryption);

            $username = trim((string) ($_POST['smtp_username'] ?? ''));
            $settingRepo->set('smtp_username', $username);

            $password = $_POST['smtp_password'] ?? null;
            $passwordCleared = isset($_POST['smtp_password_clear']) && $_POST['smtp_password_clear'] === '1';
            $passwordUpdated = false;
            if ($password !== null && $password !== '') {
                $settingRepo->set('smtp_password', (string) $password);
                $passwordUpdated = true;
            } elseif ($passwordCleared) {
                $settingRepo->set('smtp_password', '');
            }

            $fromEmail = trim((string) ($_POST['smtp_from_email'] ?? ''));
            $settingRepo->set('smtp_from_email', $fromEmail);

            $fromName = trim((string) ($_POST['smtp_from_name'] ?? ''));
            $settingRepo->set('smtp_from_name', $fromName);

            $replyTo = trim((string) ($_POST['smtp_reply_to'] ?? ''));
            $settingRepo->set('smtp_reply_to', $replyTo);

            $updates = [
                'smtp_enabled' => $enabled,
                'smtp_host' => $host,
                'smtp_port' => (string) $port,
                'smtp_encryption' => $encryption,
                'smtp_username' => $username,
                'smtp_from_email' => $fromEmail,
                'smtp_from_name' => $fromName,
                'smtp_reply_to' => $replyTo,
                'smtp_password_updated' => $passwordUpdated,
                'smtp_password_cleared' => $passwordCleared && !$passwordUpdated,
            ];

            echo json_encode(['message' => 'SMTP ayarları güncellendi', 'data' => $updates]);
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
        case 'list-posts':
            $status = isset($_GET['status']) ? trim((string) $_GET['status']) : '';
            if ($status === 'all') {
                $status = '';
            }
            $search = trim((string) ($_GET['search'] ?? ''));
            $posts = $postRepo->list([
                'status' => $status !== '' ? $status : null,
                'search' => $search !== '' ? $search : null,
            ]);
            echo json_encode(['data' => $posts]);
            break;
        case 'get-post':
            $postId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if ($postId <= 0) {
                throw new InvalidArgumentException('Geçersiz yazı');
            }
            $post = $postRepo->find($postId);
            if (!$post) {
                throw new InvalidArgumentException('Yazı bulunamadı');
            }
            echo json_encode(['data' => $post]);
            break;
        case 'create-post':
            $title = trim((string) ($_POST['title'] ?? ''));
            if ($title === '') {
                throw new InvalidArgumentException('Yazı başlığı zorunludur.');
            }
            $post = $postRepo->create([
                'title' => $title,
                'slug' => $_POST['slug'] ?? '',
                'excerpt' => $_POST['excerpt'] ?? '',
                'content' => $_POST['content'] ?? '',
                'status' => $_POST['status'] ?? 'draft',
                'featured_image' => $_POST['featured_image'] ?? '',
                'author_id' => isset($_POST['author_id']) ? (int) $_POST['author_id'] : null,
                'categories' => parseTermInput($_POST['categories'] ?? []),
                'tags' => parseTermInput($_POST['tags'] ?? []),
            ]);
            echo json_encode(['message' => 'Yazı oluşturuldu', 'data' => $post]);
            break;
        case 'update-post':
            $postId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($postId <= 0) {
                throw new InvalidArgumentException('Geçersiz yazı');
            }

            $payload = [];
            foreach (['title', 'slug', 'excerpt', 'content', 'status', 'featured_image'] as $field) {
                if (array_key_exists($field, $_POST)) {
                    $payload[$field] = $_POST[$field];
                }
            }
            if (array_key_exists('author_id', $_POST)) {
                $payload['author_id'] = $_POST['author_id'] !== '' ? (int) $_POST['author_id'] : null;
            }
            if (array_key_exists('categories', $_POST)) {
                $payload['categories'] = parseTermInput($_POST['categories']);
            }
            if (array_key_exists('tags', $_POST)) {
                $payload['tags'] = parseTermInput($_POST['tags']);
            }

            $updated = $postRepo->update($postId, $payload);
            if (!$updated) {
                throw new InvalidArgumentException('Yazı güncellenemedi');
            }
            echo json_encode(['message' => 'Yazı güncellendi', 'data' => $updated]);
            break;
        case 'delete-post':
            $postId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($postId <= 0) {
                throw new InvalidArgumentException('Geçersiz yazı');
            }
            $postRepo->delete($postId);
            echo json_encode(['message' => 'Yazı silindi']);
            break;
        case 'list-taxonomies':
            $taxonomy = isset($_GET['taxonomy']) ? strtolower(trim((string) $_GET['taxonomy'])) : '';
            if (!in_array($taxonomy, ['category', 'tag'], true)) {
                throw new InvalidArgumentException('Geçersiz taksonomi');
            }
            $terms = $taxonomyRepo->list($taxonomy);
            echo json_encode(['data' => $terms]);
            break;
        case 'save-taxonomy':
            $taxonomy = isset($_POST['taxonomy']) ? strtolower(trim((string) $_POST['taxonomy'])) : '';
            if (!in_array($taxonomy, ['category', 'tag'], true)) {
                throw new InvalidArgumentException('Geçersiz taksonomi');
            }
            $payload = [
                'name' => $_POST['name'] ?? '',
                'slug' => $_POST['slug'] ?? '',
                'description' => $_POST['description'] ?? '',
            ];
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            $term = $id > 0
                ? $taxonomyRepo->update($taxonomy, $id, $payload)
                : $taxonomyRepo->create($taxonomy, $payload);
            if (!$term) {
                throw new InvalidArgumentException('Taksonomi kaydedilemedi');
            }
            echo json_encode(['message' => 'Terim kaydedildi', 'data' => $term]);
            break;
        case 'delete-taxonomy':
            $taxonomy = isset($_POST['taxonomy']) ? strtolower(trim((string) $_POST['taxonomy'])) : '';
            if (!in_array($taxonomy, ['category', 'tag'], true)) {
                throw new InvalidArgumentException('Geçersiz taksonomi');
            }
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($id <= 0) {
                throw new InvalidArgumentException('Geçersiz terim');
            }
            $taxonomyRepo->delete($taxonomy, $id);
            echo json_encode(['message' => 'Terim silindi']);
            break;
        case 'list-media':
            $search = trim((string) ($_GET['search'] ?? ''));
            $media = $mediaRepo->list([
                'search' => $search !== '' ? $search : null,
            ]);
            $media = array_map(static function (array $item): array {
                $item['full_url'] = '../public/' . ltrim($item['url'], '/');
                return $item;
            }, $media);
            echo json_encode(['data' => $media]);
            break;
        case 'upload-media':
            if (empty($_FILES['media_file']) || !is_uploaded_file($_FILES['media_file']['tmp_name'])) {
                throw new InvalidArgumentException('Medya dosyası seçilmedi.');
            }
            $saved = persistMediaUpload($_FILES['media_file']);
            $record = $mediaRepo->create([
                'filename' => $saved['filename'],
                'path' => $saved['path'],
                'mime_type' => $saved['mime_type'],
                'size_bytes' => $saved['size_bytes'],
                'title' => $_POST['title'] ?? $saved['filename'],
                'alt_text' => $_POST['alt_text'] ?? '',
                'created_by' => Auth::user()['id'] ?? null,
            ]);
            $record['full_url'] = '../public/' . ltrim($record['url'], '/');
            echo json_encode(['message' => 'Medya yüklendi', 'data' => $record]);
            break;
        case 'delete-media':
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($id <= 0) {
                throw new InvalidArgumentException('Geçersiz medya');
            }
            $media = $mediaRepo->find($id);
            if ($media) {
                $filePath = __DIR__ . '/../public/' . ltrim($media['url'], '/');
                if (is_file($filePath)) {
                    @unlink($filePath);
                }
            }
            $mediaRepo->delete($id);
            echo json_encode(['message' => 'Medya silindi']);
            break;
        case 'list-roles':
            echo json_encode(['data' => $roleRepo->list()]);
            break;
        case 'create-role':
            $capabilities = parseTermInput($_POST['capabilities'] ?? []);
            $role = $roleRepo->create([
                'role_key' => $_POST['role_key'] ?? '',
                'label' => $_POST['label'] ?? '',
                'capabilities' => $capabilities,
                'sort_order' => isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : 0,
            ]);
            echo json_encode(['message' => 'Rol oluşturuldu', 'data' => $role]);
            break;
        case 'update-role':
            $key = $_POST['role_key'] ?? '';
            if ($key === '') {
                throw new InvalidArgumentException('Geçersiz rol');
            }
            $payload = [
                'role_key' => $_POST['new_role_key'] ?? $key,
                'label' => $_POST['label'] ?? null,
                'sort_order' => isset($_POST['sort_order']) ? (int) $_POST['sort_order'] : null,
            ];
            if (array_key_exists('capabilities', $_POST)) {
                $payload['capabilities'] = parseTermInput($_POST['capabilities']);
            }
            $role = $roleRepo->update($key, array_filter($payload, static fn($value) => $value !== null));
            if (!$role) {
                throw new InvalidArgumentException('Rol güncellenemedi');
            }
            echo json_encode(['message' => 'Rol güncellendi', 'data' => $role]);
            break;
        case 'delete-role':
            $key = $_POST['role_key'] ?? '';
            if ($key === '') {
                throw new InvalidArgumentException('Geçersiz rol');
            }
            $roleRepo->delete($key);
            echo json_encode(['message' => 'Rol silindi']);
            break;
        case 'list-comments-admin':
            $status = isset($_GET['status']) ? (string) $_GET['status'] : 'active';
            $search = isset($_GET['search']) ? (string) $_GET['search'] : '';
            $comments = $interactionRepo->listForAdmin([
                'status' => $status,
                'search' => $search,
            ]);
            echo json_encode(['data' => $comments]);
            break;
        case 'trash-comment':
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($id <= 0) {
                throw new InvalidArgumentException('Geçersiz yorum');
            }
            $interactionRepo->setDeleted($id, true);
            echo json_encode(['message' => 'Yorum çöp kutusuna taşındı']);
            break;
        case 'restore-comment':
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($id <= 0) {
                throw new InvalidArgumentException('Geçersiz yorum');
            }
            $interactionRepo->setDeleted($id, false);
            echo json_encode(['message' => 'Yorum geri yüklendi']);
            break;
        case 'purge-comments':
            $removed = $interactionRepo->purgeDeleted();
            echo json_encode(['message' => 'Çöp kutusu temizlendi', 'removed' => $removed]);
            break;
        case 'recent-comments':
            $comments = $interactionRepo->listRecentComments(10);
            echo json_encode(['data' => $comments]);
            break;
        case 'list-users':
            $roles = [];
            foreach ($roleRepo->list() as $role) {
                $roles[$role['role_key']] = $role;
            }
            $users = array_map(static function (array $user) use ($roles): array {
                unset($user['password_hash']);
                $roleKey = $user['role'] ?? 'member';
                $user['role_label'] = $roles[$roleKey]['label'] ?? ucfirst($roleKey);
                $user['role_capabilities'] = $roles[$roleKey]['capabilities'] ?? [];
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
        case 'get-user':
            $userId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            if ($userId <= 0) {
                throw new InvalidArgumentException('Geçersiz üye');
            }

            $user = $userRepo->findById($userId);
            if (!$user) {
                throw new InvalidArgumentException('Kullanıcı bulunamadı.');
            }
            unset($user['password_hash']);
            echo json_encode(['data' => $user]);
            break;
        case 'update-user-profile':
            $userId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            if ($userId <= 0) {
                throw new InvalidArgumentException('Geçersiz üye');
            }

            $payload = [
                'username' => $_POST['username'] ?? null,
                'email' => $_POST['email'] ?? null,
                'bio' => $_POST['bio'] ?? null,
                'avatar_url' => $_POST['avatar_url'] ?? null,
                'website_url' => $_POST['website_url'] ?? null,
                'ki_balance' => $_POST['ki_balance'] ?? null,
            ];

            $updated = $userRepo->updateAdminProfile($userId, $payload);
            echo json_encode(['message' => 'Üye profili güncellendi', 'user' => $updated]);
            break;
        case 'get-ftp-settings':
            $ftpDefaults = [
                'ftp_host' => '',
                'ftp_port' => '21',
                'ftp_username' => '',
                'ftp_password' => '',
                'ftp_root' => '/',
                'ftp_passive' => '1',
                'ftp_base_url' => '',
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

function notifyChapterPublished(SettingRepository $settings, UserRepository $users, array $chapter, array $manga): ?array
{
    if (($settings->get('smtp_enabled') ?? '0') !== '1') {
        return ['sent' => 0, 'skipped' => 'smtp-disabled'];
    }

    $recipients = $users->listActiveUsers();
    if (!$recipients) {
        return ['sent' => 0, 'skipped' => 'no-recipients'];
    }

    $mailer = Mailer::fromSettings($settings);
    $siteName = $settings->get('site_name') ?? 'Manga Diyarı';
    $baseUrl = buildSiteBaseUrl($settings);

    $chapterTitle = trim((string) ($chapter['title'] ?? ''));
    $chapterLabel = 'Bölüm ' . $chapter['number'];
    if ($chapterTitle !== '') {
        $chapterLabel .= ' - ' . $chapterTitle;
    }

    $chapterUrl = $baseUrl . '/chapter.php?slug=' . rawurlencode($manga['slug'])
        . '&chapter=' . rawurlencode((string) $chapter['number']);
    $mangaUrl = $baseUrl . '/manga.php?slug=' . rawurlencode($manga['slug']);
    $subject = sprintf('%s · %s', $manga['title'], $chapterLabel);

    $sent = 0;
    $errors = [];

    foreach ($recipients as $recipient) {
        $username = $recipient['username'];
        $email = $recipient['email'];

        $htmlBody = sprintf(
            '<p>Merhaba %s,</p>'
            . '<p><strong>%s</strong> serisine yeni bir bölüm eklendi.</p>'
            . '<p><a href="%s">%s</a> hemen okuyabilirsiniz.</p>'
            . '<p>Seri sayfası: <a href="%s">%s</a></p>'
            . '<p>İyi okumalar,<br>%s</p>',
            htmlspecialchars($username, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            htmlspecialchars($manga['title'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            htmlspecialchars($chapterUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            htmlspecialchars($chapterLabel, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            htmlspecialchars($mangaUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            htmlspecialchars($mangaUrl, ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            htmlspecialchars($siteName, ENT_QUOTES | ENT_HTML5, 'UTF-8')
        );

        $textBody = "Merhaba {$username},\n\n"
            . "{$manga['title']} serisine yeni bir bölüm eklendi: {$chapterLabel}.\n"
            . "Bölümü oku: {$chapterUrl}\n"
            . "Seri sayfası: {$mangaUrl}\n\n"
            . "İyi okumalar,\n{$siteName}";

        try {
            $mailer->send([$email => $username], $subject, $htmlBody, ['text' => $textBody]);
            $sent++;
        } catch (Throwable $exception) {
            $errors[] = $exception->getMessage();
        }
    }

    $result = ['sent' => $sent];
    if ($errors) {
        $result['errors'] = array_values(array_unique($errors));
    }

    return $result;
}

function buildSiteBaseUrl(SettingRepository $settings): string
{
    $stored = trim((string) ($settings->get('site_base_url') ?? ''));
    if ($stored !== '') {
        return rtrim($stored, '/');
    }

    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    $basePath = $scriptDir ? preg_replace('#/admin$#', '', $scriptDir) : '';
    if ($basePath === '/' || $basePath === '.' || $basePath === null) {
        $basePath = '';
    }

    return rtrim($scheme . '://' . $host . $basePath, '/');
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

function persistCoverUpload(array $file): ?string
{
    $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $extension = strtolower((string) pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $allowed, true)) {
        throw new InvalidArgumentException('Desteklenmeyen kapak görseli formatı.');
    }

    $coversDir = __DIR__ . '/../public/uploads/covers';
    if (!is_dir($coversDir)) {
        mkdir($coversDir, 0775, true);
    }

    $filename = 'cover-' . date('YmdHis') . '-' . substr(bin2hex(random_bytes(6)), 0, 12) . '.' . $extension;
    $target = $coversDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return null;
    }

    return 'uploads/covers/' . $filename;
}

/**
 * @param mixed $value
 * @return array<int, string>
 */
function parseTermInput(mixed $value): array
{
    if (is_array($value)) {
        $items = $value;
    } else {
        $value = trim((string) $value);
        if ($value === '') {
            return [];
        }
        if (str_starts_with($value, '[')) {
            try {
                $decoded = json_decode($value, true, flags: JSON_THROW_ON_ERROR);
                $items = is_array($decoded) ? $decoded : [];
            } catch (Throwable) {
                $items = [];
            }
        } else {
            $items = array_map('trim', explode(',', $value));
        }
    }

    $normalized = [];
    foreach ($items as $item) {
        $term = strtolower(trim((string) $item));
        if ($term !== '') {
            $normalized[] = $term;
        }
    }

    return array_values(array_unique($normalized));
}

/**
 * @return array{filename:string,path:string,mime_type:string,size_bytes:int}
 */
function persistMediaUpload(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Dosya yüklenirken hata oluştu.');
    }

    $extension = strtolower((string) pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'pdf', 'mp4', 'zip'];
    if (!in_array($extension, $allowed, true)) {
        throw new InvalidArgumentException('Desteklenmeyen dosya uzantısı.');
    }

    $mediaRoot = __DIR__ . '/../public/uploads/media/' . date('Y') . '/' . date('m');
    if (!is_dir($mediaRoot)) {
        mkdir($mediaRoot, 0775, true);
    }

    $baseName = Slugger::slugify((string) pathinfo((string) $file['name'], PATHINFO_FILENAME));
    if ($baseName === '') {
        $baseName = 'dosya';
    }
    $filename = $baseName . '-' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $extension;
    $target = $mediaRoot . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        throw new InvalidArgumentException('Dosya kaydedilemedi.');
    }

    $mimeType = mime_content_type($target) ?: ($file['type'] ?? 'application/octet-stream');
    $relativePath = 'uploads/media/' . date('Y') . '/' . date('m') . '/' . $filename;

    return [
        'filename' => $filename,
        'path' => $relativePath,
        'mime_type' => $mimeType,
        'size_bytes' => (int) filesize($target),
    ];
}
