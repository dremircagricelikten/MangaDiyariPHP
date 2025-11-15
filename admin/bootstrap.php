<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/SiteContext.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/MangaRepository.php';
require_once __DIR__ . '/../src/ChapterRepository.php';
require_once __DIR__ . '/../src/UserRepository.php';
require_once __DIR__ . '/../src/SettingRepository.php';
require_once __DIR__ . '/../src/WidgetRepository.php';
require_once __DIR__ . '/../src/MenuRepository.php';
require_once __DIR__ . '/../src/KiRepository.php';
require_once __DIR__ . '/../src/PageRepository.php';
require_once __DIR__ . '/../src/InteractionRepository.php';
require_once __DIR__ . '/../src/PostRepository.php';
require_once __DIR__ . '/../src/TaxonomyRepository.php';
require_once __DIR__ . '/../src/MediaRepository.php';
require_once __DIR__ . '/../src/RoleRepository.php';

use MangaDiyari\Core\Auth;
use MangaDiyari\Core\SiteContext;
use MangaDiyari\Core\Database;
use MangaDiyari\Core\MangaRepository;
use MangaDiyari\Core\ChapterRepository;
use MangaDiyari\Core\UserRepository;
use MangaDiyari\Core\SettingRepository;
use MangaDiyari\Core\WidgetRepository;
use MangaDiyari\Core\MenuRepository;
use MangaDiyari\Core\KiRepository;
use MangaDiyari\Core\PageRepository;
use MangaDiyari\Core\InteractionRepository;
use MangaDiyari\Core\PostRepository;
use MangaDiyari\Core\TaxonomyRepository;
use MangaDiyari\Core\MediaRepository;
use MangaDiyari\Core\RoleRepository;

Auth::start();
if (!Auth::checkRole(['admin', 'editor'])) {
    header('Location: login.php');
    exit;
}

$config = require __DIR__ . '/../config.php';
$context = SiteContext::build();
$site = $context['site'];
$user = Auth::user();
$menus = $context['menus'];

$pdo = Database::getConnection();
$mangaRepo = new MangaRepository($pdo);
$chapterRepo = new ChapterRepository($pdo);
$userRepo = new UserRepository($pdo);
$settingRepo = new SettingRepository($pdo);
$widgetRepo = new WidgetRepository($pdo);
$menuRepo = new MenuRepository($pdo);
$kiRepo = new KiRepository($pdo);
$pageRepo = new PageRepository($pdo);
$interactionRepo = new InteractionRepository($pdo);
$postRepo = new PostRepository($pdo);
$taxonomyRepo = new TaxonomyRepository($pdo);
$mediaRepo = new MediaRepository($pdo);
$roleRepo = new RoleRepository($pdo);
$userRepo->setRoleRepository($roleRepo);

$dashboardStats = [
    'manga_total' => $mangaRepo->count(),
    'chapter_total' => $chapterRepo->count(),
    'chapter_premium' => $chapterRepo->count(['premium_only' => true]),
    'active_members' => $userRepo->count(['active' => true]),
];

function admin_page(string $page): bool
{
    $current = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '');
    $target = $page . '.php';

    return $current === $target;
}

function admin_nav_active(string $page): string
{
    return admin_page($page) ? 'is-active' : '';
}

