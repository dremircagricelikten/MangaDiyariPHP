<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/UserRepository.php';

use MangaDiyari\Core\Auth;
use MangaDiyari\Core\Database;
use MangaDiyari\Core\UserRepository;

Auth::start();
$user = Auth::user();

$username = trim($_GET['u'] ?? '');
if ($username === '') {
    http_response_code(404);
    echo 'Üye bulunamadı';
    exit;
}

$config = require __DIR__ . '/../config.php';
$site = $config['site'];

$pdo = Database::getConnection();
$userRepo = new UserRepository($pdo);
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
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($notFound ? 'Üye Bulunamadı' : $profile['username']) ?> - <?= htmlspecialchars($site['name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
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

    <footer class="py-4 bg-black text-center text-secondary">
      <div class="container">
        <small>© <?= date('Y') ?> <?= htmlspecialchars($site['name']) ?>. Tüm hakları saklıdır.</small>
      </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  </body>
</html>
