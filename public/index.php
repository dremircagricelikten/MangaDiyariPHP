<?php
require_once __DIR__ . '/../src/Auth.php';

use MangaDiyari\Core\Auth;

Auth::start();
$user = Auth::user();

$config = require __DIR__ . '/../config.php';
$site = $config['site'];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - <?= htmlspecialchars($site['tagline']) ?></title>
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
            <li class="nav-item"><a class="nav-link" href="#populer">Popüler</a></li>
            <li class="nav-item"><a class="nav-link" href="#yeniler">Yeni Eklenenler</a></li>
            <?php if ($user && in_array($user['role'], ['admin', 'editor'], true)): ?>
              <li class="nav-item"><a class="nav-link" href="../admin/index.php">Yönetim</a></li>
            <?php endif; ?>
            <?php if ($user): ?>
              <li class="nav-item"><span class="nav-link disabled">Merhaba, <?= htmlspecialchars($user['username']) ?></span></li>
              <li class="nav-item"><a class="nav-link" href="logout.php">Çıkış Yap</a></li>
            <?php else: ?>
              <li class="nav-item"><a class="nav-link" href="login.php">Giriş Yap</a></li>
              <li class="nav-item"><a class="nav-link" href="register.php">Kayıt Ol</a></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>

    <header class="hero py-5">
      <div class="container">
        <div class="row align-items-center">
          <div class="col-lg-7">
            <h1 class="display-5 fw-bold"><?= htmlspecialchars($site['tagline']) ?></h1>
            <p class="lead">Topluluk tarafından yönetilen koleksiyonumuzla yeni seriler keşfedin, favorilerinizi takip edin ve anında yeni bölümlerden haberdar olun.</p>
            <form id="search-form" class="mt-4">
              <div class="row g-2">
                <div class="col-md-6">
                  <input type="search" id="search" class="form-control form-control-lg" placeholder="Manga ara...">
                </div>
                <div class="col-md-4">
                  <select id="status" class="form-select form-select-lg">
                    <option value="">Tüm Seriler</option>
                    <option value="ongoing">Devam Ediyor</option>
                    <option value="completed">Tamamlandı</option>
                    <option value="hiatus">Ara Verildi</option>
                  </select>
                </div>
                <div class="col-md-2 d-grid">
                  <button class="btn btn-primary btn-lg" type="submit">Ara</button>
                </div>
              </div>
            </form>
          </div>
          <div class="col-lg-5 d-none d-lg-block">
            <div class="hero-card shadow">
              <div id="featured-carousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner" id="featured-content"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </header>

    <main class="container my-5">
      <section id="yeniler" class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="section-title">Yeni Eklenenler</h2>
          <span class="text-secondary">Liste otomatik olarak güncellenir</span>
        </div>
        <div class="row" id="manga-list"></div>
      </section>

      <section id="populer" class="mb-5">
        <h2 class="section-title">Popüler Seriler</h2>
        <div class="row" id="featured-list"></div>
      </section>
    </main>

    <footer class="py-4 bg-black text-center text-secondary">
      <div class="container">
        <small>© <?= date('Y') ?> <?= htmlspecialchars($site['name']) ?>. Tüm hakları saklıdır.</small>
      </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/app.js"></script>
  </body>
</html>
