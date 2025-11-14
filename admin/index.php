<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/SiteContext.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/MangaRepository.php';
require_once __DIR__ . '/../src/ChapterRepository.php';
require_once __DIR__ . '/../src/UserRepository.php';

use MangaDiyari\Core\Auth;
use MangaDiyari\Core\SiteContext;
use MangaDiyari\Core\Database;
use MangaDiyari\Core\MangaRepository;
use MangaDiyari\Core\ChapterRepository;
use MangaDiyari\Core\UserRepository;

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

$pdo = Database::getConnection();
$mangaRepo = new MangaRepository($pdo);
$chapterRepo = new ChapterRepository($pdo);
$userRepo = new UserRepository($pdo);

$dashboardStats = [
    'manga_total' => $mangaRepo->count(),
    'chapter_total' => $chapterRepo->count(),
    'chapter_premium' => $chapterRepo->count(['premium_only' => true]),
    'active_members' => $userRepo->count(['active' => true]),
];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Yönetim Paneli</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/admin.css">
  </head>
  <body class="admin-body text-light">
    <div class="admin-shell">
      <aside class="admin-sidebar">
        <div class="sidebar-brand">
          <a href="../public/index.php" class="sidebar-logo d-flex align-items-center gap-2 text-decoration-none text-light">
            <?php if (!empty($site['logo'])): ?>
              <span class="sidebar-logo-image">
                <img src="../public/<?= htmlspecialchars($site['logo']) ?>" alt="<?= htmlspecialchars($site['name']) ?>" loading="lazy">
              </span>
            <?php endif; ?>
            <span class="sidebar-logo-text">
              <strong><?= htmlspecialchars($site['name']) ?></strong>
              <small class="d-block text-muted">Kontrol Paneli</small>
            </span>
          </a>
        </div>
        <div class="sidebar-user d-flex align-items-center gap-3">
          <div class="sidebar-avatar"><?= strtoupper(substr($user['username'], 0, 1)) ?></div>
          <div>
            <div class="fw-semibold"><?= htmlspecialchars($user['username']) ?></div>
            <div class="text-muted small">Rol: <?= htmlspecialchars($user['role']) ?></div>
          </div>
        </div>
        <nav class="sidebar-nav wp-menu">
          <ul class="menu-block">
            <li class="menu-heading">Başlangıç</li>
            <li>
              <a class="menu-link is-active" href="#dashboard" data-section="dashboard">
                <i class="bi bi-speedometer2"></i>
                <span>Giriş</span>
              </a>
            </li>
          </ul>
          <ul class="menu-block">
            <li class="menu-heading">İçerik</li>
            <li>
              <button class="menu-parent" type="button" data-target="menu-manga">
                <span><i class="bi bi-book-half"></i> Manga</span>
                <i class="bi bi-chevron-down"></i>
              </button>
              <ul class="menu-sub" id="menu-manga">
                <li><a class="menu-link" href="#content-management" data-section="manga" data-anchor="#manga-form">Yeni Manga Ekle</a></li>
                <li><a class="menu-link" href="#content-management" data-section="manga" data-anchor="#chapter-form">Yeni Bölüm Ekle</a></li>
                <li><a class="menu-link" href="#homepage" data-section="widgets">Ana Sayfa Widgetları</a></li>
              </ul>
            </li>
            <li>
              <a class="menu-link" href="#pages" data-section="pages">
                <i class="bi bi-file-earmark-text"></i>
                <span>Sayfalar</span>
              </a>
            </li>
          </ul>
          <ul class="menu-block">
            <li class="menu-heading">Görünüm</li>
            <li>
              <button class="menu-parent" type="button" data-target="menu-appearance">
                <span><i class="bi bi-palette"></i> Tema</span>
                <i class="bi bi-chevron-down"></i>
              </button>
              <ul class="menu-sub" id="menu-appearance">
                <li><a class="menu-link" href="#appearance" data-section="appearance">Tema Ayarları</a></li>
                <li><a class="menu-link" href="#homepage" data-section="widgets">Widgetlar</a></li>
                <li><a class="menu-link" href="#menus" data-section="menus">Menüler</a></li>
              </ul>
            </li>
          </ul>
          <ul class="menu-block">
            <li class="menu-heading">Topluluk</li>
            <li><a class="menu-link" href="#community" data-section="community"><i class="bi bi-people"></i><span>Üyeler &amp; Reklam</span></a></li>
          </ul>
          <ul class="menu-block">
            <li class="menu-heading">Ayarlar</li>
            <li><a class="menu-link" href="#integrations" data-section="integrations"><i class="bi bi-plug"></i><span>Entegrasyonlar</span></a></li>
          </ul>
        </nav>
        <div class="sidebar-footer d-flex flex-column gap-2">
          <a href="../public/index.php" class="btn btn-outline-light btn-sm w-100"><i class="bi bi-box-arrow-up-right me-1"></i> Siteyi Görüntüle</a>
          <a href="logout.php" class="btn btn-danger btn-sm w-100"><i class="bi bi-box-arrow-right me-1"></i> Çıkış Yap</a>
        </div>
      </aside>
      <div class="admin-content">
        <header class="admin-header d-flex flex-wrap justify-content-between align-items-center gap-3">
          <div>
            <div class="text-uppercase small text-muted">Hoş geldin, <?= htmlspecialchars($user['username']) ?></div>
            <h1 class="h3 mb-0">Yönetim Paneli</h1>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-outline-light btn-sm js-section-link" href="#menus" data-section="menus"><i class="bi bi-menu-app me-1"></i> Menüler</a>
            <a class="btn btn-primary btn-sm js-section-link" href="#content-management" data-section="manga" data-anchor="#manga-form"><i class="bi bi-plus-circle me-1"></i> Yeni İçerik</a>
          </div>
        </header>
        <main class="admin-main">
          <section id="dashboard" class="admin-section is-active" data-section="dashboard">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Özet</span>
                <h2>Kontrol Paneli</h2>
                <p class="text-muted mb-0">Manga Diyarı sitenizin performansını tek bakışta izleyin.</p>
              </div>
            </div>
            <div class="dashboard-grid">
              <div class="dashboard-card gradient-purple">
                <div class="dashboard-card-icon"><i class="bi bi-journal-bookmark"></i></div>
                <div class="dashboard-card-value" data-dashboard-stat="manga_total"><?= number_format($dashboardStats['manga_total']) ?></div>
                <div class="dashboard-card-label">Toplam Manga</div>
                <div class="dashboard-card-meta">Yeni seri eklemek için İçerik sekmesini kullanın.</div>
              </div>
              <div class="dashboard-card gradient-blue">
                <div class="dashboard-card-icon"><i class="bi bi-collection-play"></i></div>
                <div class="dashboard-card-value" data-dashboard-stat="chapter_total"><?= number_format($dashboardStats['chapter_total']) ?></div>
                <div class="dashboard-card-label">Toplam Bölüm</div>
                <div class="dashboard-card-meta">Bölümleri toplu olarak yükleyebilir veya düzenleyebilirsiniz.</div>
              </div>
              <div class="dashboard-card gradient-amber">
                <div class="dashboard-card-icon"><i class="bi bi-gem"></i></div>
                <div class="dashboard-card-value" data-dashboard-stat="chapter_premium"><?= number_format($dashboardStats['chapter_premium']) ?></div>
                <div class="dashboard-card-label">Premium Bölümler</div>
                <div class="dashboard-card-meta">Ücretli bölümleri kolayca yönetin.</div>
              </div>
              <div class="dashboard-card gradient-green">
                <div class="dashboard-card-icon"><i class="bi bi-people"></i></div>
                <div class="dashboard-card-value" data-dashboard-stat="active_members"><?= number_format($dashboardStats['active_members']) ?></div>
                <div class="dashboard-card-label">Aktif Üye</div>
                <div class="dashboard-card-meta">Topluluk etkileşimini artırmak için ödül sistemini kullanın.</div>
              </div>
            </div>
            <div class="card admin-card mt-4">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                  <div>
                    <h3 class="card-title h5 mb-0">Son Yorumlar</h3>
                    <p class="text-muted small mb-0">Topluluktan gelen en yeni yorumları hızlıca kontrol edin.</p>
                  </div>
                  <button type="button" class="btn btn-outline-light btn-sm" id="refresh-comments"><i class="bi bi-arrow-clockwise me-1"></i>Yenile</button>
                </div>
                <div id="dashboard-comments" class="comment-feed"></div>
              </div>
            </div>
          </section>

          <section id="content-management" class="admin-section" data-section="manga">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Üretim</span>
                <h2>İçerik Yönetimi</h2>
                <p class="text-muted mb-0">Yeni seriler ekleyin, bölümleri toplu olarak yükleyin.</p>
              </div>
            </div>
            <div class="row g-4">
              <div class="col-xl-6">
                <div class="card admin-card h-100">
                  <div class="card-body">
                    <h3 class="card-title h5">Yeni Seri Ekle</h3>
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
                <div class="card admin-card h-100">
                  <div class="card-body">
                    <h3 class="card-title h5">Bölüm Ekle</h3>
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
                      <div class="row g-3">
                        <div class="col-sm-6">
                          <label class="form-label">Ki Ücreti</label>
                          <input type="number" min="0" class="form-control" name="ki_cost" value="0">
                        </div>
                        <div class="col-sm-6">
                          <label class="form-label">Premium Süre (saat)</label>
                          <input type="number" min="0" class="form-control" name="premium_duration_hours" placeholder="Opsiyonel">
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

          <section id="pages" class="admin-section" data-section="pages">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Statik İçerik</span>
                <h2>Sayfa Yönetimi</h2>
                <p class="text-muted mb-0">WordPress benzeri bir düzenle yeni sayfalar oluşturun veya mevcut sayfaları güncelleyin.</p>
              </div>
            </div>
            <div class="row g-4">
              <div class="col-xl-5">
                <div class="card admin-card h-100">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h3 class="card-title h5 mb-0">Sayfa Detayı</h3>
                      <button type="button" class="btn btn-outline-light btn-sm d-none" id="page-cancel-edit"><i class="bi bi-x-lg me-1"></i>İptal</button>
                    </div>
                    <form id="page-form" class="vstack gap-3">
                      <input type="hidden" name="id" id="page-id">
                      <div>
                        <label class="form-label">Başlık</label>
                        <input type="text" class="form-control" name="title" id="page-title" required>
                      </div>
                      <div>
                        <label class="form-label">Bağlantı (slug)</label>
                        <input type="text" class="form-control" name="slug" id="page-slug" placeholder="ornek-sayfa">
                        <div class="form-text">Sadece küçük harf ve tire kullanın. Boş bırakılırsa otomatik oluşturulur.</div>
                      </div>
                      <div>
                        <label class="form-label">Durum</label>
                        <select class="form-select" name="status" id="page-status">
                          <option value="draft">Taslak</option>
                          <option value="published">Yayında</option>
                        </select>
                      </div>
                      <div>
                        <label class="form-label">İçerik</label>
                        <textarea class="form-control" rows="8" name="content" id="page-content" placeholder="Sayfa içeriğini buraya girin..." required></textarea>
                      </div>
                      <div class="d-flex justify-content-between align-items-center">
                        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-save me-1"></i>Sayfayı Kaydet</button>
                        <div class="small text-muted" id="page-form-hint">Yeni sayfalar yayınlandığında menülere ekleyebilirsiniz.</div>
                      </div>
                      <div id="page-form-message"></div>
                    </form>
                  </div>
                </div>
              </div>
              <div class="col-xl-7">
                <div class="card admin-card h-100">
                  <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                      <div>
                        <h3 class="card-title h5 mb-0">Kayıtlı Sayfalar</h3>
                        <p class="text-muted small mb-0">Duruma göre filtreleyebilir veya sayfaları arayabilirsiniz.</p>
                      </div>
                      <div class="d-flex flex-wrap gap-2">
                        <select id="page-status-filter" class="form-select form-select-sm">
                          <option value="">Tümü</option>
                          <option value="published">Yayında</option>
                          <option value="draft">Taslak</option>
                        </select>
                        <div class="input-group input-group-sm">
                          <span class="input-group-text"><i class="bi bi-search"></i></span>
                          <input type="search" id="page-search" class="form-control" placeholder="Sayfa ara">
                        </div>
                      </div>
                    </div>
                    <div class="table-responsive">
                      <table class="table table-dark table-hover align-middle" id="page-table">
                        <thead>
                          <tr>
                            <th>Başlık</th>
                            <th>Durum</th>
                            <th>Bağlantı</th>
                            <th class="text-end">İşlemler</th>
                          </tr>
                        </thead>
                        <tbody></tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <section id="appearance" class="admin-section" data-section="appearance">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Marka</span>
                <h2>Görünüm</h2>
                <p class="text-muted mb-0">Tema renklerini ve marka kimliğini yönetin.</p>
              </div>
            </div>
            <div class="row g-4">
              <div class="col-lg-6">
                <div class="card admin-card h-100">
                  <div class="card-body">
                    <h3 class="card-title h5">Tema Ayarları</h3>
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
                <div class="card admin-card h-100">
                  <div class="card-body">
                    <h3 class="card-title h5">Marka Ayarları</h3>
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

          <section id="homepage" class="admin-section" data-section="widgets">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Bileşenler</span>
                <h2>Ana Sayfa</h2>
                <p class="text-muted mb-0">Bileşenleri etkinleştirerek veya sırasını değiştirerek ana sayfayı şekillendirin.</p>
              </div>
            </div>
            <div class="card admin-card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
                  <div>
                    <h3 class="card-title h5 mb-0">Widget Yönetimi</h3>
                    <span class="text-muted small">Sürükle bırak ile sıralamayı güncelleyebilirsiniz.</span>
                  </div>
                  <span class="badge bg-info text-dark">Widget Sistemi</span>
                </div>
                <div id="widget-list" class="vstack gap-3"></div>
              </div>
            </div>
          </section>

          <section id="menus" class="admin-section" data-section="menus">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Navigasyon</span>
                <h2>Menü Yönetimi</h2>
                <p class="text-muted mb-0">Birden fazla menü alanı oluşturun, bağlantıları sürükleyip sıralayın.</p>
              </div>
              <button class="btn btn-outline-light btn-sm" id="create-menu-btn"><i class="bi bi-plus-circle me-1"></i> Yeni Menü Alanı</button>
            </div>
            <div class="row g-4">
              <div class="col-lg-4">
                <div class="list-group" id="menu-list" role="tablist"></div>
              </div>
              <div class="col-lg-8">
                <div class="card admin-card h-100">
                  <div class="card-body" id="menu-editor">
                    <div class="text-secondary small">Bir menü seçin veya yeni bir menü oluşturun.</div>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <section id="community" class="admin-section" data-section="community">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Topluluk</span>
                <h2>Üyeler &amp; Reklam</h2>
                <p class="text-muted mb-0">Üyeleri görüntüleyin, ödülleri belirleyin ve reklam alanlarını yönetin.</p>
              </div>
            </div>
            <div class="row g-4">
              <div class="col-xl-7">
                <div class="card admin-card h-100">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <div>
                        <h3 class="card-title h5 mb-1">Üye Yönetimi</h3>
                        <p class="text-muted small mb-0">Üyeleri görüntüleyin, rollerini güncelleyin ve hesap durumlarını yönetin.</p>
                      </div>
                      <span class="badge bg-info text-dark">Topluluk</span>
                    </div>
                    <div class="bg-dark bg-opacity-25 border rounded-4 p-3 mb-4">
                      <h4 class="h6">Yeni Üye Oluştur</h4>
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
                <div class="card admin-card h-100">
                  <div class="card-body">
                    <h3 class="card-title h5">Reklam Alanları</h3>
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

          <section id="integrations" class="admin-section" data-section="integrations">
            <div class="admin-section-header">
              <div>
                <span class="eyebrow">Bağlantılar</span>
                <h2>Entegrasyonlar</h2>
                <p class="text-muted mb-0">Takip kodlarını ve FTP bağlantısını kolayca güncelleyin.</p>
              </div>
            </div>
            <div class="row g-4">
              <div class="col-lg-6">
                <div class="card admin-card h-100">
                  <div class="card-body">
                    <h3 class="card-title h5">Analytics &amp; Search Console</h3>
                    <p class="text-muted small">Takip kodlarını buraya yapıştırarak kolayca güncelleyebilirsiniz.</p>
                    <form id="analytics-form" class="vstack gap-3">
                      <div>
                        <label class="form-label">Google Analytics</label>
                        <textarea class="form-control" name="analytics_google" rows="4" placeholder="<script>...</script>"></textarea>
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
                <div class="card admin-card h-100">
                  <div class="card-body">
                    <h3 class="card-title h5">FTP Ayarları</h3>
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
        </main>
      </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      window.dashboardStats = <?= json_encode($dashboardStats, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="assets/admin.js"></script>
  </body>
</html>
