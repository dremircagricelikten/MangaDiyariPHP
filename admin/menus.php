<?php
require_once __DIR__ . '/bootstrap.php';

$pageTitle = 'Menü Yönetimi';
$pageSubtitle = 'Navigasyon menülerini oluşturun ve düzenleyin.';
$headerActions = [
    ['href' => 'pages.php', 'label' => 'Sayfa Yönetimi', 'class' => 'btn-outline-light btn-sm', 'icon' => 'bi bi-file-earmark-text']
];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Menü Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/admin.css">
  </head>
  <body class="admin-body text-light" data-admin-page="menus">
    <div class="admin-shell">
      <?php require __DIR__ . '/partials/sidebar.php'; ?>
      <div class="admin-content">
        <?php require __DIR__ . '/partials/header.php'; ?>
        <main class="admin-main">
          <section class="admin-section is-active">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Navigasyon</span>
                <h2>Menü Yönetimi</h2>
                <p class="text-muted mb-0">Birden fazla menü alanı oluşturun, bağlantıları sürükleyip sıralayın.</p>
              </div>
              <button class="btn btn-outline-light btn-sm" id="create-menu-btn"><i class="bi bi-plus-circle me-1"></i> Yeni Menü Alanı</button>
            </div>
            <div class="row g-4">
              <div class="col-lg-4">
                <div class="list-group" id="menu-list" role="tablist"></div>
              </div>
              <div class="col-lg-8">
                <div class="card admin-card h-100">
                  <div class="card-body" id="menu-editor">
                    <div class="text-secondary small">Bir menü seçin veya yeni bir menü oluşturun.</div>
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
