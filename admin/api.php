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

use MangaDiyari\Core\Auth;
use MangaDiyari\Core\Database;
use MangaDiyari\Core\MangaRepository;
use MangaDiyari\Core\ChapterRepository;
use MangaDiyari\Core\SettingRepository;
use MangaDiyari\Core\WidgetRepository;
use MangaDiyari\Core\UserRepository;
use MangaDiyari\Core\MenuRepository;
use MangaDiyari\Core\Slugger;
use MangaDiyari\Core\KiRepository;

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
                $assets = persistChapterAssets($chapter['id'], $number, $preparedAssets);
                $chapter = $chapterRepo->update($chapter['id'], [
                    'assets' => $assets,
                ]) ?? $chapter;
            }

            echo json_encode(['message' => 'Bölüm başarıyla oluşturuldu', 'chapter' => $chapter]);
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
function persistChapterAssets(int $chapterId, string $chapterNumber, array $assets): array
{
    $baseDir = __DIR__ . '/../public/uploads/chapters';
    if (!is_dir($baseDir)) {
        mkdir($baseDir, 0775, true);
    }

    $chapterDir = $baseDir . '/' . $chapterId;
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
        if ($asset['uploaded']) {
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
