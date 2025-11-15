<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/UserRepository.php';
require_once __DIR__ . '/../src/SettingRepository.php';
require_once __DIR__ . '/../src/SiteContext.php';
require_once __DIR__ . '/../src/FollowRepository.php';

use MangaDiyari\Core\Auth;
use MangaDiyari\Core\Database;
use MangaDiyari\Core\UserRepository;
use MangaDiyari\Core\SettingRepository;
use MangaDiyari\Core\SiteContext;
use MangaDiyari\Core\FollowRepository;

Auth::start();
if (!Auth::check()) {
    header('Location: login.php?redirect=profile.php');
    exit;
}

$context = SiteContext::build();
$site = $context['site'];
$menus = $context['menus'];
$ads = $context['ads'];
$analytics = $context['analytics'];

$pdo = Database::getConnection();
$userRepo = new UserRepository($pdo);
$settingRepo = new SettingRepository($pdo);
$followRepo = new FollowRepository($pdo);
$allSettings = $settingRepo->all();
$themeDefaults = [
    'primary_color' => '#5f2c82',
    'accent_color' => '#49a09d',
    'background_color' => '#05060c',
    'gradient_start' => '#5f2c82',
    'gradient_end' => '#49a09d',
];
$theme = array_replace($themeDefaults, $allSettings);
$kiSettings = [
    'currency_name' => $allSettings['ki_currency_name'] ?? 'Ki',
];
$footerText = trim((string) ($allSettings['site_footer'] ?? ''));
$defaultFooter = '© ' . date('Y') . ' ' . $site['name'] . '. Tüm hakları saklıdır.';

$sessionUser = Auth::user();
$profileUser = $userRepo->findById((int) $sessionUser['id']);
if (!$profileUser) {
    Auth::logout();
    header('Location: login.php');
    exit;
}

unset($profileUser['password_hash']);

$followedSeries = $followRepo->listFollowedByUser((int) $profileUser['id'], 50);

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'update-profile') {
            $updated = $userRepo->updateProfile((int) $profileUser['id'], [
                'email' => $_POST['email'] ?? '',
                'bio' => $_POST['bio'] ?? '',
                'avatar_url' => $_POST['avatar_url'] ?? '',
                'website_url' => $_POST['website_url'] ?? '',
            ]);
            Auth::login($updated);
            $sessionUser = Auth::user();
            $profileUser = array_merge($profileUser, $updated);
            $success = 'Profil bilgileriniz güncellendi.';
        } elseif ($action === 'change-password') {
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['new_password_confirmation'] ?? '';

            if ($newPassword === '' || $currentPassword === '') {
                throw new InvalidArgumentException('Parola alanları boş bırakılamaz.');
            }

            if ($newPassword !== $confirmPassword) {
                throw new InvalidArgumentException('Yeni parolalar eşleşmiyor.');
            }

            if (strlen($newPassword) < 6) {
                throw new InvalidArgumentException('Yeni parola en az 6 karakter olmalıdır.');
            }

            if (!$userRepo->verifyCredentials($profileUser['email'], $currentPassword)) {
                throw new InvalidArgumentException('Mevcut parolanızı doğrulayamadık.');
            }

            $updated = $userRepo->updateCredentials((int) $profileUser['id'], null, null, $newPassword);
            Auth::login($updated);
            $sessionUser = Auth::user();
            $profileUser = array_merge($profileUser, $updated);
            $success = 'Parolanız başarıyla güncellendi.';
        }
    } catch (Throwable $exception) {
        $error = $exception->getMessage();
        $success = null;
    }
}

$joinDate = $profileUser['created_at'] ?? null;
$joinDateFormatted = $joinDate ? date('d F Y', strtotime($joinDate)) : null;
$publicProfileUrl = 'member.php?u=' . urlencode($profileUser['username']);

$primaryMenuItems = $menus['primary']['items'] ?? [];
$footerMenuItems = $menus['footer']['items'] ?? [];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Üye Profili</title>
    <?php if (!empty($analytics['search_console'])): ?>
      <?= $analytics['search_console'] ?>
    <?php endif; ?>
    <?php if (!empty($analytics['google'])): ?>
      <?= $analytics['google'] ?>
    <?php endif; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
    <style>
      :root {
        --color-primary: <?= htmlspecialchars($theme['primary_color']) ?>;
        --color-accent: <?= htmlspecialchars($theme['accent_color']) ?>;
        --color-background: <?= htmlspecialchars($theme['background_color']) ?>;
        --gradient-start: <?= htmlspecialchars($theme['gradient_start']) ?>;
        --gradient-end: <?= htmlspecialchars($theme['gradient_end']) ?>;
      }
    </style>
  </head>
  <body class="site-body" data-theme="dark">
    <?php $showSearchForm = false; $user = $sessionUser; require __DIR__ . '/partials/site-navbar.php'; unset($user); ?>

    <?php if (!empty($ads['header'])): ?>
      <section class="ad-slot ad-slot--header py-3">
        <div class="container">
          <div class="ad-wrapper text-center">
            <?= $ads['header'] ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <main class="container my-5">
      <div class="row g-4">
        <div class="col-lg-4">
          <div class="card bg-secondary border-0 shadow-sm h-100">
            <div class="card-body text-center p-4">
              <?php if (!empty($profileUser['avatar_url'])): ?>
                <img src="<?= htmlspecialchars($profileUser['avatar_url']) ?>" alt="Avatar" class="rounded-circle mb-3" width="120" height="120">
              <?php else: ?>
                <div class="rounded-circle bg-dark bg-opacity-50 d-inline-flex align-items-center justify-content-center mb-3" style="width: 120px; height: 120px; font-size: 48px;">
                  <?= htmlspecialchars(strtoupper(substr($profileUser['username'], 0, 1))) ?>
                </div>
              <?php endif; ?>
              <h1 class="h4 mb-1"><?= htmlspecialchars($profileUser['username']) ?></h1>
              <p class="text-muted small mb-2"><?= htmlspecialchars($profileUser['email']) ?></p>
              <?php if ($joinDateFormatted): ?>
                <p class="text-muted small">Üyelik tarihi: <?= htmlspecialchars($joinDateFormatted) ?></p>
              <?php endif; ?>
              <a href="<?= htmlspecialchars($publicProfileUrl) ?>" class="btn btn-outline-light btn-sm">Kamu Profilini Görüntüle</a>
            </div>
          </div>
        </div>
        <div class="col-lg-8">
          <div class="card bg-secondary border-0 shadow-sm mb-4">
            <div class="card-body p-4">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h4 mb-0">Profil Bilgileri</h2>
                <?php if ($success): ?>
                  <span class="badge bg-success bg-opacity-75 text-light">Güncel</span>
                <?php endif; ?>
              </div>
              <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
              <?php elseif ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
              <?php endif; ?>
              <form method="post" class="vstack gap-3">
                <input type="hidden" name="action" value="update-profile">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Kullanıcı Adı</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($profileUser['username']) ?>" disabled>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">E-posta</label>
                    <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($profileUser['email']) ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Avatar URL</label>
                    <input type="url" class="form-control" name="avatar_url" placeholder="https://" value="<?= htmlspecialchars($profileUser['avatar_url'] ?? '') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Web Sitesi</label>
                    <input type="url" class="form-control" name="website_url" placeholder="https://" value="<?= htmlspecialchars($profileUser['website_url'] ?? '') ?>">
                  </div>
                  <div class="col-12">
                    <label class="form-label">Hakkımda</label>
                    <textarea class="form-control" name="bio" rows="4" placeholder="Topluluğa kendinizi tanıtın."><?= htmlspecialchars($profileUser['bio'] ?? '') ?></textarea>
                  </div>
                </div>
                <div>
                  <button type="submit" class="btn btn-primary">Profil Bilgilerini Kaydet</button>
                </div>
              </form>
            </div>
          </div>

          <div class="card bg-secondary border-0 shadow-sm mb-4">
            <div class="card-body p-4">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h4 mb-0">Takip Ettiğim Seriler</h2>
                <a class="btn btn-outline-light btn-sm" href="index.php">Serileri keşfet</a>
              </div>
              <?php if (!$followedSeries): ?>
                <p class="text-muted mb-0">Henüz hiçbir seriyi takip etmiyorsunuz. İlginizi çeken serileri keşfetmeye başlayın.</p>
              <?php else: ?>
                <div class="list-group list-group-flush">
                  <?php foreach ($followedSeries as $series): ?>
                    <?php
                      $cover = $series['cover_image'] ?? '';
                      $title = $series['title'] ?? '';
                      $initial = function_exists('mb_substr') ? mb_substr($title, 0, 1) : substr($title, 0, 1);
                      $followedAtRaw = $series['followed_at'] ?? null;
                      $followedAtFormatted = null;
                      if ($followedAtRaw) {
                          $timestamp = strtotime($followedAtRaw);
                          if ($timestamp !== false) {
                              $followedAtFormatted = date('d.m.Y H:i', $timestamp);
                          }
                      }
                    ?>
                    <a class="list-group-item list-group-item-action bg-dark text-light d-flex gap-3 align-items-center" href="manga.php?slug=<?= htmlspecialchars($series['slug']) ?>">
                      <?php if (!empty($cover)): ?>
                        <img src="<?= htmlspecialchars($cover) ?>" alt="<?= htmlspecialchars($title) ?>" class="rounded" width="56" height="80">
                      <?php else: ?>
                        <div class="rounded bg-secondary d-flex align-items-center justify-content-center" style="width: 56px; height: 80px;">
                          <span class="fw-semibold"><?= htmlspecialchars($initial) ?></span>
                        </div>
                      <?php endif; ?>
                      <div class="flex-grow-1">
                        <div class="fw-semibold"><?= htmlspecialchars($title) ?></div>
                        <?php if (!empty($series['status'])): ?>
                          <div class="small text-secondary text-uppercase">Durum: <?= htmlspecialchars($series['status']) ?></div>
                        <?php endif; ?>
                        <?php if ($followedAtFormatted): ?>
                          <div class="small text-muted">Takibe alındı: <?= htmlspecialchars($followedAtFormatted) ?></div>
                        <?php endif; ?>
                      </div>
                      <span class="badge bg-primary">Takip</span>
                    </a>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="card bg-secondary border-0 shadow-sm mb-4">
            <div class="card-body p-4">
              <div class="d-flex align-items-center justify-content-between mb-3">
                <h2 class="h4 mb-0">Okuma Geçmişi</h2>
              </div>
              <p id="reading-history-status" class="text-muted small mb-0">Okuma geçmişiniz yükleniyor...</p>
            </div>
            <ul id="reading-history-list" class="list-group list-group-flush"></ul>
          </div>

          <div class="card bg-secondary border-0 shadow-sm">
            <div class="card-body p-4">
              <h2 class="h4 mb-3">Parolayı Güncelle</h2>
              <form method="post" class="row g-3">
                <input type="hidden" name="action" value="change-password">
                <div class="col-md-6">
                  <label class="form-label">Mevcut Parola</label>
                  <input type="password" class="form-control" name="current_password" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Yeni Parola</label>
                  <input type="password" class="form-control" name="new_password" minlength="6" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Yeni Parola (Tekrar)</label>
                  <input type="password" class="form-control" name="new_password_confirmation" minlength="6" required>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn btn-outline-light">Parolayı Güncelle</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </main>

    <footer class="site-footer py-4">
      <div class="container d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
        <small><?= $footerText !== '' ? $footerText : htmlspecialchars($defaultFooter) ?></small>
        <?php if (!empty($footerMenuItems)): ?>
          <ul class="nav footer-menu">
            <?php foreach ($footerMenuItems as $item): ?>
              <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($item['url']) ?>" target="<?= htmlspecialchars($item['target']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>
      </div>
      <?php if (!empty($ads['footer'])): ?>
        <div class="container mt-3">
          <div class="ad-slot ad-slot--footer text-center">
            <?= $ads['footer'] ?>
          </div>
        </div>
      <?php endif; ?>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/theme.js"></script>
    <script>
      document.addEventListener('DOMContentLoaded', () => {
        const historyList = document.getElementById('reading-history-list');
        const historyStatus = document.getElementById('reading-history-status');

        if (!historyList || !historyStatus) {
          return;
        }

        const formatChapterNumber = (value) => {
          if (typeof value === 'number') {
            return Number.isInteger(value) ? value.toString() : value.toString();
          }
          const numeric = Number(value);
          if (!Number.isNaN(numeric)) {
            return Number.isInteger(numeric) ? numeric.toString() : value;
          }
          return value;
        };

        const formatDate = (value) => {
          if (!value) {
            return '';
          }
          const normalized = value.replace(' ', 'T');
          const date = new Date(normalized);
          if (Number.isNaN(date.getTime())) {
            return value;
          }
          return date.toLocaleString('tr-TR');
        };

        const renderHistory = (items) => {
          historyList.innerHTML = '';

          if (!items || items.length === 0) {
            historyStatus.textContent = 'Henüz okuma geçmişiniz yok.';
            historyStatus.classList.remove('text-danger');
            historyStatus.classList.add('text-muted');
            historyStatus.classList.remove('d-none');
            return;
          }

          historyStatus.classList.add('d-none');

          items.forEach((item) => {
            const li = document.createElement('li');
            li.className = 'list-group-item bg-transparent border-secondary text-light';

            const wrapper = document.createElement('div');
            wrapper.className = 'd-flex align-items-center gap-3';

            if (item.cover_image) {
              const image = document.createElement('img');
              image.src = item.cover_image;
              image.alt = item.manga_title || 'Kapak görseli';
              image.width = 56;
              image.height = 80;
              image.loading = 'lazy';
              image.className = 'rounded object-fit-cover flex-shrink-0';
              wrapper.appendChild(image);
            }

            const content = document.createElement('div');
            content.className = 'flex-grow-1';

            const chapterLink = document.createElement('a');
            const chapterNumber = formatChapterNumber(item.number);
            chapterLink.href = `chapter.php?slug=${encodeURIComponent(item.manga_slug)}&chapter=${encodeURIComponent(chapterNumber)}`;
            chapterLink.className = 'text-decoration-none text-light fw-semibold';
            const linkParts = [];
            linkParts.push(item.manga_title || 'Manga');
            if (chapterNumber !== '') {
              linkParts.push(`Bölüm ${chapterNumber}`);
            }
            if (item.chapter_title) {
              linkParts.push(item.chapter_title);
            }
            chapterLink.textContent = linkParts.join(' - ');

            const meta = document.createElement('div');
            meta.className = 'small text-muted';
            meta.textContent = `Son okuma: ${formatDate(item.last_read_at)}`;

            content.appendChild(chapterLink);
            content.appendChild(meta);

            wrapper.appendChild(content);
            li.appendChild(wrapper);
            historyList.appendChild(li);
          });
        };

        const showError = (message) => {
          historyStatus.textContent = message;
          historyStatus.classList.remove('text-muted');
          historyStatus.classList.add('text-danger');
          historyStatus.classList.remove('d-none');
          historyList.innerHTML = '';
        };

        fetch('api.php?action=reading-history&limit=10', { credentials: 'same-origin' })
          .then((response) => {
            if (!response.ok) {
              throw new Error('Okuma geçmişi alınamadı.');
            }
            return response.json();
          })
          .then((payload) => {
            if (!payload || !Array.isArray(payload.data)) {
              throw new Error('Beklenmeyen yanıt alındı.');
            }
            renderHistory(payload.data);
          })
          .catch((error) => {
            showError(error.message || 'Okuma geçmişi alınamadı.');
          });
      });
    </script>
  </body>
</html>
