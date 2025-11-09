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
$chapterNumber = $_GET['chapter'] ?? '';

$context = SiteContext::build();
$site = $context['site'];
$menus = $context['menus'];
$ads = $context['ads'];
$analytics = $context['analytics'];

$pdo = Database::getConnection();
$settingRepo = new SettingRepository($pdo);
$themeDefaults = [
    'primary_color' => '#5f2c82',
    'accent_color' => '#49a09d',
    'background_color' => '#05060c',
    'gradient_start' => '#5f2c82',
    'gradient_end' => '#49a09d',
];
$theme = array_replace($themeDefaults, $settingRepo->all());

$primaryMenuItems = $menus['primary']['items'] ?? [];
$footerMenuItems = $menus['footer']['items'] ?? [];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Bölüm Okuma</title>
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

    <main class="container my-4" id="chapter-reader" data-slug="<?= htmlspecialchars($slug) ?>" data-chapter="<?= htmlspecialchars($chapterNumber) ?>">
      <div class="row g-4">
        <div class="<?php if (!empty($ads['sidebar'])): ?>col-lg-9<?php else: ?>col-12<?php endif; ?>">
          <div class="reader-header mb-4">
            <h1 class="h3" id="chapter-title"></h1>
            <div class="d-flex flex-wrap gap-2 align-items-center">
              <a class="btn btn-outline-light btn-sm" id="prev-chapter" href="#">Önceki</a>
              <a class="btn btn-outline-light btn-sm" id="next-chapter" href="#">Sonraki</a>
              <select class="form-select form-select-sm w-auto" id="chapter-select"></select>
            </div>
          </div>
          <article class="chapter-content" id="chapter-content"></article>
        </div>
        <?php if (!empty($ads['sidebar'])): ?>
          <aside class="col-lg-3">
            <div class="ad-slot ad-slot--sidebar sticky-lg-top">
              <?= $ads['sidebar'] ?>
            </div>
          </aside>
        <?php endif; ?>
      </div>
    </main>

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
    <script src="assets/chapter.js"></script>
  </body>
</html>
