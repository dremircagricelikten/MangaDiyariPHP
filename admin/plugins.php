<?php
require_once __DIR__ . '/bootstrap.php';

$pageTitle = 'Eklenti Yöneticisi';
$pageSubtitle = 'Özelleştirilmiş eklentileri etkinleştirerek paneli zenginleştirin.';
$headerActions = [];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Eklenti Yöneticisi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/admin.css">
  </head>
  <body class="admin-body text-light" data-admin-page="plugins">
    <div class="admin-shell">
      <?php require __DIR__ . '/partials/sidebar.php'; ?>
      <div class="admin-content">
        <?php require __DIR__ . '/partials/header.php'; ?>
        <main class="admin-main">
          <section class="admin-section is-active">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">WordPress Deneyimi</span>
                <h2>Eklentiler</h2>
                <p class="text-muted mb-0">SEO, önbellek ve analitik gibi hazır eklentileri tek tıkla yönetebilirsiniz.</p>
              </div>
            </div>
            <div id="plugin-message" class="mb-3"></div>
            <div id="plugin-list" class="row g-4"></div>
          </section>
        </main>
      </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin.js"></script>
  </body>
</html>
