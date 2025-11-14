<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/UserRepository.php';
require_once __DIR__ . '/../src/SettingRepository.php';
require_once __DIR__ . '/../src/SiteContext.php';

use MangaDiyari\Core\Auth;
use MangaDiyari\Core\Database;
use MangaDiyari\Core\UserRepository;
use MangaDiyari\Core\SettingRepository;
use MangaDiyari\Core\SiteContext;

Auth::start();
$currentUser = Auth::user();

$username = trim($_GET['u'] ?? '');
if ($username === '') {
    http_response_code(404);
    echo 'Üye bulunamadı';
    exit;
}

$context = SiteContext::build();
$site = $context['site'];
$menus = $context['menus'];
$ads = $context['ads'];
$analytics = $context['analytics'];

$pdo = Database::getConnection();
$userRepo = new UserRepository($pdo);
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
$footerText = trim((string) ($allSettings['site_footer'] ?? ''));
$defaultFooter = '© ' . date('Y') . ' ' . $site['name'] . '. Tüm hakları saklıdır.';

$profile = $userRepo->findByUsername($username);
$notFound = false;
$joinDateFormatted = null;
$roleLabel = 'Üye';

if (!$profile || (int) ($profile['is_active'] ?? 1) !== 1) {
    http_response_code(404);
    $notFound = true;
} else {
    unset($profile['password_hash']);
    $joinDate = $profile['created_at'] ?? null;
    $joinDateFormatted = $joinDate ? date('d F Y', strtotime($joinDate)) : null;
    $roleLabels = [
        'admin' => 'Yönetici',
        'editor' => 'Editör',
        'member' => 'Üye',
    ];
    $roleLabel = $roleLabels[$profile['role']] ?? 'Üye';
}

$primaryMenuItems = $menus['primary']['items'] ?? [];
$footerMenuItems = $menus['footer']['items'] ?? [];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($notFound ? 'Üye Bulunamadı' : $profile['username']) ?> - <?= htmlspecialchars($site['name']) ?></title>
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
            <?php if ($currentUser && in_array($currentUser['role'], ['admin', 'editor'], true)): ?>
              <li class="nav-item"><a class="nav-link" href="../admin/index.php">Yönetim</a></li>
            <?php endif; ?>
            <?php if ($currentUser): ?>
              <?php $memberProfileUrl = 'member.php?u=' . urlencode($currentUser['username']); ?>
              <li class="nav-item"><a class="nav-link" href="profile.php">Profilim</a></li>
              <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($memberProfileUrl) ?>">Kamu Profilim</a></li>
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

    <main class="container my-5">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <?php if ($notFound): ?>
            <div class="alert alert-dark border-light text-center py-5">
              <h1 class="h4 mb-3">Üye bulunamadı</h1>
              <p class="mb-0">Aradığınız üye mevcut değil veya hesabı devre dışı bırakılmış.</p>
            </div>
          <?php else: ?>
            <div class="card bg-secondary border-0 shadow-lg">
              <div class="card-body p-5">
                <div class="d-flex flex-column flex-md-row align-items-center align-items-md-start gap-4">
                  <?php if (!empty($profile['avatar_url'])): ?>
                    <img src="<?= htmlspecialchars($profile['avatar_url']) ?>" alt="Avatar" class="rounded-circle" width="140" height="140">
                  <?php else: ?>
                    <div class="rounded-circle bg-dark bg-opacity-50 d-inline-flex align-items-center justify-content-center" style="width: 140px; height: 140px; font-size: 56px;">
                      <?= htmlspecialchars(strtoupper(substr($profile['username'], 0, 1))) ?>
                    </div>
                  <?php endif; ?>
                  <div class="flex-grow-1 text-center text-md-start">
                    <h1 class="display-6 mb-1"><?= htmlspecialchars($profile['username']) ?></h1>
                    <span class="badge bg-light text-dark mb-3"><?= htmlspecialchars($roleLabel) ?></span>
                    <?php if (!empty($profile['website_url'])): ?>
                      <p class="mb-2"><a href="<?= htmlspecialchars($profile['website_url']) ?>" class="link-light" target="_blank" rel="noopener">Web Sitesi</a></p>
                    <?php endif; ?>
                    <?php if ($joinDateFormatted): ?>
                      <p class="text-muted small mb-2">Üyelik tarihi: <?= htmlspecialchars($joinDateFormatted) ?></p>
                    <?php endif; ?>
                  </div>
                </div>
                <?php if (!empty($profile['bio'])): ?>
                  <hr class="border-light border-opacity-25 my-4">
                  <div>
                    <h2 class="h5 mb-3">Hakkında</h2>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($profile['bio'])) ?></p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </main>

    <footer class="py-4 bg-black text-secondary">
      <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
        <small><?= $footerText !== '' ? $footerText : htmlspecialchars($defaultFooter) ?></small>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
