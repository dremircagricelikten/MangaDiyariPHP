<?php
require_once __DIR__ . '/bootstrap.php';

$pageTitle = 'Manga Yönetimi';
$pageSubtitle = 'Serilerinizi detaylı olarak yönetin, filtreleyin ve düzenleyin.';
$headerActions = [
    ['href' => 'chapters.php', 'label' => 'Bölüm Yönetimine Git', 'class' => 'btn-outline-light btn-sm', 'icon' => 'bi bi-journal-richtext'],
];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Manga Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/admin.css">
  </head>
  <body class="admin-body text-light" data-admin-page="manga">
    <div class="admin-shell">
      <?php require __DIR__ . '/partials/sidebar.php'; ?>
      <div class="admin-content">
        <?php require __DIR__ . '/partials/header.php'; ?>
        <main class="admin-main">
          <section class="admin-section is-active">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Yeni Seri</span>
                <h2>Manga Ekle</h2>
                <p class="text-muted mb-0">Serinin tüm detaylarını eksiksiz olarak girin.</p>
              </div>
            </div>
            <form id="manga-form" class="card glass-card">
              <div class="card-body row g-4">
                <div class="col-lg-6">
                  <label class="form-label">Manga İsmi <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="title" required placeholder="Örn. Efsanevi Macera">
                </div>
                <div class="col-lg-6">
                  <label class="form-label">Kapak Görseli URL</label>
                  <input type="url" class="form-control" name="cover_image" placeholder="https://...">
                </div>
                <div class="col-12">
                  <label class="form-label">Konusu</label>
                  <textarea class="form-control" name="description" rows="4" placeholder="Serinin ana hikayesini özetleyin"></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Türü</label>
                  <input type="text" class="form-control" name="type" placeholder="Aksiyon, Macera">
                  <small class="text-muted">Virgül ile ayırarak birden fazla tür ekleyebilirsiniz.</small>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Durumu</label>
                  <select class="form-select" name="status">
                    <option value="ongoing">Devam Ediyor</option>
                    <option value="completed">Tamamlandı</option>
                    <option value="hiatus">Ara Verildi</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Yazar</label>
                  <input type="text" class="form-control" name="author" placeholder="Yazar adı">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Çizer</label>
                  <input type="text" class="form-control" name="artist" placeholder="Çizer adı">
                </div>
                <div class="col-12">
                  <label class="form-label">Etiketler</label>
                  <input type="text" class="form-control" name="tags" placeholder="#dram #fantastik">
                  <small class="text-muted">Etiketleri boşlukla ayırabilirsiniz.</small>
                </div>
              </div>
              <div class="card-footer d-flex justify-content-between align-items-center gap-3">
                <div id="manga-form-message"></div>
                <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Mangayı Kaydet</button>
              </div>
            </form>
          </section>

          <section class="admin-section">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Mevcut Seriler</span>
                <h2>Manga Kataloğu</h2>
                <p class="text-muted mb-0">Serilerinizi filtreleyin, düzenleyin ve hızla güncelleyin.</p>
              </div>
            </div>
            <div class="card glass-card mb-3">
              <div class="card-body row g-3 align-items-end">
                <div class="col-md-6">
                  <label class="form-label">Arama</label>
                  <input type="search" class="form-control" id="manga-search" placeholder="İsim veya yazara göre ara">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Durum</label>
                  <select class="form-select" id="manga-status-filter">
                    <option value="">Tümü</option>
                    <option value="ongoing">Devam Ediyor</option>
                    <option value="completed">Tamamlandı</option>
                    <option value="hiatus">Ara Verildi</option>
                  </select>
                </div>
                <div class="col-md-3 d-grid">
                  <button class="btn btn-outline-light" type="button" id="refresh-manga-list"><i class="bi bi-arrow-repeat me-1"></i>Listeyi Yenile</button>
                </div>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-dark table-hover align-middle" id="manga-table">
                <thead>
                  <tr>
                    <th scope="col">Kapak</th>
                    <th scope="col">Manga</th>
                    <th scope="col">Türler</th>
                    <th scope="col">Durum</th>
                    <th scope="col">Son Güncelleme</th>
                    <th scope="col" class="text-end">İşlemler</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
            <div id="manga-table-empty" class="empty-state d-none">Henüz manga eklenmemiş.</div>
          </section>
        </main>
      </div>
    </div>

    <div class="modal fade" id="manga-edit-modal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content bg-dark text-light">
          <form id="manga-edit-form">
            <div class="modal-header border-secondary">
              <h5 class="modal-title">Mangayı Düzenle</h5>
              <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
            </div>
            <div class="modal-body row g-3">
              <input type="hidden" name="id" id="edit-manga-id">
              <div class="col-lg-6">
                <label class="form-label">Manga İsmi</label>
                <input type="text" class="form-control" name="title" id="edit-manga-title" required>
              </div>
              <div class="col-lg-6">
                <label class="form-label">Kapak Görseli URL</label>
                <input type="url" class="form-control" name="cover_image" id="edit-manga-cover">
              </div>
              <div class="col-12">
                <label class="form-label">Konusu</label>
                <textarea class="form-control" name="description" id="edit-manga-description" rows="3"></textarea>
              </div>
              <div class="col-md-6">
                <label class="form-label">Türü</label>
                <input type="text" class="form-control" name="type" id="edit-manga-type">
              </div>
              <div class="col-md-6">
                <label class="form-label">Durumu</label>
                <select class="form-select" name="status" id="edit-manga-status">
                  <option value="ongoing">Devam Ediyor</option>
                  <option value="completed">Tamamlandı</option>
                  <option value="hiatus">Ara Verildi</option>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">Yazar</label>
                <input type="text" class="form-control" name="author" id="edit-manga-author">
              </div>
              <div class="col-md-6">
                <label class="form-label">Çizer</label>
                <input type="text" class="form-control" name="artist" id="edit-manga-artist">
              </div>
              <div class="col-12">
                <label class="form-label">Etiketler</label>
                <input type="text" class="form-control" name="tags" id="edit-manga-tags">
              </div>
              <div id="manga-edit-message" class="col-12"></div>
            </div>
            <div class="modal-footer border-secondary d-flex justify-content-between">
              <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Vazgeç</button>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-danger" id="delete-manga"><i class="bi bi-trash me-1"></i>Sil</button>
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
