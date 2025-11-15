<?php
require_once __DIR__ . '/bootstrap.php';

$pageTitle = 'Araçlar';
$pageSubtitle = 'Bakım, içe aktarma ve analiz görevlerini tek ekrandan çalıştırın.';
$headerActions = [];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Araçlar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/admin.css">
  </head>
  <body class="admin-body text-light" data-admin-page="tools">
    <div class="admin-shell">
      <?php require __DIR__ . '/partials/sidebar.php'; ?>
      <div class="admin-content">
        <?php require __DIR__ . '/partials/header.php'; ?>
        <main class="admin-main">
          <section class="admin-section is-active">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Bakım</span>
                <h2>Hazır Araçlar</h2>
                <p class="text-muted mb-0">Önbellek temizleme, istatistik yenileme veya harici servis senkronizasyonlarını başlatın.</p>
              </div>
            </div>
            <div id="tool-message" class="mb-3"></div>
            <div class="row g-4" id="tool-list">
              <div class="col-md-6">
                <div class="card admin-card h-100">
                  <div class="card-body d-flex flex-column gap-2">
                    <h3 class="h5">İstatistikleri Yenile</h3>
                    <p class="text-muted small mb-0">Manga, bölüm ve kullanıcı sayaçlarını yeniden hesaplar.</p>
                    <button class="btn btn-outline-light mt-auto" data-tool="refresh-stats"><i class="bi bi-arrow-clockwise me-1"></i>Çalıştır</button>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card admin-card h-100">
                  <div class="card-body d-flex flex-column gap-2">
                    <h3 class="h5">Önbelleği Temizle</h3>
                    <p class="text-muted small mb-0">Sık kullanılan sorgu ve widget önbelleğini temizler.</p>
                    <button class="btn btn-outline-light mt-auto" data-tool="clear-cache"><i class="bi bi-broom me-1"></i>Temizle</button>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card admin-card h-100">
                  <div class="card-body d-flex flex-column gap-2">
                    <h3 class="h5">Medya Yetimlerini Sil</h3>
                    <p class="text-muted small mb-0">Veritabanında kullanılmayan medya dosyalarını tarar.</p>
                    <button class="btn btn-outline-light mt-auto" data-tool="cleanup-media"><i class="bi bi-x-circle me-1"></i>Tarayı Çalıştır</button>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="card admin-card h-100">
                  <div class="card-body d-flex flex-column gap-2">
                    <h3 class="h5">Analitik Senkronizasyonu</h3>
                    <p class="text-muted small mb-0">Harici analitik servislerinden yeni verileri çeker.</p>
                    <button class="btn btn-outline-light mt-auto" data-tool="sync-analytics"><i class="bi bi-graph-up-arrow me-1"></i>Senkronize Et</button>
                  </div>
                </div>
              </div>
            </div>
          </section>
        </main>
      </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin.js"></script>
  </body>
</html>
