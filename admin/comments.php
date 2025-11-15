<?php
require_once __DIR__ . '/bootstrap.php';

$pageTitle = 'Yorum Yönetimi';
$pageSubtitle = 'Ziyaretçi yorumlarını onaylayın, çöp kutusuna gönderin veya geri alın.';
$headerActions = [
    ['href' => '#', 'label' => 'Çöp Kutusunu Temizle', 'class' => 'btn-outline-danger btn-sm', 'icon' => 'bi bi-trash3', 'attributes' => 'id="purge-comments"'],
];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Yorum Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/admin.css">
  </head>
  <body class="admin-body text-light" data-admin-page="comments">
    <div class="admin-shell">
      <?php require __DIR__ . '/partials/sidebar.php'; ?>
      <div class="admin-content">
        <?php require __DIR__ . '/partials/header.php'; ?>
        <main class="admin-main">
          <section class="admin-section is-active">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Moderasyon</span>
                <h2>Yorum Listesi</h2>
                <p class="text-muted mb-0">Aktif ve çöp kutusundaki yorumları filtreleyin, gerekirse geri yükleyin.</p>
              </div>
            </div>
            <div class="card glass-card mb-3">
              <div class="card-body row g-3 align-items-end">
                <div class="col-md-3">
                  <label class="form-label">Durum</label>
                  <select class="form-select" id="comment-status-filter">
                    <option value="active">Yayında</option>
                    <option value="trash">Çöp Kutusu</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Arama</label>
                  <input type="search" class="form-control" id="comment-search" placeholder="Kullanıcı, e-posta veya içerik">
                </div>
                <div class="col-md-3 d-grid">
                  <button type="button" class="btn btn-outline-light" id="comment-refresh"><i class="bi bi-arrow-repeat me-1"></i>Yenile</button>
                </div>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-dark table-hover align-middle" id="comment-table">
                <thead>
                  <tr>
                    <th>Yorum</th>
                    <th>Kullanıcı</th>
                    <th>Manga</th>
                    <th>Tarih</th>
                    <th class="text-end">İşlemler</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
            <div id="comment-empty" class="empty-state d-none">Gösterilecek yorum bulunamadı.</div>
            <div id="comment-message" class="mt-3"></div>
          </section>
        </main>
      </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin.js"></script>
  </body>
</html>
