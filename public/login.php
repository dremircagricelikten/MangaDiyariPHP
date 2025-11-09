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
$success = isset($_GET['registered']);

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
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login === '' || $password === '') {
        $error = 'Lütfen tüm alanları doldurun.';
    } else {
        $user = $users->verifyCredentials($login, $password);

        if (!$user) {
            $existing = filter_var($login, FILTER_VALIDATE_EMAIL)
                ? $users->findByEmail($login)
                : $users->findByUsername($login);

            if ($existing && isset($existing['is_active']) && (int) $existing['is_active'] === 0) {
                $error = 'Hesabınız pasif durumdadır. Lütfen yöneticiyle iletişime geçin.';
            } else {
                $error = 'Giriş bilgileri hatalı.';
            }
        } else {
            unset($user['password_hash']);
            Auth::login($user);
            $redirect = $_GET['redirect'] ?? 'index.php';
            header('Location: ' . $redirect);
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Üye Girişi</title>
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
  <body class="bg-dark text-light d-flex align-items-center" style="min-height: 100vh;">
    <div class="container py-5">
      <div class="row justify-content-center mb-4">
        <div class="col-auto text-center">
          <a class="navbar-brand d-inline-flex align-items-center gap-2" href="index.php">
            <?php if (!empty($site['logo'])): ?>
              <img src="<?= htmlspecialchars($site['logo']) ?>" alt="<?= htmlspecialchars($site['name']) ?>" class="brand-logo">
            <?php endif; ?>
            <span class="fs-5 fw-semibold"><?= htmlspecialchars($site['name']) ?></span>
          </a>
        </div>
      </div>
      <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
          <div class="card bg-secondary border-0 shadow-lg">
            <div class="card-body p-4">
              <h1 class="h4 text-center mb-4">Üye Girişi</h1>
              <?php if ($success): ?>
                <div class="alert alert-success">Kayıt işlemi tamamlandı. Şimdi giriş yapabilirsiniz.</div>
              <?php endif; ?>
              <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
              <?php endif; ?>
              <form method="post" autocomplete="off">
                <div class="mb-3">
                  <label class="form-label">Kullanıcı adı veya E-posta</label>
                  <input type="text" name="login" class="form-control" required value="<?= htmlspecialchars($_POST['login'] ?? '') ?>">
                </div>
                <div class="mb-3">
                  <label class="form-label">Parola</label>
                  <input type="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
              </form>
              <div class="mt-3 text-center">
                <a href="register.php" class="link-light small">Hesabınız yok mu? Kayıt olun.</a>
              </div>
              <div class="mt-2 text-center">
                <a href="index.php" class="link-light small">Ana sayfaya dön</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
