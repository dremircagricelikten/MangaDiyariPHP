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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
  <body class="bg-dark text-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="/">
          <?php if (!empty($site['logo'])): ?>
            <img src="<?= htmlspecialchars($site['logo']) ?>" alt="<?= htmlspecialchars($site['name']) ?>" class="brand-logo">
          <?php endif; ?>
          <span><?= htmlspecialchars($site['name']) ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
          <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
            <?php if (!empty($primaryMenuItems)): ?>
              <?php foreach ($primaryMenuItems as $item): ?>
                <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($item['url']) ?>" target="<?= htmlspecialchars($item['target']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
              <?php endforeach; ?>
            <?php endif; ?>
            <?php if ($user): ?>
              <li class="nav-item"><span class="nav-link">Bakiye: <strong id="nav-ki-balance"><?= (int) ($user['ki_balance'] ?? 0) ?></strong> <?= htmlspecialchars($kiSettings['currency_name']) ?></span></li>
            <?php endif; ?>
            <?php if ($user && in_array($user['role'], ['admin', 'editor'], true)): ?>
              <li class="nav-item"><a class="nav-link" href="../admin/index.php">Yönetim</a></li>
            <?php endif; ?>
            <?php if ($user): ?>
              <?php $memberProfileUrl = 'member.php?u=' . urlencode($user['username']); ?>
              <li class="nav-item"><a class="nav-link" href="profile.php">Profilim</a></li>
              <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($memberProfileUrl) ?>">Kamu Profili</a></li>
              <li class="nav-item"><a class="nav-link" href="logout.php">Çıkış Yap</a></li>
            <?php else: ?>
              <li class="nav-item"><a class="nav-link" href="login.php">Giriş Yap</a></li>
              <li class="nav-item"><a class="nav-link" href="register.php">Kayıt Ol</a></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>

    <?php if (!empty($ads['header'])): ?>
      <section class="ad-slot ad-slot--header py-3">
        <div class="container">
          <div class="ad-wrapper text-center">
            <?= $ads['header'] ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <main class="container my-4" id="manga-detail" data-slug="<?= htmlspecialchars($slug) ?>">
      <div class="row g-4">
        <div class="col-md-4">
          <div class="cover-wrapper mb-3">
            <img id="manga-cover" class="img-fluid rounded" alt="Kapak görseli">
          </div>
          <div class="card bg-secondary border-0">
            <div class="card-body">
              <h5 class="card-title">Seri Bilgisi</h5>
              <dl class="row mb-0 small">
                <dt class="col-4">Durum</dt>
                <dd class="col-8" id="manga-status">-</dd>
                <dt class="col-4">Yazar</dt>
                <dd class="col-8" id="manga-author">-</dd>
                <dt class="col-4">Türler</dt>
                <dd class="col-8" id="manga-genres">-</dd>
                <dt class="col-4">Etiketler</dt>
                <dd class="col-8" id="manga-tags">-</dd>
              </dl>
            </div>
          </div>
          <?php if (!empty($ads['sidebar'])): ?>
            <div class="ad-slot ad-slot--sidebar mt-4">
              <?= $ads['sidebar'] ?>
            </div>
          <?php endif; ?>
        </div>
        <div class="col-md-8">
          <h1 id="manga-title" class="display-6 mb-3"></h1>
          <p id="manga-description" class="lead"></p>
          <h2 class="h4 mt-5">Bölümler</h2>
          <div class="list-group" id="chapter-list"></div>
        </div>
      </div>
    </main>

    <div id="site-chat-widget" class="chat-widget minimized" data-page="manga">
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

    <footer class="py-4 bg-black text-secondary">
      <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
        <small>© <?= date('Y') ?> <?= htmlspecialchars($site['name']) ?>. Tüm hakları saklıdır.</small>
        <?php if (!empty($footerMenuItems)): ?>
          <ul class="nav footer-menu">
            <?php foreach ($footerMenuItems as $item): ?>
              <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($item['url']) ?>" target="<?= htmlspecialchars($item['target']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
      <?php if (!empty($ads['footer'])): ?>
        <div class="container mt-3">
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
    <script src="assets/chat.js"></script>
    <script src="assets/manga.js"></script>
  </body>
</html>
