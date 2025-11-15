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
    <link rel="stylesheet" href="assets/tailwind.css">
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
      <section class="px-4 py-4">
        <div class="mx-auto max-w-7xl">
          <div class="ad-slot">
            <?= $ads['header'] ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <header class="px-4 py-12">
      <div class="mx-auto max-w-7xl">
        <div class="hero-shell">
          <div class="grid gap-6 lg:grid-cols-2 lg:gap-10">
            <div class="flex flex-col gap-4 text-soft">
              <span class="hero-pill">Topluluk Mangaları</span>
              <h1 class="hero-title text-white text-balance"><?= htmlspecialchars($site['tagline']) ?></h1>
              <p class="text-lg leading-relaxed">Yeni seriler keşfedin, favorilerinizi takip edin ve toplulukla beraber anında yeni bölümlerden haberdar olun.</p>
              <div class="flex flex-wrap gap-3 mt-4">
                <a class="btn btn-primary hover:shadow-xl" href="#discover"><i class="bi bi-collection-play"></i>Serileri Keşfet</a>
                <a class="btn btn-outline" href="#latest-feed"><i class="bi bi-lightning-charge"></i>Son Güncellemeler</a>
                <button class="btn btn-ghost text-muted gap-2" type="button" data-theme-toggle data-track="hero-theme-toggle">
                  <i class="bi bi-moon-stars"></i>
                  <span id="hero-theme-status">Koyu tema aktif</span>
                </button>
              </div>
              <div class="flex flex-wrap gap-2 text-sm text-muted">
                <a class="chip" href="#latest-feed"><i class="bi bi-broadcast"></i> Canlı Güncellemeler</a>
                <a class="chip" href="#discover"><i class="bi bi-compass"></i> Keşfet Rafı</a>
                <a class="chip" href="#top-reads"><i class="bi bi-graph-up"></i> En Çok Okunanlar</a>
              </div>
              <div class="grid hero-metrics gap-3 sm:grid-cols-2 lg:grid-cols-4 mt-6">
                <div class="hero-metric">
                  <span class="hero-metric__icon"><i class="bi bi-bookshelf"></i></span>
                  <span class="hero-metric__value"><?= number_format($siteStats['manga_total']) ?></span>
                  <span class="hero-metric__label">Seri</span>
                </div>
                <div class="hero-metric">
                  <span class="hero-metric__icon"><i class="bi bi-journal-richtext"></i></span>
                  <span class="hero-metric__value"><?= number_format($siteStats['chapter_total']) ?></span>
                  <span class="hero-metric__label">Bölüm</span>
                </div>
                <div class="hero-metric">
                  <span class="hero-metric__icon"><i class="bi bi-star"></i></span>
                  <span class="hero-metric__value"><?= number_format($siteStats['premium_total']) ?></span>
                  <span class="hero-metric__label">Premium</span>
                </div>
                <div class="hero-metric">
                  <span class="hero-metric__icon"><i class="bi bi-people"></i></span>
                  <span class="hero-metric__value"><?= number_format($siteStats['active_members']) ?></span>
                  <span class="hero-metric__label">Aktif Üye</span>
                </div>
              </div>
            </div>
            <div class="hero-showcase glass-panel h-full">
              <?php $heroWidgets = $widgetsByArea['hero'] ?? []; ?>
              <div class="flex flex-wrap justify-between items-center gap-3">
                <div>
                  <span class="text-xs uppercase tracking-wide text-muted">Öne Çıkanlar</span>
                  <h2 class="text-2xl font-semibold text-white mb-0">Trend rafını keşfet</h2>
                </div>
                <div class="flex flex-wrap gap-3 text-sm text-muted">
                  <div class="flex flex-col gap-1">
                    <label for="popular-sort" class="text-xs uppercase tracking-wide">Sıralama</label>
                    <select id="popular-sort" class="px-4 py-2 rounded-full border bg-transparent text-soft">
                      <option value="random">Rastgele</option>
                      <option value="newest">En Yeni</option>
                      <option value="updated">Son Güncellenen</option>
                      <option value="alphabetical">Alfabetik</option>
                    </select>
                  </div>
                  <div class="flex flex-col gap-1">
                    <label for="popular-status" class="text-xs uppercase tracking-wide">Durum</label>
                    <select id="popular-status" class="px-4 py-2 rounded-full border bg-transparent text-soft">
                      <option value="">Tümü</option>
                      <option value="ongoing">Devam Ediyor</option>
                      <option value="completed">Tamamlandı</option>
                      <option value="hiatus">Ara Verildi</option>
                    </select>
                  </div>
                </div>
              </div>
              <div class="hero-showcase__body">
                <?php if (!empty($heroWidgets)): ?>
                  <?php foreach ($heroWidgets as $widget): ?>
                    <?php if ($widget['type'] === 'popular_slider'): ?>
                      <div class="space-y-4">
                        <div id="featured-highlight" class="feature-highlight"></div>
                        <div id="featured-rail" class="featured-rail"></div>
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
        </div>
      </div>
    </header>

    <main class="px-4 pb-16" id="discover">
      <div class="mx-auto max-w-7xl">
        <div class="layout-grid">
          <div class="layout-main space-y-8">
            <?php $mainWidgets = $widgetsByArea['main'] ?? []; ?>
            <?php foreach ($mainWidgets as $widget): ?>
              <?php if ($widget['type'] === 'latest_updates'): ?>
                <section class="glass-panel space-y-6" data-widget="latest" id="latest-feed">
                  <div class="flex flex-wrap justify-between gap-4 items-center">
                    <div class="space-y-2">
                      <span class="text-xs uppercase tracking-wide text-muted">Anlık Güncellemeler</span>
                      <h2 class="text-2xl font-semibold text-white"><?= htmlspecialchars($widget['title']) ?></h2>
                      <p class="text-sm text-muted">Yeni yüklenen bölümleri yakalayın.</p>
                    </div>
                    <div class="flex flex-wrap gap-4 text-sm text-muted">
                      <div class="flex flex-col gap-1">
                        <label for="latest-sort" class="text-xs uppercase tracking-wide">Sıralama</label>
                        <select id="latest-sort" class="px-4 py-2 rounded-full border bg-transparent text-soft">
                          <option value="newest">En Yeni</option>
                          <option value="oldest">En Eski</option>
                          <option value="chapter_desc">Bölüm No (Azalan)</option>
                          <option value="chapter_asc">Bölüm No (Artan)</option>
                        </select>
                      </div>
                      <div class="flex flex-col gap-1">
                        <label for="latest-status" class="text-xs uppercase tracking-wide">Durum</label>
                        <select id="latest-status" class="px-4 py-2 rounded-full border bg-transparent text-soft">
                          <option value="">Tümü</option>
                          <option value="ongoing">Devam Ediyor</option>
                          <option value="completed">Tamamlandı</option>
                          <option value="hiatus">Ara Verildi</option>
                        </select>
                      </div>
                    </div>
                  </div>
                  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4" id="latest-list"></div>
                </section>
              <?php elseif ($widget['type'] === 'popular_slider'): ?>
                <section class="glass-panel space-y-6" data-widget="popular-grid">
                  <div class="space-y-2">
                    <span class="text-xs uppercase tracking-wide text-muted">Trend</span>
                    <h2 class="text-2xl font-semibold text-white"><?= htmlspecialchars($widget['title']) ?></h2>
                    <p class="text-sm text-muted">Topluluğun favori serileri.</p>
                  </div>
                  <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4" id="featured-grid"></div>
                </section>
              <?php else: ?>
                <section class="glass-panel space-y-3">
                  <div class="space-y-2">
                    <h2 class="text-xl font-semibold text-white"><?= htmlspecialchars($widget['title']) ?></h2>
                    <p class="text-sm text-muted">Bu widget türü henüz özelleştirilmedi.</p>
                  </div>
                </section>
              <?php endif; ?>
            <?php endforeach; ?>

            <section class="glass-panel space-y-4" id="discover-grid">
              <div class="space-y-2">
                <span class="text-xs uppercase tracking-wide text-muted">Keşfet</span>
                <h2 class="text-2xl font-semibold text-white">Koleksiyon</h2>
                <p class="text-sm text-muted">Arama sonuçları ve popüler mangalar burada listelenir.</p>
              </div>
              <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4" id="manga-list"></div>
            </section>
          </div>
          <aside class="layout-sidebar space-y-6">
            <?php if (!empty($ads['sidebar'])): ?>
              <div class="ad-slot sticky top-6">
                <?= $ads['sidebar'] ?>
              </div>
            <?php endif; ?>

            <div class="sidebar-widget" data-widget="top-reads" id="top-reads">
              <div class="flex items-center justify-between gap-3">
                <div>
                  <h3 class="text-xl font-semibold text-white">En Çok Okunanlar</h3>
                  <p class="text-sm text-muted mb-0" data-top-reads-status>Son 7 gün</p>
                </div>
                <div class="top-reads-switch flex items-center gap-2" role="group" aria-label="Zaman aralığı">
                  <button type="button" class="btn" data-range="daily">Günlük</button>
                  <button type="button" class="btn active" data-range="weekly">Haftalık</button>
                  <button type="button" class="btn" data-range="monthly">Aylık</button>
                </div>
              </div>
              <div class="space-y-4 mt-4">
                <div class="space-y-2">
                  <h4 class="text-sm uppercase tracking-wide text-muted">Seriler</h4>
                  <ol class="top-reads-list" data-top-reads="manga">
                    <li class="top-reads-empty">Veriler yükleniyor...</li>
                  </ol>
                </div>
                <div class="space-y-2">
                  <h4 class="text-sm uppercase tracking-wide text-muted">Bölümler</h4>
                  <ol class="top-reads-list" data-top-reads="chapters">
                    <li class="top-reads-empty">Veriler yükleniyor...</li>
                  </ol>
                </div>
              </div>
            </div>

            <?php $sidebarWidgets = $widgetsByArea['sidebar'] ?? []; ?>
            <?php foreach ($sidebarWidgets as $widget): ?>
              <?php if ($widget['type'] === 'popular_slider'): ?>
                <div class="sidebar-widget space-y-3">
                  <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-white"><?= htmlspecialchars($widget['title']) ?></h3>
                  </div>
                  <div id="featured-sidebar" class="sidebar-list"></div>
                </div>
              <?php elseif ($widget['type'] === 'latest_updates'): ?>
                <div class="sidebar-widget space-y-3">
                  <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-white"><?= htmlspecialchars($widget['title']) ?></h3>
                  </div>
                  <div id="latest-sidebar" class="sidebar-list"></div>
                </div>
              <?php else: ?>
                <div class="sidebar-widget space-y-2">
                  <h3 class="text-lg font-semibold text-white"><?= htmlspecialchars($widget['title']) ?></h3>
                  <p class="text-sm text-muted mb-0">Bu widget türü henüz yan panelde desteklenmiyor.</p>
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
        <button class="nav-trigger" type="button" aria-label="Kapat" data-chat-close><i class="bi bi-x-lg"></i></button>
      </div>
      <div class="chat-body">
        <div class="chat-messages" id="chat-messages"></div>
      </div>
      <div class="chat-footer">
        <?php if ($user): ?>
          <form id="chat-form" class="flex gap-3">
            <input type="text" class="w-full" name="message" placeholder="Mesaj yaz..." autocomplete="off" required>
            <button class="btn btn-primary" type="submit">Gönder</button>
          </form>
        <?php else: ?>
          <div class="text-center text-sm text-muted">Sohbet için <a href="login.php" class="text-primary">giriş yapın</a>.</div>
        <?php endif; ?>
      </div>
      <button class="chat-toggle btn btn-primary" type="button" aria-label="Sohbeti aç">💬</button>
    </div>

    <footer class="site-footer py-6 mt-12">
      <div class="mx-auto max-w-7xl px-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between text-muted">
        <small><?= $footerText !== '' ? $footerText : htmlspecialchars($defaultFooter) ?></small>
        <?php if (!empty($menus['footer']['items'])): ?>
          <ul class="footer-menu">
            <?php foreach ($menus['footer']['items'] as $item): ?>
              <li><a class="nav-link" href="<?= htmlspecialchars($item['url']) ?>" target="<?= htmlspecialchars($item['target']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
      <?php if (!empty($ads['footer'])): ?>
        <div class="mx-auto max-w-7xl px-4 mt-4">
          <div class="ad-slot">
            <?= $ads['footer'] ?>
          </div>
        </div>
      <?php endif; ?>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
