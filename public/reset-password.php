<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/UserRepository.php';
require_once __DIR__ . '/../src/SettingRepository.php';
require_once __DIR__ . '/../src/PasswordResetRepository.php';
require_once __DIR__ . '/../src/SiteContext.php';

use MangaDiyari\Core\Auth;
use MangaDiyari\Core\Database;
use MangaDiyari\Core\UserRepository;
use MangaDiyari\Core\SettingRepository;
use MangaDiyari\Core\PasswordResetRepository;
use MangaDiyari\Core\SiteContext;

Auth::start();
if (Auth::check()) {
    header('Location: index.php');
    exit;
}

$token = $_GET['token'] ?? ($_POST['token'] ?? '');
$token = is_string($token) ? trim($token) : '';

$context = SiteContext::build();
$site = $context['site'];
$analytics = $context['analytics'];

$pdo = Database::getConnection();
$users = new UserRepository($pdo);
$settingRepo = new SettingRepository($pdo);
$resetRepo = new PasswordResetRepository($pdo);
$themeDefaults = [
    'primary_color' => '#5f2c82',
    'accent_color' => '#49a09d',
    'background_color' => '#05060c',
    'gradient_start' => '#5f2c82',
    'gradient_end' => '#49a09d',
];
$theme = array_replace($themeDefaults, $settingRepo->all());

$error = null;
$success = false;
$record = null;

if ($token !== '') {
    $record = $resetRepo->findValidToken($token);
    if (!$record) {
        $error = 'Şifre sıfırlama bağlantısı geçersiz veya süresi dolmuş.';
    }
} else {
    $error = 'Şifre sıfırlama bağlantısı eksik.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $record) {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['password_confirmation'] ?? '';

    if ($password === '' || strlen($password) < 6) {
        $error = 'Parola en az 6 karakter olmalıdır.';
    } elseif ($password !== $confirm) {
        $error = 'Parolalar uyuşmuyor.';
    } else {
        $users->updatePassword((int) $record['user_id'], $password);
        $resetRepo->deleteByToken($token);
        header('Location: login.php?reset=1');
        exit;
    }
}
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Şifre Sıfırlama</title>
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
  <body class="site-body auth-page" data-theme="dark">
    <div class="auth-shell">
      <div class="auth-brand">
        <a class="navbar-brand" href="index.php">
          <?php if (!empty($site['logo'])): ?>
            <img src="<?= htmlspecialchars($site['logo']) ?>" alt="<?= htmlspecialchars($site['name']) ?>" class="brand-logo">
          <?php endif; ?>
          <span><?= htmlspecialchars($site['name']) ?></span>
        </a>
      </div>
      <div class="card auth-card border-0">
        <div class="card-body">
          <h1 class="h4 text-center mb-4">Şifreyi Sıfırla</h1>
          <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
          <?php endif; ?>
          <?php if ($record): ?>
            <form method="post" autocomplete="off">
              <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
              <div class="mb-3">
                <label class="form-label">Yeni Parola</label>
                <input type="password" name="password" class="form-control" required minlength="6">
              </div>
              <div class="mb-3">
                <label class="form-label">Parolayı Doğrula</label>
                <input type="password" name="password_confirmation" class="form-control" required minlength="6">
              </div>
              <button type="submit" class="btn btn-primary w-100">Parolayı Güncelle</button>
            </form>
          <?php else: ?>
            <div class="text-center">
              <a href="forgot-password.php" class="btn btn-outline-light">Yeni bağlantı iste</a>
            </div>
          <?php endif; ?>
          <div class="mt-3 text-center">
            <a href="login.php" class="link-light small">Giriş sayfasına dön</a>
          </div>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/theme.js"></script>
  </body>
</html>
