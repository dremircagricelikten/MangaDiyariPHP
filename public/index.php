<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/SettingRepository.php';
require_once __DIR__ . '/../src/WidgetRepository.php';
require_once __DIR__ . '/../src/SiteContext.php';
require_once __DIR__ . '/../src/MenuRepository.php';
require_once __DIR__ . '/../src/MangaRepository.php';
require_once __DIR__ . '/../src/ChapterRepository.php';
require_once __DIR__ . '/../src/UserRepository.php';

use MangaDiyari\Core\Auth;
use MangaDiyari\Core\Database;
use MangaDiyari\Core\SettingRepository;
use MangaDiyari\Core\WidgetRepository;
use MangaDiyari\Core\SiteContext;
use MangaDiyari\Core\MenuRepository;
use MangaDiyari\Core\MangaRepository;
use MangaDiyari\Core\ChapterRepository;
use MangaDiyari\Core\UserRepository;

Auth::start();
$user = Auth::user();

$context = SiteContext::build();
$site = $context['site'];
$menus = $context['menus'];
$ads = $context['ads'];
$analytics = $context['analytics'];

$pdo = Database::getConnection();
$settingRepo = new SettingRepository($pdo);
$widgetRepo = new WidgetRepository($pdo);
$mangaRepo = new MangaRepository($pdo);
$chapterRepo = new ChapterRepository($pdo);
$userRepo = new UserRepository($pdo);

$allSettings = $settingRepo->all();
$themeDefaults = [
    'primary_color' => '#5f2c82',
    'accent_color' => '#49a09d',
    'background_color' => '#05060c',
    'gradient_start' => '#5f2c82',
    'gradient_end' => '#49a09d',
];
$theme = array_replace($themeDefaults, $allSettings);
$kiSettings = [
    'currency_name' => $allSettings['ki_currency_name'] ?? 'Ki',
];
$footerText = trim((string) ($allSettings['site_footer'] ?? ''));
$defaultFooter = '© ' . date('Y') . ' ' . $site['name'] . '. Tüm hakları saklıdır.';

$activeWidgetList = $widgetRepo->getActive();
$activeWidgets = [];
$widgetsByArea = [
    'hero' => [],
    'main' => [],
    'sidebar' => [],
];
foreach ($activeWidgetList as $widget) {
    $activeWidgets[$widget['type']] = $widget;
    $area = $widget['area'] ?? 'main';
    if (!isset($widgetsByArea[$area])) {
        $widgetsByArea[$area] = [];
    }
    $widgetsByArea[$area][] = $widget;
}

$popularWidget = $activeWidgets['popular_slider'] ?? null;
$latestWidget = $activeWidgets['latest_updates'] ?? null;

$siteStats = [
    'manga_total' => $mangaRepo->count(),
    'chapter_total' => $chapterRepo->count(),
    'premium_total' => $chapterRepo->count(['premium_only' => true]),
    'active_members' => $userRepo->count(['active' => true]),
];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - <?= htmlspecialchars($site['tagline']) ?></title>
    <?php if (!empty($analytics['search_console'])): ?>
      <?= $analytics['search_console'] ?>
    <?php endif; ?>
    <?php if (!empty($analytics['google'])): ?>
      <?= $analytics['google'] ?>
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/styles.css">
    <style>
      :root {
        --color-primary: <?= htmlspecialchars($theme['primary_color']) ?>;
        --color-accent: <?= htmlspecialchars($theme['accent_color']) ?>;
        --color-background: <?= htmlspecialchars($theme['background_color']) ?>;
        --gradient-start: <?= htmlspecialchars($theme['gradient_start']) ?>;
        --gradient-end: <?= htmlspecialchars($theme['gradient_end']) ?>;
      }
    </style>
  </head>
  <body class="site-body" data-theme="dark">
    <?php $showSearchForm = true; require __DIR__ . '/partials/site-navbar.php'; ?>

    <?php if (!empty($ads['header'])): ?>
      <section class="ad-slot ad-slot--header py-3">
        <div class="container-xxl">
          <div class="ad-wrapper text-center">
            <?= $ads['header'] ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <header class="landing-hero py-5">
      <div class="container-xxl">
        <div class="row g-5 align-items-center">
          <div class="col-xl-5">
            <span class="eyebrow">Topluluk Mangaları</span>
            <h1 class="display-5 fw-bold mb-3"><?= htmlspecialchars($site['tagline']) ?></h1>
            <p class="lead text-secondary">Yeni seriler keşfedin, favorilerinizi takip edin ve toplulukla beraber anında yeni bölümlerden haberdar olun.</p>
            <div class="hero-stats mt-4">
              <div class="hero-stat">
                <span class="hero-stat__label">Seri</span>
                <span class="hero-stat__value"><?= number_format($siteStats['manga_total']) ?></span>
              </div>
              <div class="hero-stat">
                <span class="hero-stat__label">Bölüm</span>
                <span class="hero-stat__value"><?= number_format($siteStats['chapter_total']) ?></span>
              </div>
              <div class="hero-stat">
                <span class="hero-stat__label">Premium</span>
                <span class="hero-stat__value"><?= number_format($siteStats['premium_total']) ?></span>
              </div>
              <div class="hero-stat">
                <span class="hero-stat__label">Aktif Üye</span>
                <span class="hero-stat__value"><?= number_format($siteStats['active_members']) ?></span>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2 mt-3">
              <button class="btn btn-outline-light btn-sm" type="button" data-theme-toggle data-track="hero-theme-toggle">
                <i class="bi bi-moon-stars"></i>
                <span class="ms-1 d-none d-md-inline">Temayı Değiştir</span>
              </button>
              <span class="text-muted small" id="hero-theme-status">Koyu tema aktif</span>
            </div>
          </div>
          <div class="col-xl-7">
            <?php $heroWidgets = $widgetsByArea['hero'] ?? []; ?>
            <?php if (!empty($heroWidgets)): ?>
              <?php foreach ($heroWidgets as $widget): ?>
                <?php if ($widget['type'] === 'popular_slider'): ?>
                  <div class="hero-showcase">
                    <div id="featured-highlight" class="feature-highlight"></div>
                    <div class="feature-controls">
                      <div>
                        <label for="popular-sort" class="form-label small text-uppercase">Sıralama</label>
                        <select id="popular-sort" class="form-select">
                          <option value="random">Rastgele</option>
                          <option value="newest">En Yeni</option>
                          <option value="updated">Son Güncellenen</option>
                          <option value="alphabetical">Alfabetik</option>
                        </select>
                      </div>
                      <div>
                        <label for="popular-status" class="form-label small text-uppercase">Durum</label>
                        <select id="popular-status" class="form-select">
                          <option value="">Tümü</option>
                          <option value="ongoing">Devam Ediyor</option>
                          <option value="completed">Tamamlandı</option>
                          <option value="hiatus">Ara Verildi</option>
                        </select>
                      </div>
                    </div>
                    <div id="featured-rail" class="featured-rail mt-3"></div>
                  </div>
                <?php else: ?>
                  <div class="feature-placeholder">Yeni hero widget türü yakında desteklenecek.</div>
                <?php endif; ?>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="feature-placeholder">Ana vitrin bileşeni henüz etkin değil.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </header>

    <main class="landing-main py-5">
      <div class="container-xxl">
        <div class="layout-grid">
          <div class="layout-main">
            <?php $mainWidgets = $widgetsByArea['main'] ?? []; ?>
            <?php foreach ($mainWidgets as $widget): ?>
              <?php if ($widget['type'] === 'latest_updates'): ?>
                <section class="landing-section" data-widget="latest">
                  <div class="section-header">
                    <div>
                      <span class="eyebrow">Anlık Güncellemeler</span>
                      <h2><?= htmlspecialchars($widget['title']) ?></h2>
                      <p class="text-secondary">Yeni yüklenen bölümleri yakalayın.</p>
                    </div>
                    <div class="widget-controls d-flex gap-3 flex-wrap">
                      <div>
                        <label for="latest-sort" class="form-label small text-uppercase">Sıralama</label>
                        <select id="latest-sort" class="form-select form-select-sm">
                          <option value="newest">En Yeni</option>
                          <option value="oldest">En Eski</option>
                          <option value="chapter_desc">Bölüm No (Azalan)</option>
                          <option value="chapter_asc">Bölüm No (Artan)</option>
                        </select>
                      </div>
                      <div>
                        <label for="latest-status" class="form-label small text-uppercase">Durum</label>
                        <select id="latest-status" class="form-select form-select-sm">
                          <option value="">Tümü</option>
                          <option value="ongoing">Devam Ediyor</option>
                          <option value="completed">Tamamlandı</option>
                          <option value="hiatus">Ara Verildi</option>
                        </select>
                      </div>
                    </div>
                  </div>
                  <div class="row g-4" id="latest-list"></div>
                </section>
              <?php elseif ($widget['type'] === 'popular_slider'): ?>
                <section class="landing-section" data-widget="popular-grid">
                  <div class="section-header">
                    <div>
                      <span class="eyebrow">Trend</span>
                      <h2><?= htmlspecialchars($widget['title']) ?></h2>
                      <p class="text-secondary">Topluluğun favori serileri.</p>
                    </div>
                  </div>
                  <div class="row g-4" id="featured-grid"></div>
                </section>
              <?php else: ?>
                <section class="landing-section">
                  <div class="section-header">
                    <h2><?= htmlspecialchars($widget['title']) ?></h2>
                    <p class="text-secondary">Bu widget türü henüz özelleştirilmedi.</p>
                  </div>
                </section>
              <?php endif; ?>
            <?php endforeach; ?>

            <section id="discover" class="landing-section">
              <div class="section-header">
                <div>
                  <span class="eyebrow">Keşfet</span>
                  <h2>Koleksiyon</h2>
                  <p class="text-secondary">Arama sonuçları ve popüler mangalar burada listelenir.</p>
                </div>
              </div>
              <div class="row g-4" id="manga-list"></div>
            </section>
          </div>
          <aside class="layout-sidebar">
            <?php if (!empty($ads['sidebar'])): ?>
              <div class="ad-slot ad-slot--sidebar sticky-lg-top mb-4">
                <?= $ads['sidebar'] ?>
              </div>
            <?php endif; ?>

            <div class="sidebar-widget top-reads-widget" data-widget="top-reads">
              <div class="sidebar-widget__header">
                <div>
                  <h3>En Çok Okunanlar</h3>
                  <p class="small text-secondary mb-0" data-top-reads-status>Son 7 gün</p>
                </div>
                <div class="btn-group btn-group-sm top-reads-switch" role="group" aria-label="Zaman aralığı">
                  <button type="button" class="btn btn-outline-light" data-range="daily">Günlük</button>
                  <button type="button" class="btn btn-outline-light active" data-range="weekly">Haftalık</button>
                  <button type="button" class="btn btn-outline-light" data-range="monthly">Aylık</button>
                </div>
              </div>
              <div class="top-reads-content">
                <div class="top-reads-group">
                  <h4 class="top-reads-group__title">Seriler</h4>
                  <ol class="top-reads-list" data-top-reads="manga">
                    <li class="top-reads-empty">Veriler yükleniyor...</li>
                  </ol>
                </div>
                <div class="top-reads-group">
                  <h4 class="top-reads-group__title">Bölümler</h4>
                  <ol class="top-reads-list" data-top-reads="chapters">
                    <li class="top-reads-empty">Veriler yükleniyor...</li>
                  </ol>
                </div>
              </div>
            </div>

            <?php $sidebarWidgets = $widgetsByArea['sidebar'] ?? []; ?>
            <?php foreach ($sidebarWidgets as $widget): ?>
              <?php if ($widget['type'] === 'popular_slider'): ?>
                <div class="sidebar-widget">
                  <div class="sidebar-widget__header">
                    <h3><?= htmlspecialchars($widget['title']) ?></h3>
                  </div>
                  <div id="featured-sidebar" class="sidebar-list"></div>
                </div>
              <?php elseif ($widget['type'] === 'latest_updates'): ?>
                <div class="sidebar-widget">
                  <div class="sidebar-widget__header">
                    <h3><?= htmlspecialchars($widget['title']) ?></h3>
                  </div>
                  <div id="latest-sidebar" class="sidebar-list"></div>
                </div>
              <?php else: ?>
                <div class="sidebar-widget">
                  <div class="sidebar-widget__header">
                    <h3><?= htmlspecialchars($widget['title']) ?></h3>
                  </div>
                  <p class="small text-secondary mb-0">Bu widget türü henüz yan panelde desteklenmiyor.</p>
                </div>
              <?php endif; ?>
            <?php endforeach; ?>
          </aside>
        </div>
      </div>
    </main>

    <div id="site-chat-widget" class="chat-widget" data-page="index">
      <div class="chat-header">
        <strong>Sohbet</strong>
        <button class="btn-close btn-close-white" type="button" aria-label="Kapat"></button>
      </div>
      <div class="chat-body">
        <div class="chat-messages" id="chat-messages"></div>
      </div>
      <div class="chat-footer">
        <?php if ($user): ?>
          <form id="chat-form" class="d-flex gap-2">
            <input type="text" class="form-control" name="message" placeholder="Mesaj yaz..." autocomplete="off" required>
            <button class="btn btn-primary" type="submit">Gönder</button>
          </form>
        <?php else: ?>
          <div class="text-center small">Sohbet için <a href="login.php">giriş yapın</a>.</div>
        <?php endif; ?>
      </div>
      <button class="chat-toggle btn btn-primary rounded-circle" type="button" aria-label="Sohbeti aç">💬</button>
    </div>

    <footer class="site-footer py-4">
      <div class="container-xxl d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
        <small><?= $footerText !== '' ? $footerText : htmlspecialchars($defaultFooter) ?></small>
        <?php if (!empty($menus['footer']['items'])): ?>
          <ul class="nav footer-menu">
            <?php foreach ($menus['footer']['items'] as $item): ?>
              <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($item['url']) ?>" target="<?= htmlspecialchars($item['target']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
      <?php if (!empty($ads['footer'])): ?>
        <div class="container-xxl mt-3">
          <div class="ad-slot ad-slot--footer text-center">
            <?= $ads['footer'] ?>
          </div>
        </div>
      <?php endif; ?>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      window.currentUser = <?= json_encode($user ? [
          'id' => $user['id'],
          'username' => $user['username'],
          'ki_balance' => $user['ki_balance'] ?? 0,
      ] : null, JSON_UNESCAPED_UNICODE) ?>;
      window.kiSettings = <?= json_encode($kiSettings, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="assets/theme.js"></script>
    <script src="assets/chat.js"></script>
    <script>
      window.appWidgets = <?= json_encode($activeWidgets, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="assets/app.js"></script>
  </body>
</html>
