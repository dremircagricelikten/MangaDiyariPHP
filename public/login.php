<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/UserRepository.php';

use MangaDiyari\Core\Auth;
use MangaDiyari\Core\Database;
use MangaDiyari\Core\UserRepository;

Auth::start();
if (Auth::check()) {
    header('Location: index.php');
    exit;
}

$config = require __DIR__ . '/../config.php';
$site = $config['site'];
$error = null;
$success = isset($_GET['registered']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login === '' || $password === '') {
        $error = 'Lütfen tüm alanları doldurun.';
    } else {
        $pdo = Database::getConnection();
        $users = new UserRepository($pdo);
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body class="bg-dark text-light d-flex align-items-center" style="min-height: 100vh;">
    <div class="container">
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
