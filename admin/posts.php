<?php
require_once __DIR__ . '/bootstrap.php';

$pageTitle = 'Yazı Yönetimi';
$pageSubtitle = 'Blog yazılarını, kategorileri ve etiketleri tek yerden yönetin.';
$headerActions = [
    ['href' => '#new-post', 'label' => 'Yeni Yazı', 'class' => 'btn-primary btn-sm', 'icon' => 'bi bi-pencil-square'],
];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Yazı Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/admin.css">
  </head>
  <body class="admin-body text-light" data-admin-page="posts">
    <div class="admin-shell">
      <?php require __DIR__ . '/partials/sidebar.php'; ?>
      <div class="admin-content">
        <?php require __DIR__ . '/partials/header.php'; ?>
        <main class="admin-main">
          <section class="admin-section is-active" id="new-post">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Yeni İçerik</span>
                <h2>Yazı Oluştur</h2>
                <p class="text-muted mb-0">WordPress deneyimine benzer şekilde başlık, içerik ve kategorileri belirleyin.</p>
              </div>
            </div>
            <div class="row g-4">
              <div class="col-xl-7">
                <form id="post-form" class="card glass-card h-100">
                  <div class="card-body row g-4">
                    <input type="hidden" name="id" id="post-id">
                    <div class="col-12">
                      <label class="form-label">Başlık <span class="text-danger">*</span></label>
                      <input type="text" class="form-control" name="title" id="post-title" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Bağlantı (slug)</label>
                      <input type="text" class="form-control" name="slug" id="post-slug" placeholder="yeni-yazi">
                      <div class="form-text">Boş bırakılırsa otomatik oluşturulur.</div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Durum</label>
                      <select class="form-select" name="status" id="post-status">
                        <option value="draft">Taslak</option>
                        <option value="published">Yayınlandı</option>
                      </select>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Öne Çıkan Görsel URL</label>
                      <input type="url" class="form-control" name="featured_image" id="post-featured" placeholder="https://...">
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Yazar</label>
                      <select class="form-select" name="author_id" id="post-author">
                        <option value="">Varsayılan (Siz)</option>
                      </select>
                    </div>
                    <div class="col-12">
                      <label class="form-label">Kısa Özet</label>
                      <textarea class="form-control" rows="3" name="excerpt" id="post-excerpt" placeholder="Öne çıkan metin"></textarea>
                    </div>
                    <div class="col-12">
                      <label class="form-label">İçerik</label>
                      <textarea class="form-control" rows="8" name="content" id="post-content" placeholder="Yazı içeriğini girin" required></textarea>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Kategoriler</label>
                      <select class="form-select" id="post-categories" name="categories[]" multiple data-placeholder="Kategori seçin"></select>
                      <div class="form-text">CTRL (veya CMD) ile birden fazla kategori seçebilirsiniz.</div>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Etiketler</label>
                      <input type="text" class="form-control" name="tags" id="post-tags" placeholder="macera, fantastik">
                      <div class="form-text">Virgülle ayırarak yeni etiketler ekleyebilirsiniz.</div>
                    </div>
                    <div class="col-12" id="post-form-message"></div>
                  </div>
                  <div class="card-footer d-flex justify-content-between align-items-center gap-3">
                    <button type="button" class="btn btn-outline-light btn-sm" id="post-reset"><i class="bi bi-arrow-counterclockwise me-1"></i>Temizle</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>Kaydet</button>
                  </div>
                </form>
              </div>
              <div class="col-xl-5">
                <div class="card glass-card h-100">
                  <div class="card-body d-flex flex-column gap-4">
                    <div>
                      <h3 class="h5 mb-3">Kategoriler</h3>
                      <form id="category-form" class="vstack gap-3">
                        <input type="hidden" name="id" id="category-id">
                        <div>
                          <label class="form-label">Adı</label>
                          <input type="text" class="form-control" name="name" id="category-name" required>
                        </div>
                        <div>
                          <label class="form-label">Bağlantı</label>
                          <input type="text" class="form-control" name="slug" id="category-slug" placeholder="aksiyon">
                        </div>
                        <div>
                          <label class="form-label">Açıklama</label>
                          <textarea class="form-control" rows="2" name="description" id="category-description"></textarea>
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                          <button class="btn btn-outline-light btn-sm" type="submit"><i class="bi bi-save me-1"></i>Kaydet</button>
                          <button class="btn btn-link text-decoration-none text-muted btn-sm" type="button" id="category-reset">Sıfırla</button>
                        </div>
                        <div id="category-message" class="small"></div>
                      </form>
                      <hr class="border-secondary">
                      <div class="taxonomy-list" id="category-list"></div>
                    </div>
                    <div>
                      <h3 class="h5 mb-3">Etiketler</h3>
                      <form id="tag-form" class="vstack gap-3">
                        <input type="hidden" name="id" id="tag-id">
                        <div>
                          <label class="form-label">Adı</label>
                          <input type="text" class="form-control" name="name" id="tag-name" required>
                        </div>
                        <div>
                          <label class="form-label">Bağlantı</label>
                          <input type="text" class="form-control" name="slug" id="tag-slug" placeholder="efsane">
                        </div>
                        <div class="d-flex gap-2 align-items-center">
                          <button class="btn btn-outline-light btn-sm" type="submit"><i class="bi bi-save me-1"></i>Kaydet</button>
                          <button class="btn btn-link text-decoration-none text-muted btn-sm" type="button" id="tag-reset">Sıfırla</button>
                        </div>
                        <div id="tag-message" class="small"></div>
                      </form>
                      <hr class="border-secondary">
                      <div class="taxonomy-list" id="tag-list"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <section class="admin-section">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Arşiv</span>
                <h2>Yayınlanmış Yazılar</h2>
                <p class="text-muted mb-0">Duruma göre filtreleyin, hızlıca güncelleyin veya taslağa alın.</p>
              </div>
            </div>
            <div class="card glass-card mb-3">
              <div class="card-body row g-3 align-items-end">
                <div class="col-md-4">
                  <label class="form-label">Durum</label>
                  <select class="form-select" id="post-status-filter">
                    <option value="all">Tümü</option>
                    <option value="published">Yayınlanan</option>
                    <option value="draft">Taslak</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Arama</label>
                  <input type="search" class="form-control" id="post-search" placeholder="Başlık veya içerikte ara">
                </div>
                <div class="col-md-2 d-grid">
                  <button class="btn btn-outline-light" type="button" id="post-refresh"><i class="bi bi-arrow-repeat me-1"></i>Yenile</button>
                </div>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-dark table-hover align-middle" id="post-table">
                <thead>
                  <tr>
                    <th>Başlık</th>
                    <th>Durum</th>
                    <th>Kategoriler</th>
                    <th>Yazar</th>
                    <th>Güncelleme</th>
                    <th class="text-end">İşlemler</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
            <div id="post-table-empty" class="empty-state d-none">Henüz yazı eklenmedi.</div>
          </section>
        </main>
      </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin.js"></script>
  </body>
</html>
