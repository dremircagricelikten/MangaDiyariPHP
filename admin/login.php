<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/UserRepository.php';

use MangaDiyari\Core\Auth;
use MangaDiyari\Core\Database;
use MangaDiyari\Core\UserRepository;

Auth::start();
if (Auth::checkRole(['admin', 'editor'])) {
    header('Location: index.php');
    exit;
}

$config = require __DIR__ . '/../config.php';
$site = $config['site'];
$error = null;

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
            $error = 'Geçersiz kullanıcı bilgileri.';
        } elseif (!in_array($user['role'], ['admin', 'editor'], true)) {
            $error = 'Bu hesaba yönetim paneli erişimi verilmemiş.';
        } else {
            unset($user['password_hash']);
            Auth::login($user);
            header('Location: index.php');
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
    <title><?= htmlspecialchars($site['name']) ?> - Yönetici Girişi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body class="bg-dark text-light d-flex align-items-center" style="min-height: 100vh;">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
          <div class="card bg-secondary border-0 shadow-lg">
            <div class="card-body p-4">
              <h1 class="h4 text-center mb-4">Yönetim Paneli Girişi</h1>
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
                <a href="../public/index.php" class="link-light small">Siteye dön</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
