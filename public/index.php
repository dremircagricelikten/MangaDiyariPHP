<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/SettingRepository.php';
require_once __DIR__ . '/../src/WidgetRepository.php';

use MangaDiyari\Core\Auth;
use MangaDiyari\Core\Database;
use MangaDiyari\Core\SettingRepository;
use MangaDiyari\Core\WidgetRepository;

Auth::start();
$user = Auth::user();

$config = require __DIR__ . '/../config.php';
$site = $config['site'];

$pdo = Database::getConnection();
$settingRepo = new SettingRepository($pdo);
$widgetRepo = new WidgetRepository($pdo);

$themeDefaults = [
    'primary_color' => '#5f2c82',
    'accent_color' => '#49a09d',
    'background_color' => '#05060c',
    'gradient_start' => '#5f2c82',
    'gradient_end' => '#49a09d',
];
$theme = array_replace($themeDefaults, $settingRepo->all());

$activeWidgets = [];
foreach ($widgetRepo->getActive() as $widget) {
    $activeWidgets[$widget['type']] = $widget;
}

$popularWidget = $activeWidgets['popular_slider'] ?? null;
$latestWidget = $activeWidgets['latest_updates'] ?? null;
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - <?= htmlspecialchars($site['tagline']) ?></title>
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
        <a class="navbar-brand" href="/"><?= htmlspecialchars($site['name']) ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
          <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
            <li class="nav-item"><a class="nav-link" href="/">Anasayfa</a></li>
            <?php if ($popularWidget): ?>
              <li class="nav-item"><a class="nav-link" href="#populer">Popüler</a></li>
            <?php endif; ?>
            <?php if ($latestWidget): ?>
              <li class="nav-item"><a class="nav-link" href="#latest-updates">Yeni Bölümler</a></li>
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

    <?php if ($popularWidget): ?>
    <header class="hero py-5">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-7">
            <h1 class="display-5 fw-bold"><?= htmlspecialchars($site['tagline']) ?></h1>
            <p class="lead">Topluluk tarafından yönetilen koleksiyonumuzla yeni seriler keşfedin, favorilerinizi takip edin ve anında yeni bölümlerden haberdar olun.</p>
            <form id="search-form" class="mt-4">
              <div class="row g-2">
                <div class="col-md-6">
                  <input type="search" id="search" class="form-control form-control-lg" placeholder="Manga ara...">
                </div>
                <div class="col-md-4">
                  <select id="status" class="form-select form-select-lg">
                    <option value="">Tüm Seriler</option>
                    <option value="ongoing">Devam Ediyor</option>
                    <option value="completed">Tamamlandı</option>
                    <option value="hiatus">Ara Verildi</option>
                  </select>
                </div>
                <div class="col-md-2 d-grid">
                  <button class="btn btn-primary btn-lg" type="submit">Ara</button>
                </div>
              </div>
            </form>
            <div class="widget-controls mt-4">
              <div class="row g-2 align-items-end">
                <div class="col-sm-4">
                  <label for="popular-sort" class="form-label small text-uppercase">Sıralama</label>
                  <select id="popular-sort" class="form-select">
                    <option value="random">Rastgele</option>
                    <option value="newest">En Yeni</option>
                    <option value="updated">Son Güncellenen</option>
                    <option value="alphabetical">Alfabetik</option>
                  </select>
                </div>
                <div class="col-sm-4">
                  <label for="popular-status" class="form-label small text-uppercase">Durum</label>
                  <select id="popular-status" class="form-select">
                    <option value="">Tümü</option>
                    <option value="ongoing">Devam Ediyor</option>
                    <option value="completed">Tamamlandı</option>
                    <option value="hiatus">Ara Verildi</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <div class="col-lg-5 d-none d-lg-block">
            <div class="hero-card shadow">
              <div id="featured-carousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner" id="featured-content"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>
    <?php else: ?>
    <section class="bg-dark py-5 border-bottom border-secondary">
      <div class="container">
        <div class="row g-4 align-items-center">
          <div class="col-lg-8">
            <h1 class="display-5 fw-bold mb-3"><?= htmlspecialchars($site['tagline']) ?></h1>
            <p class="lead mb-0">Topluluk tarafından yönetilen koleksiyonumuzla yeni seriler keşfedin, favorilerinizi takip edin ve anında yeni bölümlerden haberdar olun.</p>
          </div>
          <div class="col-lg-4">
            <form id="search-form" class="row g-2">
              <div class="col-12">
                <input type="search" id="search" class="form-control form-control-lg" placeholder="Manga ara...">
              </div>
              <div class="col-12">
                <select id="status" class="form-select form-select-lg">
                  <option value="">Tüm Seriler</option>
                  <option value="ongoing">Devam Ediyor</option>
                  <option value="completed">Tamamlandı</option>
                  <option value="hiatus">Ara Verildi</option>
                </select>
              </div>
              <div class="col-12 d-grid">
                <button class="btn btn-primary btn-lg" type="submit">Ara</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </section>
    <?php endif; ?>

    <main class="container my-5">
      <?php if ($latestWidget): ?>
      <section id="latest-updates" class="mb-5">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3 mb-3">
          <div>
            <h2 class="section-title mb-0"><?= htmlspecialchars($latestWidget['title']) ?></h2>
            <span class="text-secondary small">Yeni yüklenen bölümleri anında görüntüleyin.</span>
          </div>
          <div class="widget-controls d-flex gap-2 flex-wrap">
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
        <div class="row" id="latest-list"></div>
      </section>
      <?php endif; ?>

      <section id="yeniler" class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="section-title">Koleksiyon</h2>
          <span class="text-secondary">Arama sonuçları anlık olarak güncellenir.</span>
        </div>
        <div class="row" id="manga-list"></div>
      </section>

      <?php if ($popularWidget): ?>
      <section id="populer" class="mb-5">
        <h2 class="section-title"><?= htmlspecialchars($popularWidget['title']) ?></h2>
        <div class="row" id="featured-list"></div>
      </section>
      <?php endif; ?>
    </main>

    <footer class="py-4 bg-black text-center text-secondary">
      <div class="container">
        <small>© <?= date('Y') ?> <?= htmlspecialchars($site['name']) ?>. Tüm hakları saklıdır.</small>
      </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      window.appWidgets = <?= json_encode($activeWidgets, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="assets/app.js"></script>
  </body>
</html>
