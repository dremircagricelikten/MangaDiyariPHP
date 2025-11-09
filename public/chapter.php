<?php
require_once __DIR__ . '/../src/Auth.php';

use MangaDiyari\Core\Auth;

Auth::start();
$user = Auth::user();

$slug = $_GET['slug'] ?? '';
$chapterNumber = $_GET['chapter'] ?? '';
$config = require __DIR__ . '/../config.php';
$site = $config['site'];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Bölüm Okuma</title>
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

    <main class="container my-4" id="chapter-reader" data-slug="<?= htmlspecialchars($slug) ?>" data-chapter="<?= htmlspecialchars($chapterNumber) ?>">
      <div class="reader-header mb-4">
        <h1 class="h3" id="chapter-title"></h1>
        <div class="d-flex flex-wrap gap-2 align-items-center">
          <a class="btn btn-outline-light btn-sm" id="prev-chapter" href="#">Önceki</a>
          <a class="btn btn-outline-light btn-sm" id="next-chapter" href="#">Sonraki</a>
          <select class="form-select form-select-sm w-auto" id="chapter-select"></select>
        </div>
      </div>
      <article class="chapter-content" id="chapter-content"></article>
    </main>

    <footer class="py-4 bg-black text-center text-secondary">
      <div class="container">
        <small>© <?= date('Y') ?> <?= htmlspecialchars($site['name']) ?>. Tüm hakları saklıdır.</small>
      </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/chapter.js"></script>
  </body>
</html>
