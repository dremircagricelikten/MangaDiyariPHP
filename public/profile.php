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
if (!Auth::check()) {
    header('Location: login.php?redirect=profile.php');
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

$sessionUser = Auth::user();
$user = $userRepo->findById((int) $sessionUser['id']);
if (!$user) {
    Auth::logout();
    header('Location: login.php');
    exit;
}

unset($user['password_hash']);

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'update-profile') {
            $updated = $userRepo->updateProfile((int) $user['id'], [
                'email' => $_POST['email'] ?? '',
                'bio' => $_POST['bio'] ?? '',
                'avatar_url' => $_POST['avatar_url'] ?? '',
                'website_url' => $_POST['website_url'] ?? '',
            ]);
            Auth::login($updated);
            $sessionUser = Auth::user();
            $user = array_merge($user, $updated);
            $success = 'Profil bilgileriniz güncellendi.';
        } elseif ($action === 'change-password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['new_password_confirmation'] ?? '';

            if ($newPassword === '' || $currentPassword === '') {
                throw new InvalidArgumentException('Parola alanları boş bırakılamaz.');
            }

            if ($newPassword !== $confirmPassword) {
                throw new InvalidArgumentException('Yeni parolalar eşleşmiyor.');
            }

            if (strlen($newPassword) < 6) {
                throw new InvalidArgumentException('Yeni parola en az 6 karakter olmalıdır.');
            }

            if (!$userRepo->verifyCredentials($user['email'], $currentPassword)) {
                throw new InvalidArgumentException('Mevcut parolanızı doğrulayamadık.');
            }

            $updated = $userRepo->updateCredentials((int) $user['id'], null, null, $newPassword);
            Auth::login($updated);
            $sessionUser = Auth::user();
            $user = array_merge($user, $updated);
            $success = 'Parolanız başarıyla güncellendi.';
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
        $success = null;
    }
}

$joinDate = $user['created_at'] ?? null;
$joinDateFormatted = $joinDate ? date('d F Y', strtotime($joinDate)) : null;
$publicProfileUrl = 'member.php?u=' . urlencode($user['username']);

$primaryMenuItems = $menus['primary']['items'] ?? [];
$footerMenuItems = $menus['footer']['items'] ?? [];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Üye Profili</title>
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
            <?php if ($sessionUser && in_array($sessionUser['role'], ['admin', 'editor'], true)): ?>
              <li class="nav-item"><a class="nav-link" href="../admin/index.php">Yönetim</a></li>
            <?php endif; ?>
            <li class="nav-item"><a class="nav-link active" href="profile.php">Profilim</a></li>
            <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($publicProfileUrl) ?>">Kamu Profili</a></li>
            <li class="nav-item"><a class="nav-link" href="logout.php">Çıkış Yap</a></li>
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
      <div class="row g-4">
        <div class="col-lg-4">
          <div class="card bg-secondary border-0 shadow-sm h-100">
            <div class="card-body text-center p-4">
              <?php if (!empty($user['avatar_url'])): ?>
                <img src="<?= htmlspecialchars($user['avatar_url']) ?>" alt="Avatar" class="rounded-circle mb-3" width="120" height="120">
              <?php else: ?>
                <div class="rounded-circle bg-dark bg-opacity-50 d-inline-flex align-items-center justify-content-center mb-3" style="width: 120px; height: 120px; font-size: 48px;">
                  <?= htmlspecialchars(strtoupper(substr($user['username'], 0, 1))) ?>
                </div>
              <?php endif; ?>
              <h1 class="h4 mb-1"><?= htmlspecialchars($user['username']) ?></h1>
              <p class="text-muted small mb-2"><?= htmlspecialchars($user['email']) ?></p>
              <?php if ($joinDateFormatted): ?>
                <p class="text-muted small">Üyelik tarihi: <?= htmlspecialchars($joinDateFormatted) ?></p>
              <?php endif; ?>
              <a href="<?= htmlspecialchars($publicProfileUrl) ?>" class="btn btn-outline-light btn-sm">Kamu Profilini Görüntüle</a>
            </div>
          </div>
        </div>
        <div class="col-lg-8">
          <div class="card bg-secondary border-0 shadow-sm mb-4">
            <div class="card-body p-4">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h4 mb-0">Profil Bilgileri</h2>
                <?php if ($success): ?>
                  <span class="badge bg-success bg-opacity-75 text-light">Güncel</span>
                <?php endif; ?>
              </div>
              <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
              <?php elseif ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
              <?php endif; ?>
              <form method="post" class="vstack gap-3">
                <input type="hidden" name="action" value="update-profile">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Kullanıcı Adı</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" disabled>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">E-posta</label>
                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Avatar URL</label>
                    <input type="url" class="form-control" name="avatar_url" placeholder="https://" value="<?= htmlspecialchars($user['avatar_url'] ?? '') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Web Sitesi</label>
                    <input type="url" class="form-control" name="website_url" placeholder="https://" value="<?= htmlspecialchars($user['website_url'] ?? '') ?>">
                  </div>
                  <div class="col-12">
                    <label class="form-label">Hakkımda</label>
                    <textarea class="form-control" name="bio" rows="4" placeholder="Topluluğa kendinizi tanıtın."><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
                  </div>
                </div>
                <div>
                  <button type="submit" class="btn btn-primary">Profil Bilgilerini Kaydet</button>
                </div>
              </form>
            </div>
          </div>

          <div class="card bg-secondary border-0 shadow-sm">
            <div class="card-body p-4">
              <h2 class="h4 mb-3">Parolayı Güncelle</h2>
              <form method="post" class="row g-3">
                <input type="hidden" name="action" value="change-password">
                <div class="col-md-6">
                  <label class="form-label">Mevcut Parola</label>
                  <input type="password" class="form-control" name="current_password" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Yeni Parola</label>
                  <input type="password" class="form-control" name="new_password" minlength="6" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Yeni Parola (Tekrar)</label>
                  <input type="password" class="form-control" name="new_password_confirmation" minlength="6" required>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn btn-outline-light">Parolayı Güncelle</button>
                </div>
              </form>
            </div>
          </div>
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
