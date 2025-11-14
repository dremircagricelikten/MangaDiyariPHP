<?php
require_once __DIR__ . '/bootstrap.php';

$pageTitle = 'Görünüm Ayarları';
$pageSubtitle = 'Tema renklerini ve marka bileşenlerini düzenleyin.';
$headerActions = [];

$themeDefaults = [
    'primary_color' => '#5f2c82',
    'accent_color' => '#49a09d',
    'background_color' => '#05060c',
    'gradient_start' => '#5f2c82',
    'gradient_end' => '#49a09d',
];
$allSettings = $settingRepo->all();
$theme = array_replace($themeDefaults, array_intersect_key($allSettings, $themeDefaults));
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Görünüm</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/admin.css">
  </head>
  <body class="admin-body text-light" data-admin-page="appearance">
    <div class="admin-shell">
      <?php require __DIR__ . '/partials/sidebar.php'; ?>
      <div class="admin-content">
        <?php require __DIR__ . '/partials/header.php'; ?>
        <main class="admin-main">
          <section class="admin-section is-active">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Marka</span>
                <h2>Görünüm</h2>
                <p class="text-muted mb-0">Tema renklerini ve marka kimliğini yönetin.</p>
              </div>
            </div>
            <div class="row g-4">
              <div class="col-lg-6">
                <div class="card admin-card h-100">
                  <div class="card-body">
                    <h3 class="card-title h5">Tema Ayarları</h3>
                    <form id="theme-form" class="row g-3">
                      <div class="col-sm-6">
                        <label class="form-label" for="primary_color">Birincil Renk</label>
                        <input type="color" class="form-control form-control-color" id="primary_color" name="primary_color" value="<?= htmlspecialchars($theme['primary_color']) ?>">
                      </div>
                      <div class="col-sm-6">
                        <label class="form-label" for="accent_color">Vurgu Rengi</label>
                        <input type="color" class="form-control form-control-color" id="accent_color" name="accent_color" value="<?= htmlspecialchars($theme['accent_color']) ?>">
                      </div>
                      <div class="col-sm-6">
                        <label class="form-label" for="background_color">Arka Plan</label>
                        <input type="color" class="form-control form-control-color" id="background_color" name="background_color" value="<?= htmlspecialchars($theme['background_color']) ?>">
                      </div>
                      <div class="col-sm-6">
                        <label class="form-label" for="gradient_start">Gradyan Başlangıcı</label>
                        <input type="color" class="form-control form-control-color" id="gradient_start" name="gradient_start" value="<?= htmlspecialchars($theme['gradient_start']) ?>">
                      </div>
                      <div class="col-sm-6">
                        <label class="form-label" for="gradient_end">Gradyan Bitişi</label>
                        <input type="color" class="form-control form-control-color" id="gradient_end" name="gradient_end" value="<?= htmlspecialchars($theme['gradient_end']) ?>">
                      </div>
                      <div class="col-12">
                        <button type="submit" class="btn btn-outline-light btn-sm">Tema Ayarlarını Kaydet</button>
                        <div class="mt-3" id="theme-form-message"></div>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
              <div class="col-lg-6">
                <div class="card admin-card h-100">
                  <div class="card-body">
                    <h3 class="card-title h5">Marka Ayarları</h3>
                    <form id="branding-form" class="vstack gap-3" enctype="multipart/form-data">
                      <div>
                        <label class="form-label">Site Adı</label>
                        <input type="text" class="form-control" name="site_name" value="<?= htmlspecialchars($site['name']) ?>">
                      </div>
                      <div>
                        <label class="form-label">Slogan</label>
                        <input type="text" class="form-control" name="site_tagline" value="<?= htmlspecialchars($site['tagline']) ?>">
                      </div>
                      <div>
                        <label class="form-label">Logo</label>
                        <input type="file" class="form-control" name="site_logo" accept="image/*,image/svg+xml">
                        <span class="form-text">Şeffaf arka planlı SVG veya PNG önerilir.</span>
                        <div class="mt-3" id="branding-preview">
                          <?php if (!empty($site['logo'])): ?>
                            <img src="../public/<?= htmlspecialchars($site['logo']) ?>" alt="Logo" class="img-fluid rounded shadow-sm" style="max-height: 80px;">
                          <?php endif; ?>
                        </div>
                      </div>
                      <div>
                        <label class="form-label">Alt Bilgi Metni</label>
                        <input type="text" class="form-control" name="site_footer" value="<?= htmlspecialchars($settingRepo->get('site_footer', '')) ?>" placeholder="© <?= date('Y') ?> <?= htmlspecialchars($site['name']) ?>">
                      </div>
                      <div>
                        <label class="form-label">Varsayılan Depolama</label>
                        <select class="form-select" name="chapter_storage_driver">
                          <?php $storageDriver = $settingRepo->get('chapter_storage_driver', 'local'); ?>
                          <option value="local" <?= $storageDriver === 'local' ? 'selected' : '' ?>>Yerel Sunucu</option>
                          <option value="ftp" <?= $storageDriver === 'ftp' ? 'selected' : '' ?>>FTP Sunucu</option>
                        </select>
                      </div>
                      <button type="submit" class="btn btn-outline-light btn-sm">Marka Ayarlarını Kaydet</button>
                      <div class="mt-3" id="branding-form-message"></div>
                    </form>
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
