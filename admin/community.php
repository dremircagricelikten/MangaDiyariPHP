<?php
require_once __DIR__ . '/bootstrap.php';

$pageTitle = 'Topluluk Yönetimi';
$pageSubtitle = 'Üyeleri yönetin, reklam alanlarını yapılandırın ve topluluğu canlı tutun.';
$headerActions = [
    ['href' => 'integrations.php', 'label' => 'Entegrasyon Ayarları', 'class' => 'btn-outline-light btn-sm', 'icon' => 'bi bi-plug'],
];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Topluluk Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/admin.css">
  </head>
  <body class="admin-body text-light" data-admin-page="community">
    <div class="admin-shell">
      <?php require __DIR__ . '/partials/sidebar.php'; ?>
      <div class="admin-content">
        <?php require __DIR__ . '/partials/header.php'; ?>
        <main class="admin-main">
          <section class="admin-section is-active">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Yeni Üye</span>
                <h2>Üye Oluştur</h2>
                <p class="text-muted mb-0">Topluluğa yeni bir üye ekleyin veya editör/yönetici yetkisi tanımlayın.</p>
              </div>
            </div>
            <form id="create-user-form" class="card glass-card">
              <div class="card-body row g-4">
                <div class="col-md-4">
                  <label class="form-label">Kullanıcı Adı</label>
                  <input type="text" class="form-control" name="username" required placeholder="mangasever">
                </div>
                <div class="col-md-4">
                  <label class="form-label">E-posta</label>
                  <input type="email" class="form-control" name="email" required placeholder="kullanici@example.com">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Geçici Parola</label>
                  <input type="text" class="form-control" name="password" required placeholder="Geçici parola">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Rol</label>
                  <select class="form-select" name="role">
                    <option value="member">Üye</option>
                    <option value="editor">Editör</option>
                    <option value="admin">Yönetici</option>
                  </select>
                </div>
                <div class="col-md-4 d-flex align-items-center">
                  <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" name="is_active" id="new-user-active" checked>
                    <label class="form-check-label" for="new-user-active">Hemen aktifleştir</label>
                  </div>
                </div>
                <div class="col-12" id="create-user-message"></div>
              </div>
              <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-primary" type="submit"><i class="bi bi-person-plus me-1"></i>Üyeyi Oluştur</button>
              </div>
            </form>
          </section>

          <section class="admin-section">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Üye Listesi</span>
                <h2>Aktif Topluluk</h2>
                <p class="text-muted mb-0">Üyelerin rol, durum ve parolalarını hızla güncelleyin.</p>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-dark table-hover align-middle" id="user-table">
                <thead>
                  <tr>
                    <th scope="col">Üye</th>
                    <th scope="col">Rol</th>
                    <th scope="col" class="text-center">Durum</th>
                    <th scope="col">Parola</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </section>

          <section class="admin-section" id="role-manager">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Rol Yapısı</span>
                <h2>Rol Yöneticisi</h2>
                <p class="text-muted mb-0">WordPress'teki gibi yeni roller oluşturun, yetkileri düzenleyin.</p>
              </div>
            </div>
            <div class="row g-4">
              <div class="col-lg-4">
                <div class="card glass-card h-100">
                  <div class="card-body">
                    <form id="role-form" class="vstack gap-3">
                      <input type="hidden" id="role-current-key" value="">
                      <div>
                        <label class="form-label">Rol Anahtarı</label>
                        <input type="text" class="form-control" id="role-key" placeholder="moderator" required>
                        <div class="form-text">Küçük harf, sayı, tire veya alt çizgi kullanın.</div>
                      </div>
                      <div>
                        <label class="form-label">Gösterilen Ad</label>
                        <input type="text" class="form-control" id="role-label" placeholder="Moderasyon Ekibi" required>
                      </div>
                      <div>
                        <label class="form-label">Yetkiler</label>
                        <input type="text" class="form-control" id="role-capabilities" placeholder="manage_content, manage_comments">
                        <div class="form-text">Virgülle ayırın. Örn: manage_content, manage_comments</div>
                      </div>
                      <div>
                        <label class="form-label">Sıra</label>
                        <input type="number" class="form-control" id="role-order" value="0">
                      </div>
                      <div class="d-flex gap-2 align-items-center">
                        <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-save me-1"></i>Rolü Kaydet</button>
                        <button class="btn btn-link btn-sm text-decoration-none text-muted" type="button" id="role-reset">Sıfırla</button>
                      </div>
                      <div id="role-form-message" class="small"></div>
                    </form>
                  </div>
                </div>
              </div>
              <div class="col-lg-8">
                <div class="card glass-card h-100">
                  <div class="card-body">
                    <h3 class="h5 mb-3">Kayıtlı Roller</h3>
                    <div class="table-responsive">
                      <table class="table table-dark table-hover align-middle" id="role-table">
                        <thead>
                          <tr>
                            <th>Rol</th>
                            <th>Anahtar</th>
                            <th>Yetkiler</th>
                            <th class="text-end">İşlemler</th>
                          </tr>
                        </thead>
                        <tbody></tbody>
                      </table>
                    </div>
                    <div id="role-table-empty" class="empty-state d-none">Henüz özel rol tanımlanmadı.</div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <section class="admin-section">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Reklam Alanları</span>
                <h2>Reklam Yönetimi</h2>
                <p class="text-muted mb-0">Üst, yan ve alt bilgi reklam kodlarını düzenleyin.</p>
              </div>
            </div>
            <form id="ad-form" class="card glass-card">
              <div class="card-body row g-4">
                <div class="col-lg-4">
                  <label class="form-label">Üst Alan</label>
                  <textarea class="form-control" name="ad_header" rows="6" placeholder="Üst reklam kodu"></textarea>
                  <small class="text-muted">Anasayfanın üst kısmında görüntülenir.</small>
                </div>
                <div class="col-lg-4">
                  <label class="form-label">Yan Panel</label>
                  <textarea class="form-control" name="ad_sidebar" rows="6" placeholder="Yan panel reklam kodu"></textarea>
                  <small class="text-muted">Anasayfa ve bölüm sayfasında yan panelde gösterilir.</small>
                </div>
                <div class="col-lg-4">
                  <label class="form-label">Alt Bilgi</label>
                  <textarea class="form-control" name="ad_footer" rows="6" placeholder="Footer reklam kodu"></textarea>
                  <small class="text-muted">Site footer bölgesinde yer alır.</small>
                </div>
                <div class="col-12" id="ad-form-message"></div>
              </div>
              <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-outline-light" type="submit"><i class="bi bi-save me-1"></i>Reklamları Kaydet</button>
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
