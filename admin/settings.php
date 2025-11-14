<?php
require_once __DIR__ . '/bootstrap.php';

$pageTitle = 'Site Ayarları';
$pageSubtitle = 'Genel marka kimliği, depolama tercihleri ve iletişim ayarlarını yönetin.';
$headerActions = [];
$allSettings = $settingRepo->all();
$defaultStorage = $allSettings['chapter_storage_driver'] ?? 'local';
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Site Ayarları</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/admin.css">
  </head>
  <body class="admin-body text-light" data-admin-page="settings">
    <div class="admin-shell">
      <?php require __DIR__ . '/partials/sidebar.php'; ?>
      <div class="admin-content">
        <?php require __DIR__ . '/partials/header.php'; ?>
        <main class="admin-main">
          <section class="admin-section is-active">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Marka</span>
                <h2>Site Kimliği</h2>
                <p class="text-muted mb-0">Site adı, sloganı ve logo dosyasını güncelleyin.</p>
              </div>
            </div>
            <form id="site-settings-form" class="card glass-card" enctype="multipart/form-data">
              <div class="card-body row g-4">
                <div class="col-md-6">
                  <label class="form-label">Site İsmi</label>
                  <input type="text" class="form-control" name="site_name" value="<?= htmlspecialchars($allSettings['site_name'] ?? $site['name']) ?>" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Slogan</label>
                  <input type="text" class="form-control" name="site_tagline" value="<?= htmlspecialchars($allSettings['site_tagline'] ?? $site['tagline']) ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Varsayılan Depolama</label>
                  <select class="form-select" name="chapter_storage_driver">
                    <option value="local" <?= $defaultStorage === 'local' ? 'selected' : '' ?>>Yerel Sunucu</option>
                    <option value="ftp" <?= $defaultStorage === 'ftp' ? 'selected' : '' ?>>FTP Sunucu</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Logo</label>
                  <input type="file" class="form-control" name="site_logo" accept="image/*">
                  <?php if (!empty($site['logo'])): ?>
                    <small class="text-muted d-block mt-2">Geçerli logo: <code><?= htmlspecialchars($site['logo']) ?></code></small>
                  <?php endif; ?>
                </div>
                <div class="col-12">
                  <label class="form-label">Footer Metni</label>
                  <input type="text" class="form-control" name="site_footer" value="<?= htmlspecialchars($allSettings['site_footer'] ?? '') ?>" placeholder="© <?= date('Y') ?> <?= htmlspecialchars($site['name']) ?>">
                </div>
                <div id="site-settings-message" class="col-12"></div>
              </div>
              <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Ayarları Kaydet</button>
              </div>
            </form>
          </section>

          <section class="admin-section">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Depolama</span>
                <h2>FTP Yapılandırması</h2>
                <p class="text-muted mb-0">Bölüm dosyalarını harici bir FTP sunucusuna göndermek için bağlantı bilgilerini tanımlayın.</p>
              </div>
            </div>
            <form id="storage-settings-form" class="card glass-card">
              <div class="card-body row g-4">
                <div class="col-md-6">
                  <label class="form-label">FTP Host</label>
                  <input type="text" class="form-control" name="ftp_host" value="<?= htmlspecialchars($allSettings['ftp_host'] ?? '') ?>" placeholder="ftp.ornek.com">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Port</label>
                  <input type="number" class="form-control" name="ftp_port" min="1" value="<?= htmlspecialchars($allSettings['ftp_port'] ?? '21') ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Pasif Mod</label>
                  <select class="form-select" name="ftp_passive">
                    <option value="1" <?= ($allSettings['ftp_passive'] ?? '1') === '1' ? 'selected' : '' ?>>Açık</option>
                    <option value="0" <?= ($allSettings['ftp_passive'] ?? '1') === '0' ? 'selected' : '' ?>>Kapalı</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Kullanıcı Adı</label>
                  <input type="text" class="form-control" name="ftp_username" value="<?= htmlspecialchars($allSettings['ftp_username'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Şifre</label>
                  <input type="password" class="form-control" name="ftp_password" value="<?= htmlspecialchars($allSettings['ftp_password'] ?? '') ?>">
                </div>
                <div class="col-md-12">
                  <label class="form-label">Uzaktan Klasör</label>
                  <input type="text" class="form-control" name="ftp_root" value="<?= htmlspecialchars($allSettings['ftp_root'] ?? '/public_html/chapters') ?>" placeholder="/path/to/chapters">
                  <small class="text-muted">Bölümler bu dizin altında <code>chapter_id</code> klasörlerinde saklanır.</small>
                </div>
                <div id="storage-settings-message" class="col-12"></div>
              </div>
              <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-primary" type="submit"><i class="bi bi-hdd-network me-1"></i>FTP Ayarlarını Kaydet</button>
              </div>
            </form>
          </section>
        </main>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin.js"></script>
  </body>
</html>
