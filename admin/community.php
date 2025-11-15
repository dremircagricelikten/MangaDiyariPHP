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
            <div class="card glass-card mb-4" id="user-insights">
              <div class="card-body">
                <div class="row g-3 align-items-end">
                  <div class="col-md-3">
                    <label class="form-label">Rol Filtresi</label>
                    <select class="form-select form-select-sm" id="user-role-filter">
                      <option value="">Tümü</option>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Durum</label>
                    <select class="form-select form-select-sm" id="user-status-filter">
                      <option value="">Tümü</option>
                      <option value="active">Aktif</option>
                      <option value="inactive">Pasif</option>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Sıralama</label>
                    <select class="form-select form-select-sm" id="user-sort">
                      <option value="newest">En Yeni Üyeler</option>
                      <option value="oldest">En Eski Üyeler</option>
                      <option value="name">Alfabetik</option>
                      <option value="ki">Ki Bakiyesi</option>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Ara</label>
                    <input type="search" class="form-control form-control-sm" id="user-search" placeholder="İsim veya e-posta ile ara">
                  </div>
                </div>
                <div class="user-stats mt-4" id="user-summary">
                  <div class="user-stat" data-summary="total">
                    <span class="user-stat__label">Toplam Üye</span>
                    <span class="user-stat__value">0</span>
                  </div>
                  <div class="user-stat" data-summary="active">
                    <span class="user-stat__label">Aktif</span>
                    <span class="user-stat__value">0</span>
                  </div>
                  <div class="user-stat" data-summary="staff">
                    <span class="user-stat__label">Ekip Üyeleri</span>
                    <span class="user-stat__value">0</span>
                  </div>
                  <div class="user-stat" data-summary="filtered">
                    <span class="user-stat__label">Filtre Sonucu</span>
                    <span class="user-stat__value">0</span>
                  </div>
                </div>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-dark table-hover align-middle" id="user-table">
                <thead>
                  <tr>
                    <th scope="col">Üye</th>
                    <th scope="col">Rol / Yetkiler</th>
                    <th scope="col" class="text-center">Durum</th>
                    <th scope="col">Hızlı İşlemler</th>
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
                    <hr class="text-secondary my-4">
                    <div class="capability-guide">
                      <h4 class="h6 text-uppercase text-muted mb-3">Yaygın Yetki Açıklamaları</h4>
                      <ul class="capability-guide__list">
                        <li><span class="badge badge-capability">manage_site</span> Site ayarları ve temayı yönetebilir.</li>
                        <li><span class="badge badge-capability">manage_users</span> Üyeleri düzenleyebilir, yeni roller atayabilir.</li>
                        <li><span class="badge badge-capability">manage_content</span> Manga, bölüm ve gönderileri oluşturup yayınlayabilir.</li>
                        <li><span class="badge badge-capability">manage_comments</span> Yorumları onaylayabilir, silebilir veya geri yükleyebilir.</li>
                        <li><span class="badge badge-capability">manage_media</span> Medya kitaplığındaki dosyaları yükleyebilir veya silebilir.</li>
                        <li><span class="badge badge-capability">manage_market</span> Ki pazarı ve siparişleri yönetebilir.</li>
                        <li><span class="badge badge-capability">manage_integrations</span> Entegrasyonlar ve API anahtarlarını yapılandırabilir.</li>
                        <li><span class="badge badge-capability">read</span> Sadece okuma ve topluluk erişimi sağlar.</li>
                      </ul>
                    </div>
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
    <div class="offcanvas offcanvas-end admin-offcanvas" tabindex="-1" id="user-detail-drawer" aria-labelledby="user-detail-title">
      <div class="offcanvas-header">
        <div>
          <h5 class="offcanvas-title" id="user-detail-title">Üye Detayları</h5>
          <p class="offcanvas-subtitle text-muted mb-0">Profil, iletişim ve topluluk bilgilerini düzenleyin.</p>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Kapat"></button>
      </div>
      <div class="offcanvas-body">
        <form id="user-detail-form" class="vstack gap-4">
          <input type="hidden" name="id" id="user-detail-id">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="user-detail-username">Kullanıcı Adı</label>
              <input type="text" class="form-control" name="username" id="user-detail-username" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="user-detail-email">E-posta</label>
              <input type="email" class="form-control" name="email" id="user-detail-email" required>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="user-detail-avatar">Avatar URL</label>
              <input type="url" class="form-control" name="avatar_url" id="user-detail-avatar" placeholder="https://...">
              <div class="form-text">Boş bırakılırsa mevcut avatar korunur.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label" for="user-detail-website">Web Sitesi</label>
              <input type="url" class="form-control" name="website_url" id="user-detail-website" placeholder="https://...">
            </div>
            <div class="col-md-4">
              <label class="form-label" for="user-detail-ki">Ki Bakiyesi</label>
              <input type="number" class="form-control" name="ki_balance" id="user-detail-ki" min="0" step="1">
            </div>
            <div class="col-12">
              <label class="form-label" for="user-detail-bio">Kısa Biyografi</label>
              <textarea class="form-control" name="bio" id="user-detail-bio" rows="4" placeholder="Üyenin biyografisi"></textarea>
            </div>
          </div>
          <div id="user-detail-meta" class="user-detail-meta small text-muted"></div>
          <div id="user-detail-message"></div>
          <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div class="small text-muted">Kaydedilen değişiklikler topluluk listesine anında yansır.</div>
            <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Değişiklikleri Kaydet</button>
          </div>
        </form>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin.js"></script>
  </body>
</html>
