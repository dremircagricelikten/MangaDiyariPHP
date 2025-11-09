<?php
require_once __DIR__ . '/../src/Auth.php';

use MangaDiyari\Core\Auth;

Auth::start();
if (!Auth::checkRole(['admin', 'editor'])) {
    header('Location: login.php');
    exit;
}

$config = require __DIR__ . '/../config.php';
$site = $config['site'];
$user = Auth::user();
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Yönetim Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin.css">
  </head>
  <body class="bg-dark text-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient">
      <div class="container">
        <a class="navbar-brand" href="../public/index.php"><?= htmlspecialchars($site['name']) ?></a>
        <div class="d-flex align-items-center gap-3 text-end">
          <div class="small">
            <div class="fw-semibold"><?= htmlspecialchars($user['username']) ?></div>
            <div class="text-muted">Rol: <?= htmlspecialchars($user['role']) ?></div>
          </div>
          <a class="btn btn-outline-light btn-sm" href="logout.php">Çıkış Yap</a>
        </div>
      </div>
    </nav>

    <main class="container my-5">
      <h1 class="h3 mb-4">Yönetim Paneli</h1>
      <div class="alert alert-info bg-opacity-25 border-light text-light">
        Yeni içerik eklemek için aşağıdaki formları kullanın. Formlar yalnızca yetkili roller tarafından kullanılabilir.
      </div>
      <div class="row g-4">
        <div class="col-lg-6">
          <div class="card bg-secondary border-0 shadow-sm">
            <div class="card-body">
              <h2 class="h4 mb-3">Yeni Seri Ekle</h2>
              <form id="manga-form">
                <div class="mb-3">
                  <label class="form-label">Seri Başlığı</label>
                  <input type="text" class="form-control" name="title" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Kapak Görseli URL</label>
                  <input type="url" class="form-control" name="cover_image" placeholder="https://">
                </div>
                <div class="mb-3">
                  <label class="form-label">Yazar</label>
                  <input type="text" class="form-control" name="author">
                </div>
                <div class="mb-3">
                  <label class="form-label">Durum</label>
                  <select class="form-select" name="status">
                    <option value="ongoing">Devam Ediyor</option>
                    <option value="completed">Tamamlandı</option>
                    <option value="hiatus">Ara Verildi</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Türler</label>
                  <input type="text" class="form-control" name="genres" placeholder="Aksiyon, Fantastik">
                </div>
                <div class="mb-3">
                  <label class="form-label">Etiketler</label>
                  <input type="text" class="form-control" name="tags" placeholder="shounen, macera">
                </div>
                <div class="mb-3">
                  <label class="form-label">Açıklama</label>
                  <textarea class="form-control" name="description" rows="4"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Seriyi Kaydet</button>
                <div class="mt-3" id="manga-form-message"></div>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card bg-secondary border-0 shadow-sm">
            <div class="card-body">
              <h2 class="h4 mb-3">Bölüm Ekle</h2>
              <form id="chapter-form">
                <div class="mb-3">
                  <label class="form-label">Seri</label>
                  <select class="form-select" name="manga_id" id="manga-select"></select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Bölüm Numarası</label>
                  <input type="text" class="form-control" name="number" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Başlık</label>
                  <input type="text" class="form-control" name="title">
                </div>
                <div class="mb-3">
                  <label class="form-label">İçerik</label>
                  <textarea class="form-control" name="content" rows="6" placeholder="Her satıra bir paragraf veya sayfa URL'si"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Bölümü Kaydet</button>
                <div class="mt-3" id="chapter-form-message"></div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-4 mt-1">
        <div class="col-lg-5">
          <div class="card bg-secondary border-0 shadow-sm h-100">
            <div class="card-body">
              <h2 class="h4 mb-3">Tema Ayarları</h2>
              <p class="text-muted small">Renk paletini değiştirerek sitenin görünümünü özelleştirebilirsiniz.</p>
              <form id="theme-form" class="row g-3">
                <div class="col-md-6">
                  <label class="form-label" for="primary_color">Birincil Renk</label>
                  <input type="color" class="form-control form-control-color" id="primary_color" name="primary_color" value="#5f2c82">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="accent_color">Vurgu Rengi</label>
                  <input type="color" class="form-control form-control-color" id="accent_color" name="accent_color" value="#49a09d">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="background_color">Arkaplan Rengi</label>
                  <input type="color" class="form-control form-control-color" id="background_color" name="background_color" value="#05060c">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="gradient_start">Gradyan Başlangıcı</label>
                  <input type="color" class="form-control form-control-color" id="gradient_start" name="gradient_start" value="#5f2c82">
                </div>
                <div class="col-md-6">
                  <label class="form-label" for="gradient_end">Gradyan Bitişi</label>
                  <input type="color" class="form-control form-control-color" id="gradient_end" name="gradient_end" value="#49a09d">
                </div>
                <div class="col-12">
                  <button type="submit" class="btn btn-primary">Tema Ayarlarını Kaydet</button>
                  <div class="mt-3" id="theme-form-message"></div>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="col-lg-7">
          <div class="card bg-secondary border-0 shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h2 class="h4 mb-0">Ana Sayfa Bileşenleri</h2>
                <span class="badge bg-info text-dark">Widget Sistemi</span>
              </div>
              <p class="text-muted small">Bileşenleri etkinleştirerek veya sırasını değiştirerek ana sayfayı şekillendirin.</p>
              <div id="widget-list" class="vstack gap-3"></div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin.js"></script>
  </body>
</html>
