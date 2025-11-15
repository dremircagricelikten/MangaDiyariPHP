<?php
require_once __DIR__ . '/bootstrap.php';

$pageTitle = 'Site Ayarları';
$pageSubtitle = 'Genel marka kimliği, depolama tercihleri ve iletişim ayarlarını yönetin.';
$headerActions = [];
$allSettings = $settingRepo->all();
$defaultStorage = $allSettings['chapter_storage_driver'] ?? 'local';
$hasFtpPassword = !empty($allSettings['ftp_password'] ?? '');
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
                <div class="col-md-6">
                  <label class="form-label">Site Ana URL</label>
                  <input type="url" class="form-control" name="site_base_url" value="<?= htmlspecialchars($allSettings['site_base_url'] ?? '') ?>" placeholder="https://example.com">
                  <small class="text-muted">E-posta bildirimleri için kullanılacak tam adres.</small>
                </div>
                <div id="site-settings-message" class="col-12"></div>
              </div>
              <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Ayarları Kaydet</button>
              </div>
            </form>
          </section>

          <section class="admin-section" id="storage-settings">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Depolama</span>
                <h2>FTP Yapılandırması</h2>
                <p class="text-muted mb-0">Bölüm dosyalarını harici bir FTP sunucusuna göndermek için bağlantı bilgilerini tanımlayın.</p>
              </div>
            </div>
            <form id="storage-settings-form" class="card glass-card">
              <div class="card-body row g-4 align-items-end">
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
                  <input type="password" class="form-control" name="ftp_password" placeholder="Yeni şifre">
                  <small class="text-muted d-block mt-2">Yeni bir parola girmediğiniz sürece mevcut parola korunur.</small>
                  <?php if ($hasFtpPassword): ?>
                    <div class="form-check mt-2">
                      <input class="form-check-input" type="checkbox" name="ftp_password_clear" id="ftp-password-clear" value="1">
                      <label class="form-check-label" for="ftp-password-clear">Kayıtlı parolayı temizle</label>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="col-md-12">
                  <label class="form-label">Uzaktan Klasör</label>
                  <input type="text" class="form-control" name="ftp_root" value="<?= htmlspecialchars($allSettings['ftp_root'] ?? '/public_html/chapters') ?>" placeholder="/path/to/chapters">
                  <small class="text-muted">Bölümler bu dizin altında <code>chapter_id</code> klasörlerinde saklanır.</small>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Dosya URL Temeli</label>
                  <input type="url" class="form-control" name="ftp_base_url" value="<?= htmlspecialchars($allSettings['ftp_base_url'] ?? '') ?>" placeholder="https://cdn.ornek.com/chapters">
                  <small class="text-muted">Okuyuculara sunulacak kök adres. Sonunda <code>/</code> bulunmasına gerek yoktur.</small>
                </div>
                <div class="col-md-6">
                  <div class="alert alert-info small mb-0">
                    <i class="bi bi-info-circle me-1"></i> Parolayı değiştirirken yeni değer girin. Boş bırakırsanız mevcut parola korunur.
                  </div>
                </div>
                <div id="storage-settings-message" class="col-12"></div>
              </div>
              <div class="card-footer d-flex flex-wrap gap-3 justify-content-between align-items-center">
                <button class="btn btn-outline-light" type="button" id="storage-test-ftp">
                  <span class="button-label"><i class="bi bi-plug"></i> Bağlantıyı Test Et</span>
                  <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
                <button class="btn btn-primary" type="submit"><i class="bi bi-hdd-network me-1"></i>FTP Ayarlarını Kaydet</button>
              </div>
            </form>
          </section>

          <section class="admin-section">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Bildirim</span>
                <h2>SMTP Ayarları</h2>
                <p class="text-muted mb-0">Yeni bölüm bildirimlerini göndermek için SMTP sunucunuzu tanımlayın.</p>
              </div>
            </div>
            <?php $smtpEnabled = ($allSettings['smtp_enabled'] ?? '0') === '1'; ?>
            <form id="smtp-settings-form" class="card glass-card">
              <div class="card-body row g-4">
                <div class="col-12">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="smtp-enabled" name="smtp_enabled" value="1" <?= $smtpEnabled ? 'checked' : '' ?>>
                    <label class="form-check-label" for="smtp-enabled">SMTP üzerinden e-posta gönderimini etkinleştir</label>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">SMTP Sunucusu</label>
                  <input type="text" class="form-control" name="smtp_host" value="<?= htmlspecialchars($allSettings['smtp_host'] ?? '') ?>" placeholder="smtp.example.com">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Port</label>
                  <input type="number" class="form-control" name="smtp_port" min="1" value="<?= htmlspecialchars($allSettings['smtp_port'] ?? '587') ?>">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Şifreleme</label>
                  <?php $smtpEncryption = $allSettings['smtp_encryption'] ?? ''; ?>
                  <select class="form-select" name="smtp_encryption">
                    <option value="" <?= $smtpEncryption === '' ? 'selected' : '' ?>>Yok</option>
                    <option value="ssl" <?= $smtpEncryption === 'ssl' ? 'selected' : '' ?>>SSL</option>
                    <option value="tls" <?= $smtpEncryption === 'tls' ? 'selected' : '' ?>>TLS</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Kullanıcı Adı</label>
                  <input type="text" class="form-control" name="smtp_username" value="<?= htmlspecialchars($allSettings['smtp_username'] ?? '') ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Parola</label>
                  <input type="password" class="form-control" name="smtp_password" id="smtp-password" placeholder="Değiştirmek için yeni parola girin">
                  <?php if (!empty($allSettings['smtp_password'])): ?>
                    <small class="text-muted">Mevcut parola korunur. Temizlemek için aşağıdaki kutuyu işaretleyin.</small>
                  <?php else: ?>
                    <small class="text-muted">Parola girmediğinizde alan boş bırakılabilir.</small>
                  <?php endif; ?>
                  <div class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" value="1" id="smtp-password-clear" name="smtp_password_clear">
                    <label class="form-check-label" for="smtp-password-clear">Kayıtlı parolayı temizle</label>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Gönderici E-posta</label>
                  <input type="email" class="form-control" name="smtp_from_email" value="<?= htmlspecialchars($allSettings['smtp_from_email'] ?? '') ?>" placeholder="no-reply@example.com">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Gönderici Adı</label>
                  <input type="text" class="form-control" name="smtp_from_name" value="<?= htmlspecialchars($allSettings['smtp_from_name'] ?? $site['name']) ?>" placeholder="<?= htmlspecialchars($site['name']) ?>">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Yanıt Adresi</label>
                  <input type="email" class="form-control" name="smtp_reply_to" value="<?= htmlspecialchars($allSettings['smtp_reply_to'] ?? '') ?>" placeholder="opsiyonel">
                </div>
                <div id="smtp-settings-message" class="col-12"></div>
              </div>
              <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-primary" type="submit"><i class="bi bi-envelope-check me-1"></i>SMTP Ayarlarını Kaydet</button>
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
