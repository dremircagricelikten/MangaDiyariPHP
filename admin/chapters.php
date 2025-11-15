<?php
require_once __DIR__ . '/bootstrap.php';

$pageTitle = 'Bölüm Yönetimi';
$pageSubtitle = 'Tekli veya toplu yükleme seçenekleriyle bölümlerinizi yönetin.';
$headerActions = [
    ['href' => 'manga.php', 'label' => 'Manga Yönetimine Dön', 'class' => 'btn-outline-light btn-sm', 'icon' => 'bi bi-arrow-left'],
];

$storageSetting = $settingRepo->get('chapter_storage_driver', 'local');
$defaultStorageDriver = in_array($storageSetting, ['local', 'ftp'], true) ? $storageSetting : 'local';
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Bölüm Yönetimi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/admin.css">
  </head>
  <body class="admin-body text-light" data-admin-page="chapters">
    <div class="admin-shell">
      <?php require __DIR__ . '/partials/sidebar.php'; ?>
      <div class="admin-content">
        <?php require __DIR__ . '/partials/header.php'; ?>
        <main class="admin-main">
          <section class="admin-section is-active">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Tekli Bölüm</span>
                <h2>Yeni Bölüm Ekle</h2>
                <p class="text-muted mb-0">ZIP içindeki görselleri yükleyin, depolama hedefini seçin.</p>
              </div>
            </div>
            <form id="chapter-form" class="card glass-card" enctype="multipart/form-data">
              <div class="card-body row g-4">
                <div class="col-lg-4">
                  <label class="form-label">Manga <span class="text-danger">*</span></label>
                  <select class="form-select" name="manga_id" id="manga-select" required></select>
                </div>
                <div class="col-lg-4">
                  <label class="form-label">Bölüm No <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="number" placeholder="Örn. 10" required>
                </div>
                <div class="col-lg-4">
                  <label class="form-label">Bölüm Başlığı</label>
                  <input type="text" class="form-control" name="title" placeholder="İsteğe bağlı alt başlık">
                </div>
                <div class="col-12">
                  <label class="form-label">Bölüm İçeriği (isteğe bağlı)</label>
                  <textarea class="form-control" name="content" rows="3" placeholder="Metin içerik veya açıklamalar"></textarea>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Premium Süre (saat)</label>
                  <input type="number" class="form-control" name="premium_duration_hours" min="0" step="1" placeholder="24">
                  <small class="text-muted">0 olarak bırakırsanız varsayılan süre kullanılacak.</small>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Kilidi Açmak İçin Ki</label>
                  <input type="number" class="form-control" name="ki_cost" min="0" step="1" value="0">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Depolama Hedefi</label>
                  <div class="btn-group w-100" role="group" aria-label="Depolama">
                    <input type="radio" class="btn-check" name="storage_target" id="storage-local" value="local" autocomplete="off" <?= $defaultStorageDriver === 'local' ? 'checked' : '' ?>>
                    <label class="btn btn-outline-light" for="storage-local">Yerel</label>
                    <input type="radio" class="btn-check" name="storage_target" id="storage-ftp" value="ftp" autocomplete="off" <?= $defaultStorageDriver === 'ftp' ? 'checked' : '' ?>>
                    <label class="btn btn-outline-light" for="storage-ftp">FTP</label>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Bölüm ZIP Dosyası</label>
                  <input type="file" class="form-control" name="chapter_zip" accept=".zip">
                  <small class="text-muted">Tekli bölüm için tüm sayfaları ZIP içine koyun.</small>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Alternatif Görseller</label>
                  <input type="file" class="form-control" name="chapter_files[]" accept="image/*" multiple>
                  <small class="text-muted">ZIP kullanmıyorsanız doğrudan görselleri seçebilirsiniz.</small>
                </div>
                <div id="chapter-form-message" class="col-12"></div>
              </div>
              <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-primary" type="submit"><i class="bi bi-cloud-upload me-1"></i>Bölümü Yükle</button>
              </div>
            </form>
          </section>

          <section class="admin-section">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Toplu Yükleme</span>
                <h2>Çoklu Bölüm ZIP</h2>
                <p class="text-muted mb-0">ZIP içindeki klasör adları bölüm numaralarını temsil etmelidir.</p>
              </div>
            </div>
            <form id="bulk-chapter-form" class="card glass-card" enctype="multipart/form-data">
              <div class="card-body row g-4">
                <div class="col-lg-6">
                  <label class="form-label">Manga <span class="text-danger">*</span></label>
                  <select class="form-select" name="manga_id" id="bulk-manga-select" required></select>
                </div>
                <div class="col-lg-6">
                  <label class="form-label">Depolama Hedefi</label>
                  <div class="btn-group w-100" role="group" aria-label="Depolama">
                    <input type="radio" class="btn-check" name="storage_target" id="bulk-storage-local" value="local" autocomplete="off" <?= $defaultStorageDriver === 'local' ? 'checked' : '' ?>>
                    <label class="btn btn-outline-light" for="bulk-storage-local">Yerel</label>
                    <input type="radio" class="btn-check" name="storage_target" id="bulk-storage-ftp" value="ftp" autocomplete="off" <?= $defaultStorageDriver === 'ftp' ? 'checked' : '' ?>>
                    <label class="btn btn-outline-light" for="bulk-storage-ftp">FTP</label>
                  </div>
                </div>
                <div class="col-md-12">
                  <label class="form-label">Bölüm Paketi (.zip)</label>
                  <input type="file" class="form-control" name="chapter_bundle" accept=".zip" required>
                  <small class="text-muted">Her bölüm klasör olarak ayrılmalı, klasör adı bölüm numarasını içermelidir (örn. 10, 10.5).</small>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Varsayılan Ki Bedeli</label>
                  <input type="number" class="form-control" name="ki_cost" min="0" value="0">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Premium Süre (saat)</label>
                  <input type="number" class="form-control" name="premium_duration_hours" min="0" step="1" placeholder="0">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Başlık Şablonu</label>
                  <input type="text" class="form-control" name="title_template" placeholder="Örn. Bölüm {{number}}">
                </div>
                <div id="bulk-chapter-message" class="col-12"></div>
              </div>
              <div class="card-footer d-flex justify-content-end">
                <button class="btn btn-primary" type="submit"><i class="bi bi-collection me-1"></i>Toplu Yükle</button>
              </div>
            </form>
          </section>

          <section class="admin-section">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Bölüm Listesi</span>
                <h2>Seri Bazlı Görünüm</h2>
                <p class="text-muted mb-0">Seçtiğiniz serinin tüm bölümlerini düzenleyin.</p>
              </div>
            </div>
            <div class="card glass-card mb-3">
              <div class="card-body row g-3 align-items-end">
                <div class="col-md-6">
                  <label class="form-label">Manga</label>
                  <select class="form-select" id="chapter-list-manga"></select>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Sıralama</label>
                  <select class="form-select" id="chapter-sort">
                    <option value="desc">Yeni &gt; Eski</option>
                    <option value="asc">Eski &gt; Yeni</option>
                  </select>
                </div>
                <div class="col-md-3 d-grid">
                  <button class="btn btn-outline-light" type="button" id="refresh-chapter-list"><i class="bi bi-arrow-repeat me-1"></i>Yenile</button>
                </div>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-dark table-hover align-middle" id="chapter-table">
                <thead>
                  <tr>
                    <th scope="col">Bölüm</th>
                    <th scope="col">Başlık</th>
                    <th scope="col">Premium</th>
                    <th scope="col">Sayfa</th>
                    <th scope="col">Güncelleme</th>
                    <th scope="col" class="text-end">İşlem</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
            <div id="chapter-table-empty" class="empty-state d-none">Seçilen seriye ait bölüm bulunamadı.</div>
          </section>
        </main>
      </div>
    </div>

    <?php require __DIR__ . '/partials/chapter-edit-modal.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin.js"></script>
  </body>
</html>
