<?php
require_once __DIR__ . '/bootstrap.php';

$pageTitle = 'Ana Sayfa Widgetları';
$pageSubtitle = 'Vitrini ve listeleri yönetmek için bileşenleri düzenleyin.';
$headerActions = [];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Widget Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/admin.css">
  </head>
  <body class="admin-body text-light" data-admin-page="widgets">
    <div class="admin-shell">
      <?php require __DIR__ . '/partials/sidebar.php'; ?>
      <div class="admin-content">
        <?php require __DIR__ . '/partials/header.php'; ?>
        <main class="admin-main">
          <section class="admin-section is-active">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Bileşenler</span>
                <h2>Ana Sayfa Widgetları</h2>
                <p class="text-muted mb-0">Bileşenleri etkinleştirerek veya sırasını değiştirerek ana sayfayı şekillendirin.</p>
              </div>
            </div>
            <div class="card admin-card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                  <div>
                    <h3 class="card-title h5 mb-0">Widget Yönetimi</h3>
                    <span class="text-muted small">Ayarları kaydettikten sonra değişiklikler anında uygulanır.</span>
                  </div>
                  <span class="badge bg-info text-dark">Widget Sistemi</span>
                </div>
                <div id="widget-list" class="vstack gap-3"></div>
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
