<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/SettingRepository.php';
require_once __DIR__ . '/../src/SiteContext.php';

use MangaDiyari\Core\Auth;
use MangaDiyari\Core\Database;
use MangaDiyari\Core\SettingRepository;
use MangaDiyari\Core\SiteContext;

Auth::start();
$user = Auth::user();

$slug = $_GET['slug'] ?? '';

$context = SiteContext::build();
$site = $context['site'];
$menus = $context['menus'];
$ads = $context['ads'];
$analytics = $context['analytics'];

$pdo = Database::getConnection();
$settingRepo = new SettingRepository($pdo);
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

$primaryMenuItems = $menus['primary']['items'] ?? [];
$footerMenuItems = $menus['footer']['items'] ?? [];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Seri Detayı</title>
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
    <?php $showSearchForm = false; require __DIR__ . '/partials/site-navbar.php'; ?>

    <?php if (!empty($ads['header'])): ?>
      <section class="px-4 py-4">
        <div class="mx-auto max-w-6xl">
          <div class="ad-slot">
            <?= $ads['header'] ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <main class="px-4 py-10" id="manga-detail" data-slug="<?= htmlspecialchars($slug) ?>">
      <div class="mx-auto max-w-6xl grid gap-6 lg:grid-cols-3">
        <div class="space-y-6">
          <div class="glass-panel p-0 overflow-hidden">
            <img id="manga-cover" class="w-full h-full object-cover" alt="Kapak görseli">
          </div>
          <div class="glass-panel space-y-3">
            <h2 class="text-xl font-semibold text-white">Seri Bilgisi</h2>
            <dl class="space-y-3 text-sm text-muted">
              <div class="flex justify-between"><dt>Durum</dt><dd id="manga-status">-</dd></div>
              <div class="flex justify-between"><dt>Yazar</dt><dd id="manga-author">-</dd></div>
              <div class="flex justify-between"><dt>Türler</dt><dd id="manga-genres">-</dd></div>
              <div class="flex justify-between"><dt>Etiketler</dt><dd id="manga-tags">-</dd></div>
            </dl>
          </div>
          <?php if (!empty($ads['sidebar'])): ?>
            <div class="ad-slot">
              <?= $ads['sidebar'] ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="lg:col-span-2 space-y-6">
          <div class="glass-panel space-y-4">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
              <h1 id="manga-title" class="text-3xl font-bold text-white mb-0"></h1>
              <div id="follow-controls" class="flex items-center gap-2">
                <button id="follow-btn" type="button" class="btn btn-primary btn-sm">
                  <span class="follow-btn-text">Takip Et</span>
                </button>
                <button id="unfollow-btn" type="button" class="btn btn-outline btn-sm hidden">
                  <span class="unfollow-btn-text">Destekten Çık</span>
                </button>
              </div>
            </div>
            <p id="followers-info" class="text-sm text-muted hidden"></p>
            <p id="manga-description" class="text-base leading-relaxed"></p>
          </div>
          <div class="glass-panel space-y-4">
            <div class="flex items-center justify-between">
              <h2 class="text-2xl font-semibold text-white">Bölümler</h2>
            </div>
            <div class="list-group" id="chapter-list"></div>
          </div>
        </div>
      </div>
    </main>

    <div id="site-chat-widget" class="chat-widget minimized" data-page="manga">
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
      <div class="mx-auto max-w-6xl px-4 flex flex-col gap-4 md:flex-row md:items-center md:justify-between text-muted">
        <small><?= $footerText !== '' ? $footerText : htmlspecialchars($defaultFooter) ?></small>
        <?php if (!empty($footerMenuItems)): ?>
          <ul class="footer-menu">
            <?php foreach ($footerMenuItems as $item): ?>
              <li><a class="nav-link" href="<?= htmlspecialchars($item['url']) ?>" target="<?= htmlspecialchars($item['target']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
      <?php if (!empty($ads['footer'])): ?>
        <div class="mx-auto max-w-6xl px-4 mt-4">
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
    <script src="assets/manga.js"></script>
  </body>
</html>
