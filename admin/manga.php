<?php
require_once __DIR__ . '/bootstrap.php';

$pageTitle = 'Manga Yönetimi';
$pageSubtitle = 'Serilerinizi detaylı olarak yönetin, filtreleyin ve düzenleyin.';
$headerActions = [];
$defaultStorageDriver = $settingRepo->get('chapter_storage_driver', 'local');
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
  <body class="admin-body text-light" data-admin-page="manga" data-default-storage="<?= htmlspecialchars($defaultStorageDriver) ?>">
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
            <form id="manga-form" class="card glass-card" enctype="multipart/form-data">
              <div class="card-body row g-4">
                <div class="col-lg-6">
                  <label class="form-label">Manga İsmi <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="title" required placeholder="Örn. Efsanevi Macera">
                </div>
                <div class="col-lg-6">
                  <label class="form-label">Seri Kısayolu (Slug)</label>
                  <input type="text" class="form-control" name="slug" placeholder="efsanevi-macera">
                  <small class="text-muted">Boş bırakırsanız başlıktan otomatik oluşturulur.</small>
                </div>
                <div class="col-lg-6">
                  <label class="form-label">Kapak Görseli</label>
                  <div class="input-group">
                    <input type="url" class="form-control" name="cover_image" id="manga-cover-url" placeholder="https://...">
                    <button class="btn btn-outline-light" type="button" data-cover-trigger="#manga-cover-upload" data-default-label="Dosya Seç">Dosya Seç</button>
                  </div>
                  <input type="file" class="d-none" id="manga-cover-upload" name="cover_upload" accept="image/*">
                  <div class="form-text" data-cover-selected-for="#manga-cover-upload">URL girebilir veya dosya seçebilirsiniz.</div>
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

          <section class="admin-section" id="quick-chapter">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Hızlı İşlem</span>
                <h2>Bölüm Ekle</h2>
                <p class="text-muted mb-0">Herhangi bir mangaya hızlıca yeni bölüm atayın, WordPress yazı editörü gibi pratik bir akış.</p>
              </div>
            </div>
            <form id="quick-chapter-form" class="card glass-card" enctype="multipart/form-data">
              <div class="card-body row g-4">
                <div class="col-lg-4">
                  <label class="form-label">Manga</label>
                  <select class="form-select" id="quick-chapter-manga" name="manga_id" required>
                    <option value="">Seri seçin</option>
                  </select>
                </div>
                <div class="col-md-2">
                  <label class="form-label">Bölüm No</label>
                  <input type="text" class="form-control" name="number" id="quick-chapter-number" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Başlık</label>
                  <input type="text" class="form-control" name="title" id="quick-chapter-title" placeholder="Opsiyonel">
                </div>
                <div class="col-md-3">
                  <label class="form-label">Depolama</label>
                  <select class="form-select" name="storage_target" id="quick-chapter-storage">
                    <option value="local">Yerel</option>
                    <option value="ftp">FTP</option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label">Metin İçerik / Not</label>
                  <textarea class="form-control" rows="3" name="content" id="quick-chapter-content" placeholder="Özet veya ek notlar"></textarea>
                </div>
                <div class="col-md-4">
                  <label class="form-label">ZIP Yükle</label>
                  <input type="file" class="form-control" name="chapter_zip" id="quick-chapter-zip" accept=".zip">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Görseller</label>
                  <input type="file" class="form-control" name="chapter_files[]" id="quick-chapter-files" accept="image/*" multiple>
                </div>
                <div class="col-md-2">
                  <label class="form-label">Ki Bedeli</label>
                  <input type="number" class="form-control" name="ki_cost" id="quick-chapter-ki" min="0" value="0">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Premium (saat)</label>
                  <input type="number" class="form-control" name="premium_duration_hours" id="quick-chapter-premium" min="0" value="0">
                </div>
                <div class="col-12" id="quick-chapter-message"></div>
              </div>
              <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-primary" type="submit"><i class="bi bi-cloud-upload me-1"></i>Bölümü Kaydet</button>
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
      <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content bg-dark text-light">
          <div class="modal-header border-secondary">
            <h5 class="modal-title">Mangayı Düzenle</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
          </div>
          <div class="modal-body">
            <ul class="nav nav-tabs" id="manga-edit-tabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="manga-tab-general" data-bs-toggle="tab" data-bs-target="#manga-tab-general-pane" type="button" role="tab" aria-controls="manga-tab-general-pane" aria-selected="true">Genel</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="manga-tab-chapters" data-bs-toggle="tab" data-bs-target="#manga-tab-chapters-pane" type="button" role="tab" aria-controls="manga-tab-chapters-pane" aria-selected="false">Bölümler</button>
              </li>
            </ul>
            <div class="tab-content pt-4">
              <div class="tab-pane fade show active" id="manga-tab-general-pane" role="tabpanel" aria-labelledby="manga-tab-general">
                <form id="manga-edit-form" class="row g-3" enctype="multipart/form-data">
                  <input type="hidden" name="id" id="edit-manga-id">
                  <div class="col-lg-6">
                    <label class="form-label">Manga İsmi</label>
                    <input type="text" class="form-control" name="title" id="edit-manga-title" required>
                  </div>
                  <div class="col-lg-6">
                    <label class="form-label">Seri Kısayolu (Slug)</label>
                    <input type="text" class="form-control" name="slug" id="edit-manga-slug" placeholder="efsanevi-macera">
                    <small class="text-muted">Boş bırakırsanız mevcut değer korunur.</small>
                  </div>
                  <div class="col-lg-6">
                    <label class="form-label">Kapak Görseli</label>
                    <div class="input-group">
                      <input type="url" class="form-control" name="cover_image" id="edit-manga-cover" placeholder="https://...">
                      <button class="btn btn-outline-light" type="button" data-cover-trigger="#edit-manga-cover-upload" data-default-label="Dosya Seç">Dosya Seç</button>
                    </div>
                    <input type="file" class="d-none" id="edit-manga-cover-upload" name="cover_upload" accept="image/*">
                    <div class="form-text" data-cover-selected-for="#edit-manga-cover-upload">URL girebilir veya dosya seçebilirsiniz.</div>
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
                </form>
              </div>
              <div class="tab-pane fade" id="manga-tab-chapters-pane" role="tabpanel" aria-labelledby="manga-tab-chapters">
                <div class="mb-3">
                  <h6 class="mb-1">Yeni Bölüm Yükle</h6>
                  <p class="text-muted small mb-0">ZIP ya da görsel dosyalarıyla hızlıca bölüm ekleyin.</p>
                </div>
                <form id="inline-chapter-form" class="row g-3" enctype="multipart/form-data">
                  <input type="hidden" name="manga_id" id="inline-chapter-manga-id">
                  <div class="col-md-3">
                    <label class="form-label">Bölüm No <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="number" id="inline-chapter-number" required>
                  </div>
                  <div class="col-md-5">
                    <label class="form-label">Bölüm Başlığı</label>
                    <input type="text" class="form-control" name="title" id="inline-chapter-title" placeholder="Opsiyonel başlık">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Depolama Hedefi</label>
                    <div class="btn-group w-100" role="group">
                      <input type="radio" class="btn-check" name="storage_target" id="inline-storage-local" value="local">
                      <label class="btn btn-outline-light" for="inline-storage-local">Yerel</label>
                      <input type="radio" class="btn-check" name="storage_target" id="inline-storage-ftp" value="ftp">
                      <label class="btn btn-outline-light" for="inline-storage-ftp">FTP</label>
                    </div>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Bölüm İçeriği</label>
                    <textarea class="form-control" name="content" id="inline-chapter-content" rows="3" placeholder="Metin içerik veya özet"></textarea>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Premium Süre (saat)</label>
                    <input type="number" class="form-control" name="premium_duration_hours" id="inline-premium-duration" min="0" step="1" placeholder="0">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Kilidi Açmak İçin Ki</label>
                    <input type="number" class="form-control" name="ki_cost" id="inline-ki-cost" min="0" value="0">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Bölüm ZIP</label>
                    <input type="file" class="form-control" name="chapter_zip" id="inline-chapter-zip" accept=".zip">
                    <small class="text-muted">ZIP yerine aşağıdaki dosya seçeneğini kullanabilirsiniz.</small>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Görseller</label>
                    <input type="file" class="form-control" name="chapter_files[]" id="inline-chapter-files" accept="image/*" multiple>
                  </div>
                  <div id="inline-chapter-message" class="col-12"></div>
                  <div class="col-12 d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-cloud-upload me-1"></i>Bölümü Kaydet</button>
                  </div>
                </form>
                <div class="d-flex justify-content-between align-items-center mt-4">
                  <h6 class="mb-0">Mevcut Bölümler</h6>
                  <div class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0" for="inline-chapter-sort">Sıralama</label>
                    <select id="inline-chapter-sort" class="form-select form-select-sm" style="width: 160px;">
                      <option value="desc">Yeni &gt; Eski</option>
                      <option value="asc">Eski &gt; Yeni</option>
                    </select>
                  </div>
                </div>
                <div class="table-responsive mt-3">
                  <table class="table table-dark table-hover align-middle" id="inline-chapter-table">
                    <thead>
                      <tr>
                        <th scope="col">Bölüm</th>
                        <th scope="col">Başlık</th>
                        <th scope="col">Premium</th>
                        <th scope="col">Sayfa</th>
                        <th scope="col">Güncelleme</th>
                        <th scope="col" class="text-end">İşlemler</th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                  </table>
                </div>
                <div id="inline-chapter-empty" class="empty-state d-none mt-3">Bu mangaya ait bölüm bulunamadı.</div>
              </div>
            </div>
          </div>
          <div class="modal-footer border-secondary d-flex justify-content-between">
            <button type="button" class="btn btn-outline-light" data-bs-dismiss="modal">Kapat</button>
            <div class="d-flex gap-2">
              <button type="button" class="btn btn-danger" id="delete-manga"><i class="bi bi-trash me-1"></i>Sil</button>
              <button type="submit" class="btn btn-primary" form="manga-edit-form"><i class="bi bi-save me-1"></i>Güncelle</button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <?php require __DIR__ . '/partials/chapter-edit-modal.php'; ?>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin.js"></script>
  </body>
</html>
