<?php
require_once __DIR__ . '/bootstrap.php';

$pageTitle = 'Market Yönetimi';
$pageSubtitle = 'Topluluğa sunulan Ki tekliflerini oluşturun ve düzenleyin.';
$headerActions = [];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Market Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/admin.css">
  </head>
  <body class="admin-body text-light" data-admin-page="market">
    <div class="admin-shell">
      <?php require __DIR__ . '/partials/sidebar.php'; ?>
      <div class="admin-content">
        <?php require __DIR__ . '/partials/header.php'; ?>
        <main class="admin-main">
          <section class="admin-section is-active">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Yeni Teklif</span>
                <h2>Market Ürünü Oluştur</h2>
                <p class="text-muted mb-0">Ki paketleri oluşturarak kullanıcılarınıza avantaj sağlayın.</p>
              </div>
            </div>
            <form id="market-form" class="card glass-card">
              <div class="card-body row g-4">
                <div class="col-lg-6">
                  <label class="form-label">Teklif Başlığı</label>
                  <input type="text" class="form-control" name="title" placeholder="Örn. Haftalık Ki Paketi" required>
                </div>
                <div class="col-lg-3">
                  <label class="form-label">Ki Miktarı</label>
                  <input type="number" class="form-control" name="ki_amount" min="1" value="100" required>
                </div>
                <div class="col-lg-3">
                  <label class="form-label">Fiyat</label>
                  <div class="input-group">
                    <input type="number" class="form-control" name="price" min="0" step="0.01" value="9.99" required>
                    <span class="input-group-text">TRY</span>
                  </div>
                </div>
                <div class="col-lg-3">
                  <label class="form-label">Para Birimi</label>
                  <input type="text" class="form-control" name="currency" value="TRY" maxlength="8">
                </div>
                <div class="col-lg-3">
                  <label class="form-label">Durum</label>
                  <select class="form-select" name="is_active">
                    <option value="1" selected>Yayında</option>
                    <option value="0">Taslak</option>
                  </select>
                </div>
                <div id="market-form-message" class="col-12"></div>
              </div>
              <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-primary" type="submit"><i class="bi bi-bag-plus me-1"></i>Teklifi Kaydet</button>
              </div>
            </form>
          </section>

          <section class="admin-section">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Tüm Teklifler</span>
                <h2>Market Listesi</h2>
                <p class="text-muted mb-0">Mevcut paketleri düzenleyin, etkinliğini değiştirin veya kaldırın.</p>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-dark table-hover align-middle" id="market-table">
                <thead>
                  <tr>
                    <th scope="col">Başlık</th>
                    <th scope="col">Ki</th>
                    <th scope="col">Fiyat</th>
                    <th scope="col">Durum</th>
                    <th scope="col">Güncelleme</th>
                    <th scope="col" class="text-end">İşlem</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
            <div id="market-table-empty" class="empty-state d-none">Henüz market teklifi oluşturulmadı.</div>
          </section>
        </main>
      </div>
    </div>

    <div class="modal fade" id="market-edit-modal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content bg-dark text-light">
          <form id="market-edit-form">
            <div class="modal-header border-secondary">
              <h5 class="modal-title">Market Teklifini Düzenle</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body row g-3">
              <input type="hidden" name="id" id="edit-offer-id">
              <div class="col-12">
                <label class="form-label">Başlık</label>
                <input type="text" class="form-control" name="title" id="edit-offer-title" required>
              </div>
              <div class="col-6">
                <label class="form-label">Ki Miktarı</label>
                <input type="number" class="form-control" name="ki_amount" id="edit-offer-ki" min="1" required>
              </div>
              <div class="col-6">
                <label class="form-label">Fiyat</label>
                <input type="number" class="form-control" name="price" id="edit-offer-price" min="0" step="0.01" required>
              </div>
              <div class="col-6">
                <label class="form-label">Para Birimi</label>
                <input type="text" class="form-control" name="currency" id="edit-offer-currency" maxlength="8">
              </div>
              <div class="col-6">
                <label class="form-label">Durum</label>
                <select class="form-select" name="is_active" id="edit-offer-status">
                  <option value="1">Yayında</option>
                  <option value="0">Taslak</option>
                </select>
              </div>
              <div id="market-edit-message" class="col-12"></div>
            </div>
            <div class="modal-footer border-secondary d-flex justify-content-between">
              <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Kapat</button>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-danger" id="delete-market-offer"><i class="bi bi-trash me-1"></i>Sil</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Güncelle</button>
              </div>
            </div>
          </form>
        </div>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin.js"></script>
  </body>
</html>
