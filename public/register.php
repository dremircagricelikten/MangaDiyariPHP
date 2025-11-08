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
            $pdo = Database::getConnection();
            $users = new UserRepository($pdo);
            $users->create([
                'username' => $username,
                'email' => $email,
                'password' => $password,
                'role' => 'member',
            ]);
            header('Location: login.php?registered=1');
            exit;
        } catch (\InvalidArgumentException $exception) {
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  </head>
  <body class="bg-dark text-light d-flex align-items-center" style="min-height: 100vh;">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-md-6 col-lg-4">
          <div class="card bg-secondary border-0 shadow-lg">
            <div class="card-body p-4">
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
      </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
