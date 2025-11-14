<?php
require_once __DIR__ . '/bootstrap.php';

$pageTitle = 'Yönetim Paneli';
$pageSubtitle = 'Manga Diyarı sitenizin performansını tek bakışta izleyin.';
$headerActions = [
    ['href' => 'manga.php', 'label' => 'Yeni Manga', 'class' => 'btn-primary btn-sm', 'icon' => 'bi bi-plus-circle'],
    ['href' => 'chapters.php', 'label' => 'Yeni Bölüm', 'class' => 'btn-outline-light btn-sm', 'icon' => 'bi bi-upload'],
];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Yönetim Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/admin.css">
  </head>
  <body class="admin-body text-light" data-admin-page="dashboard">
    <div class="admin-shell">
      <?php require __DIR__ . '/partials/sidebar.php'; ?>
      <div class="admin-content">
        <?php require __DIR__ . '/partials/header.php'; ?>
        <main class="admin-main">
          <section class="admin-section is-active">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Özet</span>
                <h2>Hızlı Bakış</h2>
                <p class="text-muted mb-0">Paneldeki öne çıkan göstergeleri takip edin.</p>
              </div>
            </div>
            <div class="dashboard-grid">
              <div class="dashboard-card gradient-purple">
                <div class="dashboard-card-icon"><i class="bi bi-journal-bookmark"></i></div>
                <div class="dashboard-card-value" data-dashboard-stat="manga_total"><?= number_format($dashboardStats['manga_total']) ?></div>
                <div class="dashboard-card-label">Toplam Manga</div>
                <div class="dashboard-card-meta">Yeni seri eklemek için Manga Yönetimi sayfasını ziyaret edin.</div>
              </div>
              <div class="dashboard-card gradient-blue">
                <div class="dashboard-card-icon"><i class="bi bi-collection-play"></i></div>
                <div class="dashboard-card-value" data-dashboard-stat="chapter_total"><?= number_format($dashboardStats['chapter_total']) ?></div>
                <div class="dashboard-card-label">Toplam Bölüm</div>
                <div class="dashboard-card-meta">Bölümleri toplu olarak yüklemek için Bölüm Yönetimi sayfasını kullanın.</div>
              </div>
              <div class="dashboard-card gradient-amber">
                <div class="dashboard-card-icon"><i class="bi bi-gem"></i></div>
                <div class="dashboard-card-value" data-dashboard-stat="chapter_premium"><?= number_format($dashboardStats['chapter_premium']) ?></div>
                <div class="dashboard-card-label">Premium Bölümler</div>
                <div class="dashboard-card-meta">Premium içeriklerinizin takibini buradan yapabilirsiniz.</div>
              </div>
              <div class="dashboard-card gradient-green">
                <div class="dashboard-card-icon"><i class="bi bi-people"></i></div>
                <div class="dashboard-card-value" data-dashboard-stat="active_members"><?= number_format($dashboardStats['active_members']) ?></div>
                <div class="dashboard-card-label">Aktif Üye</div>
                <div class="dashboard-card-meta">Topluluk etkileşimini Community sayfasından yönetebilirsiniz.</div>
              </div>
            </div>
            <div class="card admin-card mt-4">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                  <div>
                    <h3 class="card-title h5 mb-0">Son Yorumlar</h3>
                    <p class="text-muted small mb-0">Topluluktan gelen en yeni yorumları hızlıca kontrol edin.</p>
                  </div>
                  <button type="button" class="btn btn-outline-light btn-sm" id="refresh-comments"><i class="bi bi-arrow-clockwise me-1"></i>Yenile</button>
                </div>
                <div id="dashboard-comments" class="comment-feed"></div>
              </div>
            </div>
          </section>
        </main>
      </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      window.dashboardStats = <?= json_encode($dashboardStats, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="assets/admin.js"></script>
  </body>
</html>
