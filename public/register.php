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
if (Auth::check()) {
    header('Location: index.php');
    exit;
}

$context = SiteContext::build();
$site = $context['site'];
$analytics = $context['analytics'];

$error = null;
$pdo = Database::getConnection();
$users = new UserRepository($pdo);
$settingRepo = new SettingRepository($pdo);
$themeDefaults = [
    'primary_color' => '#5f2c82',
    'accent_color' => '#49a09d',
    'background_color' => '#05060c',
    'gradient_start' => '#5f2c82',
    'gradient_end' => '#49a09d',
];
$theme = array_replace($themeDefaults, $settingRepo->all());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $passwordConfirm = $_POST['password_confirmation'] ?? '';

    if ($username === '' || $email === '' || $password === '') {
        $error = 'Lütfen tüm alanları doldurun.';
    } elseif ($password !== $passwordConfirm) {
        $error = 'Parolalar eşleşmiyor.';
    } else {
        try {
            $users->create([
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'role' => 'member',
            ]);
            header('Location: login.php?registered=1');
            exit;
        } catch (InvalidArgumentException $exception) {
            $error = $exception->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Kayıt Ol</title>
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
              <h1 class="h4 text-center mb-4">Yeni Üyelik</h1>
              <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
              <?php endif; ?>
              <form method="post" autocomplete="off">
                <div class="mb-3">
                  <label class="form-label">Kullanıcı Adı</label>
                  <input type="text" name="username" class="form-control" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
                </div>
                <div class="mb-3">
                  <label class="form-label">E-posta</label>
                  <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="mb-3">
                  <label class="form-label">Parola</label>
                  <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Parola (Tekrar)</label>
                  <input type="password" name="password_confirmation" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Kayıt Ol</button>
              </form>
              <div class="mt-3 text-center">
                <a href="login.php" class="link-light small">Zaten üyeliğiniz var mı? Giriş yapın.</a>
              </div>
              <div class="mt-2 text-center">
                <a href="index.php" class="link-light small">Ana sayfaya dön</a>
              </div>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/theme.js"></script>
  </body>
</html>
