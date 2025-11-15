<?php
require_once __DIR__ . '/bootstrap.php';

$pageTitle = 'Medya Kütüphanesi';
$pageSubtitle = 'WordPress benzeri bir kütüphanede yüklediğiniz dosyaları yönetin.';
$headerActions = [
    ['href' => '#media-upload', 'label' => 'Yeni Medya', 'class' => 'btn-primary btn-sm', 'icon' => 'bi bi-cloud-upload'],
];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Medya Kütüphanesi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/admin.css">
  </head>
  <body class="admin-body text-light" data-admin-page="media">
    <div class="admin-shell">
      <?php require __DIR__ . '/partials/sidebar.php'; ?>
      <div class="admin-content">
        <?php require __DIR__ . '/partials/header.php'; ?>
        <main class="admin-main">
          <section class="admin-section is-active" id="media-upload">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Dosya Yükleme</span>
                <h2>Yeni Medya Ekle</h2>
                <p class="text-muted mb-0">Resim, video veya belgelerinizi yükleyin, otomatik olarak kütüphaneye eklensin.</p>
              </div>
            </div>
            <form id="media-form" class="card glass-card" enctype="multipart/form-data">
              <div class="card-body row g-4">
                <div class="col-md-6">
                  <label class="form-label">Dosya <span class="text-danger">*</span></label>
                  <input type="file" class="form-control" name="media_file" id="media-file" required>
                  <div class="form-text">Desteklenen: JPG, PNG, GIF, WEBP, SVG, PDF, MP4, ZIP</div>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Başlık</label>
                  <input type="text" class="form-control" name="title" id="media-title" placeholder="Opsiyonel">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Alternatif Metin</label>
                  <input type="text" class="form-control" name="alt_text" id="media-alt" placeholder="Opsiyonel">
                </div>
                <div class="col-12" id="media-form-message"></div>
              </div>
              <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-primary" type="submit"><i class="bi bi-cloud-upload me-1"></i>Yükle</button>
              </div>
            </form>
          </section>

          <section class="admin-section">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Kütüphane</span>
                <h2>Medya Listesi</h2>
                <p class="text-muted mb-0">Arama yapın, dosyaları indirin veya gerekirse silin.</p>
              </div>
            </div>
            <div class="card glass-card mb-3">
              <div class="card-body row g-3 align-items-end">
                <div class="col-md-6">
                  <label class="form-label">Arama</label>
                  <input type="search" class="form-control" id="media-search" placeholder="Dosya adı veya başlık">
                </div>
                <div class="col-md-2 d-grid ms-auto">
                  <button type="button" class="btn btn-outline-light" id="media-refresh"><i class="bi bi-arrow-repeat me-1"></i>Yenile</button>
                </div>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-dark table-hover align-middle" id="media-table">
                <thead>
                  <tr>
                    <th>Önizleme</th>
                    <th>Dosya</th>
                    <th>Tür</th>
                    <th>Boyut</th>
                    <th>Yükleyen</th>
                    <th>Tarih</th>
                    <th class="text-end">İşlemler</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
            <div id="media-empty" class="empty-state d-none">Henüz medya yüklenmedi.</div>
          </section>
        </main>
      </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin.js"></script>
  </body>
</html>
