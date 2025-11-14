<?php
require_once __DIR__ . '/bootstrap.php';

$pageTitle = 'Sayfa Yönetimi';
$pageSubtitle = 'Statik içerikleri düzenleyin, yeni sayfalar oluşturun.';
$headerActions = [
    ['href' => 'menus.php', 'label' => 'Menü Yönetimi', 'class' => 'btn-outline-light btn-sm', 'icon' => 'bi bi-menu-button']
];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Sayfa Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/admin.css">
  </head>
  <body class="admin-body text-light" data-admin-page="pages">
    <div class="admin-shell">
      <?php require __DIR__ . '/partials/sidebar.php'; ?>
      <div class="admin-content">
        <?php require __DIR__ . '/partials/header.php'; ?>
        <main class="admin-main">
          <section class="admin-section is-active">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Statik İçerik</span>
                <h2>Sayfalar</h2>
                <p class="text-muted mb-0">WordPress benzeri bir düzenle yeni sayfalar oluşturun veya mevcut sayfaları güncelleyin.</p>
              </div>
            </div>
            <div class="row g-4">
              <div class="col-xl-5">
                <div class="card admin-card h-100">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h3 class="card-title h5 mb-0">Sayfa Detayı</h3>
                      <button type="button" class="btn btn-outline-light btn-sm d-none" id="page-cancel-edit"><i class="bi bi-x-lg me-1"></i>İptal</button>
                    </div>
                    <form id="page-form" class="vstack gap-3">
                      <input type="hidden" name="id" id="page-id">
                      <div>
                        <label class="form-label">Başlık</label>
                        <input type="text" class="form-control" name="title" id="page-title" required>
                      </div>
                      <div>
                        <label class="form-label">Bağlantı (slug)</label>
                        <input type="text" class="form-control" name="slug" id="page-slug" placeholder="ornek-sayfa">
                        <div class="form-text">Sadece küçük harf ve tire kullanın. Boş bırakılırsa otomatik oluşturulur.</div>
                      </div>
                      <div>
                        <label class="form-label">Durum</label>
                        <select class="form-select" name="status" id="page-status">
                          <option value="draft">Taslak</option>
                          <option value="published">Yayında</option>
                        </select>
                      </div>
                      <div>
                        <label class="form-label">İçerik</label>
                        <textarea class="form-control" rows="8" name="content" id="page-content" placeholder="Sayfa içeriğini buraya girin..." required></textarea>
                      </div>
                      <div class="d-flex justify-content-between align-items-center">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i>Sayfayı Kaydet</button>
                        <div class="small text-muted" id="page-form-hint">Yeni sayfalar yayınlandığında menülere ekleyebilirsiniz.</div>
                      </div>
                      <div id="page-form-message"></div>
                    </form>
                  </div>
                </div>
              </div>
              <div class="col-xl-7">
                <div class="card admin-card h-100">
                  <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                      <div>
                        <h3 class="card-title h5 mb-0">Kayıtlı Sayfalar</h3>
                        <p class="text-muted small mb-0">Duruma göre filtreleyebilir veya sayfaları arayabilirsiniz.</p>
                      </div>
                      <div class="d-flex flex-wrap gap-2">
                        <select id="page-status-filter" class="form-select form-select-sm">
                          <option value="">Tümü</option>
                          <option value="published">Yayında</option>
                          <option value="draft">Taslak</option>
                        </select>
                        <div class="input-group input-group-sm">
                          <span class="input-group-text"><i class="bi bi-search"></i></span>
                          <input type="search" id="page-search" class="form-control" placeholder="Sayfa ara">
                        </div>
                      </div>
                    </div>
                    <div class="table-responsive">
                      <table class="table table-dark table-hover align-middle" id="page-table">
                        <thead>
                          <tr>
                            <th>Başlık</th>
                            <th>Durum</th>
                            <th>Bağlantı</th>
                            <th class="text-end">İşlemler</th>
                          </tr>
                        </thead>
                        <tbody></tbody>
                      </table>
                    </div>
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
