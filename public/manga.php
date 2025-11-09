<?php
require_once __DIR__ . '/../src/Auth.php';

use MangaDiyari\Core\Auth;

Auth::start();
$user = Auth::user();

$slug = $_GET['slug'] ?? '';
$config = require __DIR__ . '/../config.php';
$site = $config['site'];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Seri Detayı</title>
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

    <main class="container my-4" id="manga-detail" data-slug="<?= htmlspecialchars($slug) ?>">
      <div class="row g-4">
        <div class="col-md-4">
          <div class="cover-wrapper mb-3">
            <img id="manga-cover" class="img-fluid rounded" alt="Kapak görseli">
          </div>
          <div class="card bg-secondary border-0">
            <div class="card-body">
              <h5 class="card-title">Seri Bilgisi</h5>
              <dl class="row mb-0 small">
                <dt class="col-4">Durum</dt>
                <dd class="col-8" id="manga-status">-</dd>
                <dt class="col-4">Yazar</dt>
                <dd class="col-8" id="manga-author">-</dd>
                <dt class="col-4">Türler</dt>
                <dd class="col-8" id="manga-genres">-</dd>
                <dt class="col-4">Etiketler</dt>
                <dd class="col-8" id="manga-tags">-</dd>
              </dl>
            </div>
          </div>
        </div>
        <div class="col-md-8">
          <h1 id="manga-title" class="display-6 mb-3"></h1>
          <p id="manga-description" class="lead"></p>
          <h2 class="h4 mt-5">Bölümler</h2>
          <div class="list-group" id="chapter-list"></div>
        </div>
      </div>
    </main>

    <footer class="py-4 bg-black text-center text-secondary">
      <div class="container">
        <small>© <?= date('Y') ?> <?= htmlspecialchars($site['name']) ?>. Tüm hakları saklıdır.</small>
      </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/manga.js"></script>
  </body>
</html>
