<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/SiteContext.php';

use MangaDiyari\Core\Auth;
use MangaDiyari\Core\SiteContext;

Auth::start();
if (!Auth::checkRole(['admin', 'editor'])) {
    header('Location: login.php');
    exit;
}

$config = require __DIR__ . '/../config.php';
$context = SiteContext::build();
$site = $context['site'];
$user = Auth::user();
$menus = $context['menus'];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Yönetim Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/admin.css">
  </head>
  <body class="bg-dark text-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient shadow-sm">
      <div class="container-fluid px-4">
        <a class="navbar-brand d-flex align-items-center gap-2" href="../public/index.php">
          <?php if (!empty($site['logo'])): ?>
            <img src="../public/<?= htmlspecialchars($site['logo']) ?>" alt="<?= htmlspecialchars($site['name']) ?>" class="brand-logo">
          <?php endif; ?>
          <span><?= htmlspecialchars($site['name']) ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
          <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
            <li class="nav-item"><a class="nav-link" href="../public/index.php">Siteyi Görüntüle</a></li>
            <li class="nav-item"><a class="nav-link" href="#appearance">Tema</a></li>
            <li class="nav-item"><a class="nav-link" href="#menus">Menüler</a></li>
            <li class="nav-item"><a class="nav-link" href="#integrations">Entegrasyonlar</a></li>
            <li class="nav-item"><a class="nav-link" href="logout.php">Çıkış Yap</a></li>
          </ul>
        </div>
      </div>
    </nav>

    <main class="container-fluid px-4 py-5">
      <div class="row g-4">
        <div class="col-lg-3">
          <aside class="admin-sidebar p-4 bg-secondary bg-opacity-25 rounded-4 sticky-lg-top">
            <div class="d-flex align-items-center gap-3 mb-4">
              <div class="avatar-circle">
                <?= strtoupper(substr($user['username'], 0, 1)) ?>
              </div>
              <div>
                <div class="fw-semibold"><?= htmlspecialchars($user['username']) ?></div>
                <div class="text-muted small">Rol: <?= htmlspecialchars($user['role']) ?></div>
              </div>
            </div>
            <nav class="nav flex-column gap-2">
              <a class="nav-link" href="#content-management">İçerik Yönetimi</a>
              <a class="nav-link" href="#appearance">Görünüm</a>
              <a class="nav-link" href="#homepage">Ana Sayfa Bileşenleri</a>
              <a class="nav-link" href="#menus">Menü Yönetimi</a>
              <a class="nav-link" href="#community">Topluluk</a>
              <a class="nav-link" href="#integrations">Entegrasyonlar</a>
            </nav>
            <?php if ($menus['primary'] && !empty($menus['primary']['items'])): ?>
            <div class="mt-4">
              <div class="text-uppercase small text-muted mb-2">Ana Menü</div>
              <ul class="list-unstyled small text-secondary mb-0">
                <?php foreach ($menus['primary']['items'] as $item): ?>
                  <li class="d-flex align-items-center gap-2"><span class="bullet"></span><?= htmlspecialchars($item['label']) ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
            <?php endif; ?>
          </aside>
        </div>
        <div class="col-lg-9">
          <section id="content-management" class="mb-5">
            <div class="section-heading">
              <h1 class="h3 mb-1">İçerik Yönetimi</h1>
              <p class="text-muted">Yeni seriler ekleyin, bölümleri çoklu olarak yükleyin.</p>
            </div>
            <div class="row g-4">
              <div class="col-xl-6">
                <div class="card bg-secondary border-0 shadow-sm h-100">
                  <div class="card-body">
                    <h2 class="h4 mb-3">Yeni Seri Ekle</h2>
                    <form id="manga-form" class="vstack gap-3">
                      <div>
                        <label class="form-label">Seri Başlığı</label>
                        <input type="text" class="form-control" name="title" required>
                      </div>
                      <div>
                        <label class="form-label">Kapak Görseli URL</label>
                        <input type="url" class="form-control" name="cover_image" placeholder="https://">
                      </div>
                      <div class="row g-3">
                        <div class="col-sm-6">
                          <label class="form-label">Yazar</label>
                          <input type="text" class="form-control" name="author">
                        </div>
                        <div class="col-sm-6">
                          <label class="form-label">Durum</label>
                          <select class="form-select" name="status">
                            <option value="ongoing">Devam Ediyor</option>
                            <option value="completed">Tamamlandı</option>
                            <option value="hiatus">Ara Verildi</option>
                          </select>
                        </div>
                      </div>
                      <div>
                        <label class="form-label">Türler</label>
                        <input type="text" class="form-control" name="genres" placeholder="Aksiyon, Fantastik">
                      </div>
                      <div>
                        <label class="form-label">Etiketler</label>
                        <input type="text" class="form-control" name="tags" placeholder="shounen, macera">
                      </div>
                      <div>
                        <label class="form-label">Açıklama</label>
                        <textarea class="form-control" name="description" rows="4"></textarea>
                      </div>
                      <button type="submit" class="btn btn-primary">Seriyi Kaydet</button>
                      <div id="manga-form-message"></div>
                    </form>
                  </div>
                </div>
              </div>
              <div class="col-xl-6">
                <div class="card bg-secondary border-0 shadow-sm h-100">
                  <div class="card-body">
                    <h2 class="h4 mb-3">Bölüm Ekle</h2>
                    <form id="chapter-form" class="vstack gap-3" enctype="multipart/form-data">
                      <div>
                        <label class="form-label">Seri</label>
                        <select class="form-select" name="manga_id" id="manga-select" required></select>
                      </div>
                      <div class="row g-3">
                        <div class="col-sm-6">
                          <label class="form-label">Bölüm Numarası</label>
                          <input type="text" class="form-control" name="number" required>
                        </div>
                        <div class="col-sm-6">
                          <label class="form-label">Başlık</label>
                          <input type="text" class="form-control" name="title">
                        </div>
                      </div>
                      <div>
                        <label class="form-label">Metin İçerik</label>
                        <textarea class="form-control" name="content" rows="4" placeholder="Her satıra bir paragraf"></textarea>
                      </div>
                      <div class="upload-grid">
                        <div>
                          <label class="form-label">Bölüm Görselleri</label>
                          <input type="file" class="form-control" name="chapter_files[]" accept="image/*" multiple>
                          <span class="form-text">Birden fazla görsel seçebilirsiniz.</span>
                        </div>
                        <div>
                          <label class="form-label">Zip Yükle</label>
                          <input type="file" class="form-control" name="chapter_zip" accept=".zip">
                          <span class="form-text">Zip içindeki görseller otomatik sıralanır.</span>
                        </div>
                      </div>
                      <button type="submit" class="btn btn-primary">Bölümü Kaydet</button>
                      <div id="chapter-form-message"></div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <section id="appearance" class="mb-5">
            <div class="section-heading">
              <h2 class="h4 mb-1">Görünüm</h2>
              <p class="text-muted">Tema renklerini ve marka kimliğini yönetin.</p>
            </div>
            <div class="row g-4">
              <div class="col-lg-6">
                <div class="card bg-secondary border-0 shadow-sm h-100">
                  <div class="card-body">
                    <h3 class="h5 mb-3">Tema Ayarları</h3>
                    <form id="theme-form" class="row g-3">
                      <div class="col-sm-6">
                        <label class="form-label" for="primary_color">Birincil Renk</label>
                        <input type="color" class="form-control form-control-color" id="primary_color" name="primary_color" value="#5f2c82">
                      </div>
                      <div class="col-sm-6">
                        <label class="form-label" for="accent_color">Vurgu Rengi</label>
                        <input type="color" class="form-control form-control-color" id="accent_color" name="accent_color" value="#49a09d">
                      </div>
                      <div class="col-sm-6">
                        <label class="form-label" for="background_color">Arka Plan</label>
                        <input type="color" class="form-control form-control-color" id="background_color" name="background_color" value="#05060c">
                      </div>
                      <div class="col-sm-6">
                        <label class="form-label" for="gradient_start">Gradyan Başlangıcı</label>
                        <input type="color" class="form-control form-control-color" id="gradient_start" name="gradient_start" value="#5f2c82">
                      </div>
                      <div class="col-sm-6">
                        <label class="form-label" for="gradient_end">Gradyan Bitişi</label>
                        <input type="color" class="form-control form-control-color" id="gradient_end" name="gradient_end" value="#49a09d">
                      </div>
                      <div class="col-12">
                        <button type="submit" class="btn btn-outline-light btn-sm">Tema Ayarlarını Kaydet</button>
                        <div class="mt-3" id="theme-form-message"></div>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
              <div class="col-lg-6">
                <div class="card bg-secondary border-0 shadow-sm h-100">
                  <div class="card-body">
                    <h3 class="h5 mb-3">Marka Ayarları</h3>
                    <form id="branding-form" class="vstack gap-3" enctype="multipart/form-data">
                      <div>
                        <label class="form-label">Site Adı</label>
                        <input type="text" class="form-control" name="site_name" value="<?= htmlspecialchars($site['name']) ?>">
                      </div>
                      <div>
                        <label class="form-label">Slogan</label>
                        <input type="text" class="form-control" name="site_tagline" value="<?= htmlspecialchars($site['tagline']) ?>">
                      </div>
                      <div>
                        <label class="form-label">Logo</label>
                        <input type="file" class="form-control" name="site_logo" accept="image/*,image/svg+xml">
                        <span class="form-text">Şeffaf arka planlı SVG veya PNG önerilir.</span>
                        <div class="mt-3" id="branding-preview">
                          <?php if (!empty($site['logo'])): ?>
                            <img src="../public/<?= htmlspecialchars($site['logo']) ?>" alt="Logo" class="img-fluid rounded shadow-sm" style="max-height: 80px;">
                          <?php endif; ?>
                        </div>
                      </div>
                      <button type="submit" class="btn btn-outline-light btn-sm">Marka Ayarlarını Kaydet</button>
                      <div class="mt-3" id="branding-form-message"></div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <section id="homepage" class="mb-5">
            <div class="card bg-secondary border-0 shadow-sm">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                  <div>
                    <h2 class="h4 mb-1">Ana Sayfa Bileşenleri</h2>
                    <p class="text-muted small mb-0">Bileşenleri etkinleştirerek veya sırasını değiştirerek ana sayfayı şekillendirin.</p>
                  </div>
                  <span class="badge bg-info text-dark">Widget Sistemi</span>
                </div>
                <div id="widget-list" class="vstack gap-3"></div>
              </div>
            </div>
          </section>

          <section id="menus" class="mb-5">
            <div class="card bg-secondary border-0 shadow-sm">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
                  <div>
                    <h2 class="h4 mb-1">Menü Yönetimi</h2>
                    <p class="text-muted small mb-0">Birden fazla menü alanı oluşturun, bağlantıları sürükleyip sıralayın.</p>
                  </div>
                  <button class="btn btn-outline-light btn-sm" id="create-menu-btn">Yeni Menü Alanı</button>
                </div>
                <div class="row g-4">
                  <div class="col-lg-4">
                    <div class="list-group" id="menu-list" role="tablist"></div>
                  </div>
                  <div class="col-lg-8">
                    <div class="bg-dark bg-opacity-25 rounded-4 p-4 h-100" id="menu-editor">
                      <div class="text-secondary small">Bir menü seçin veya yeni bir menü oluşturun.</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <section id="community" class="mb-5">
            <div class="row g-4">
              <div class="col-xl-7">
                <div class="card bg-secondary border-0 shadow-sm h-100">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <div>
                        <h2 class="h4 mb-1">Üye Yönetimi</h2>
                        <p class="text-muted small mb-0">Üyeleri görüntüleyin, rollerini güncelleyin ve hesap durumlarını yönetin.</p>
                      </div>
                      <span class="badge bg-info text-dark">Topluluk</span>
                    </div>
                    <div class="bg-dark bg-opacity-25 border rounded-4 p-3 mb-4">
                      <h3 class="h5 mb-3">Yeni Üye Oluştur</h3>
                      <form id="create-user-form" class="row g-3">
                        <div class="col-md-4">
                          <label class="form-label">Kullanıcı Adı</label>
                          <input type="text" class="form-control" name="username" required>
                        </div>
                        <div class="col-md-4">
                          <label class="form-label">E-posta</label>
                          <input type="email" class="form-control" name="email" required>
                        </div>
                        <div class="col-md-4">
                          <label class="form-label">Parola</label>
                          <input type="password" class="form-control" name="password" minlength="6" required>
                        </div>
                        <div class="col-md-4">
                          <label class="form-label">Rol</label>
                          <select class="form-select" name="role">
                            <option value="member">Üye</option>
                            <option value="editor">Editör</option>
                            <option value="admin">Yönetici</option>
                          </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                          <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="user-active" checked value="1">
                            <label class="form-check-label" for="user-active">Hesap Aktif</label>
                          </div>
                        </div>
                        <div class="col-12">
                          <button type="submit" class="btn btn-outline-light btn-sm">Üyeyi Oluştur</button>
                        </div>
                      </form>
                      <div class="mt-3" id="create-user-message"></div>
                    </div>
                    <div class="table-responsive">
                      <table class="table table-dark table-striped align-middle" id="user-table">
                        <thead>
                          <tr>
                            <th>Üye</th>
                            <th>Rol</th>
                            <th class="text-center">Durum</th>
                            <th>Parola &amp; İşlemler</th>
                          </tr>
                        </thead>
                        <tbody></tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-xl-5">
                <div class="card bg-secondary border-0 shadow-sm h-100">
                  <div class="card-body">
                    <h2 class="h4 mb-3">Reklam Alanları</h2>
                    <p class="text-muted small">Reklam kodlarını ilgili alanlara ekleyin. HTML ve script etiketleri desteklenir.</p>
                    <form id="ad-form" class="vstack gap-3">
                      <div>
                        <label class="form-label">Üst Kısım</label>
                        <textarea class="form-control" name="ad_header" rows="3" placeholder="Header reklam kodu"></textarea>
                      </div>
                      <div>
                        <label class="form-label">Yan Panel</label>
                        <textarea class="form-control" name="ad_sidebar" rows="3" placeholder="Sidebar reklam kodu"></textarea>
                      </div>
                      <div>
                        <label class="form-label">Alt Bölüm</label>
                        <textarea class="form-control" name="ad_footer" rows="3" placeholder="Footer reklam kodu"></textarea>
                      </div>
                      <button type="submit" class="btn btn-outline-light btn-sm">Reklam Alanlarını Kaydet</button>
                      <div class="mt-3" id="ad-form-message"></div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <section id="integrations" class="mb-5">
            <div class="row g-4">
              <div class="col-lg-6">
                <div class="card bg-secondary border-0 shadow-sm h-100">
                  <div class="card-body">
                    <h2 class="h4 mb-3">Analytics &amp; Search Console</h2>
                    <p class="text-muted small">Takip kodlarını buraya yapıştırarak kolayca güncelleyebilirsiniz.</p>
                    <form id="analytics-form" class="vstack gap-3">
                      <div>
                        <label class="form-label">Google Analytics</label>
                        <textarea class="form-control" name="analytics_google" rows="4" placeholder="&lt;script&gt;...&lt;/script&gt;"></textarea>
                      </div>
                      <div>
                        <label class="form-label">Google Search Console</label>
                        <textarea class="form-control" name="analytics_search_console" rows="3" placeholder="Meta doğrulama kodu"></textarea>
                      </div>
                      <button type="submit" class="btn btn-outline-light btn-sm">Analitik Kodlarını Kaydet</button>
                      <div class="mt-3" id="analytics-form-message"></div>
                    </form>
                  </div>
                </div>
              </div>
              <div class="col-lg-6">
                <div class="card bg-secondary border-0 shadow-sm h-100">
                  <div class="card-body">
                    <h2 class="h4 mb-3">FTP Ayarları</h2>
                    <p class="text-muted small">Bölümleri uzak sunucuya aktarmak için FTP bağlantısını yapılandırın.</p>
                    <form id="ftp-form" class="vstack gap-3">
                      <div class="row g-3">
                        <div class="col-sm-6">
                          <label class="form-label">Sunucu</label>
                          <input type="text" class="form-control" name="ftp_host" placeholder="ftp.example.com">
                        </div>
                        <div class="col-sm-6">
                          <label class="form-label">Port</label>
                          <input type="number" class="form-control" name="ftp_port" value="21" min="1" max="65535">
                        </div>
                        <div class="col-sm-6">
                          <label class="form-label">Kullanıcı Adı</label>
                          <input type="text" class="form-control" name="ftp_username">
                        </div>
                        <div class="col-sm-6">
                          <label class="form-label">Parola</label>
                          <input type="password" class="form-control" name="ftp_password">
                        </div>
                        <div class="col-12">
                          <label class="form-label">Kök Klasör</label>
                          <input type="text" class="form-control" name="ftp_root" placeholder="/public_html/manga" value="/">
                        </div>
                      </div>
                      <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="ftp_passive" id="ftp-passive" value="1" checked>
                        <label class="form-check-label" for="ftp-passive">Pasif Mod</label>
                      </div>
                      <div class="d-flex align-items-center gap-3">
                        <button type="submit" class="btn btn-outline-light btn-sm">FTP Ayarlarını Kaydet</button>
                        <span class="small" id="ftp-form-message"></span>
                      </div>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </section>
        </div>
      </div>
    </main>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/admin.js"></script>
  </body>
</html>
