<?php
require_once __DIR__ . '/bootstrap.php';

$pageTitle = 'Entegrasyonlar';
$pageSubtitle = 'Analitik servisleri, Ki ekonomi parametreleri ve ek servis bağlantılarını yönetin.';
$headerActions = [
    ['href' => 'community.php', 'label' => 'Topluluk Yönetimi', 'class' => 'btn-outline-light btn-sm', 'icon' => 'bi bi-people'],
];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Entegrasyonlar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/admin.css">
  </head>
  <body class="admin-body text-light" data-admin-page="integrations">
    <div class="admin-shell">
      <?php require __DIR__ . '/partials/sidebar.php'; ?>
      <div class="admin-content">
        <?php require __DIR__ . '/partials/header.php'; ?>
        <main class="admin-main">
          <section class="admin-section is-active">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Analitik</span>
                <h2>Analitik Servisleri</h2>
                <p class="text-muted mb-0">Google Analytics ve Search Console kodlarını tanımlayın.</p>
              </div>
            </div>
            <form id="analytics-form" class="card glass-card">
              <div class="card-body row g-4">
                <div class="col-12">
                  <label class="form-label">Google Analytics</label>
                  <textarea class="form-control" rows="5" name="analytics_google" placeholder="GA4 script kodu"></textarea>
                  <small class="text-muted">Script etiketi dahil şekilde yapıştırabilirsiniz.</small>
                </div>
                <div class="col-12">
                  <label class="form-label">Search Console Doğrulama</label>
                  <textarea class="form-control" rows="3" name="analytics_search_console" placeholder="Meta doğrulama kodu"></textarea>
                </div>
                <div class="col-12" id="analytics-form-message"></div>
              </div>
              <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-outline-light" type="submit"><i class="bi bi-save me-1"></i>Analitik Kodları Kaydet</button>
              </div>
            </form>
          </section>

          <section class="admin-section">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Ki Ekonomisi</span>
                <h2>Ki Ayarları</h2>
                <p class="text-muted mb-0">Ki para birimi, ödül miktarları ve premium süreleri üzerinde tam kontrol sağlayın.</p>
              </div>
            </div>
            <form id="ki-settings-form" class="card glass-card">
              <div class="card-body row g-4">
                <div class="col-md-4">
                  <label class="form-label">Para Birimi İsmi</label>
                  <input type="text" class="form-control" name="currency_name" placeholder="Ki">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Yorum Ödülü</label>
                  <input type="number" class="form-control" name="comment_reward" min="0" value="5">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Tepki Ödülü</label>
                  <input type="number" class="form-control" name="reaction_reward" min="0" value="1">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Sohbet / Dakika</label>
                  <input type="number" class="form-control" name="chat_reward_per_minute" min="0" value="1">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Okuma / Dakika</label>
                  <input type="number" class="form-control" name="read_reward_per_minute" min="0" value="2">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Varsayılan Premium Süresi (saat)</label>
                  <input type="number" class="form-control" name="unlock_default_duration" min="0" value="168">
                  <small class="text-muted">Kilidi açılan bölümlerin premium kalma süresi.</small>
                </div>
                <div class="col-md-4 d-flex align-items-center">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="market-enabled" name="market_enabled" checked>
                    <label class="form-check-label" for="market-enabled">Market sistemini etkinleştir</label>
                  </div>
                </div>
                <div class="col-12" id="ki-settings-message"></div>
              </div>
              <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-outline-light" type="submit"><i class="bi bi-coin me-1"></i>Ki Ayarlarını Kaydet</button>
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
