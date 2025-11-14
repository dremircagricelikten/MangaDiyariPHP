<?php
require_once __DIR__ . '/../src/Auth.php';
require_once __DIR__ . '/../src/Database.php';
require_once __DIR__ . '/../src/SettingRepository.php';
require_once __DIR__ . '/../src/SiteContext.php';

use MangaDiyari\Core\Auth;
use MangaDiyari\Core\Database;
use MangaDiyari\Core\SettingRepository;
use MangaDiyari\Core\SiteContext;

Auth::start();
$user = Auth::user();

$slug = $_GET['slug'] ?? '';
$chapterNumber = $_GET['chapter'] ?? '';

$context = SiteContext::build();
$site = $context['site'];
$menus = $context['menus'];
$ads = $context['ads'];
$analytics = $context['analytics'];

$pdo = Database::getConnection();
$settingRepo = new SettingRepository($pdo);
$allSettings = $settingRepo->all();
$themeDefaults = [
    'primary_color' => '#5f2c82',
    'accent_color' => '#49a09d',
    'background_color' => '#05060c',
    'gradient_start' => '#5f2c82',
    'gradient_end' => '#49a09d',
];
$theme = array_replace($themeDefaults, $allSettings);
$footerText = trim((string) ($allSettings['site_footer'] ?? ''));
$defaultFooter = '© ' . date('Y') . ' ' . $site['name'] . '. Tüm hakları saklıdır.';
$kiSettings = [
    'currency_name' => $allSettings['ki_currency_name'] ?? 'Ki',
    'comment_reward' => (int) ($allSettings['ki_comment_reward'] ?? 0),
    'reaction_reward' => (int) ($allSettings['ki_reaction_reward'] ?? 0),
    'chat_reward_per_minute' => (int) ($allSettings['ki_chat_reward_per_minute'] ?? 0),
    'read_reward_per_minute' => (int) ($allSettings['ki_read_reward_per_minute'] ?? 0),
];

$primaryMenuItems = $menus['primary']['items'] ?? [];
$footerMenuItems = $menus['footer']['items'] ?? [];
?>
<!doctype html>
<html lang="tr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($site['name']) ?> - Bölüm Okuma</title>
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
  <body class="bg-dark text-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient">
      <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="/">
          <?php if (!empty($site['logo'])): ?>
            <img src="<?= htmlspecialchars($site['logo']) ?>" alt="<?= htmlspecialchars($site['name']) ?>" class="brand-logo">
          <?php endif; ?>
          <span><?= htmlspecialchars($site['name']) ?></span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
          <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
            <?php if (!empty($primaryMenuItems)): ?>
              <?php foreach ($primaryMenuItems as $item): ?>
                <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($item['url']) ?>" target="<?= htmlspecialchars($item['target']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
              <?php endforeach; ?>
            <?php endif; ?>
            <?php if ($user): ?>
              <li class="nav-item"><span class="nav-link">Bakiye: <strong id="nav-ki-balance"><?= (int) ($user['ki_balance'] ?? 0) ?></strong> <?= htmlspecialchars($kiSettings['currency_name']) ?></span></li>
            <?php endif; ?>
            <?php if ($user && in_array($user['role'], ['admin', 'editor'], true)): ?>
              <li class="nav-item"><a class="nav-link" href="../admin/index.php">Yönetim</a></li>
            <?php endif; ?>
            <?php if ($user): ?>
              <?php $memberProfileUrl = 'member.php?u=' . urlencode($user['username']); ?>
              <li class="nav-item"><a class="nav-link" href="profile.php">Profilim</a></li>
              <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($memberProfileUrl) ?>">Kamu Profili</a></li>
              <li class="nav-item"><a class="nav-link" href="logout.php">Çıkış Yap</a></li>
            <?php else: ?>
              <li class="nav-item"><a class="nav-link" href="login.php">Giriş Yap</a></li>
              <li class="nav-item"><a class="nav-link" href="register.php">Kayıt Ol</a></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </nav>

    <?php if (!empty($ads['header'])): ?>
      <section class="ad-slot ad-slot--header py-3">
        <div class="container">
          <div class="ad-wrapper text-center">
            <?= $ads['header'] ?>
          </div>
        </div>
      </section>
    <?php endif; ?>

    <main class="container my-4" id="chapter-reader" data-slug="<?= htmlspecialchars($slug) ?>" data-chapter="<?= htmlspecialchars($chapterNumber) ?>">
      <div class="row g-4">
        <div class="<?php if (!empty($ads['sidebar'])): ?>col-lg-9<?php else: ?>col-12<?php endif; ?>">
          <div id="reader-toolbar" class="reader-toolbar mb-4">
            <div class="reader-toolbar__section">
              <label class="form-label form-label-sm mb-1" for="reader-mode">Okuma Biçimi</label>
              <select id="reader-mode" class="form-select form-select-sm">
                <option value="scroll">Uzun Kaydırma</option>
                <option value="fit">Genişlik Uyumlu</option>
              </select>
            </div>
            <div class="reader-toolbar__progress">
              <div class="d-flex justify-content-between align-items-center mb-1">
                <span class="small text-uppercase text-secondary">Bölüm İlerlemesi</span>
                <strong class="reader-toolbar__progress-value" id="reader-progress-value">0%</strong>
              </div>
              <div class="progress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-label="Okuma ilerlemesi">
                <div class="progress-bar" id="reader-progress-bar" style="width: 0%"></div>
              </div>
            </div>
            <div class="reader-toolbar__actions">
              <button class="btn btn-outline-light btn-sm" type="button" id="toggle-reader-toolbar"><i class="bi bi-eye-slash me-1"></i>Çubuğu Gizle</button>
            </div>
          </div>
          <button type="button" class="btn btn-outline-light btn-sm reader-toolbar-restore d-none" id="reader-toolbar-restore"><i class="bi bi-eye me-1"></i>Okuma çubuğunu göster</button>

          <div id="ki-balance-banner" class="alert alert-secondary d-flex justify-content-between align-items-center gap-3 mb-4">
            <div>
              <strong>Toplam <?= htmlspecialchars($kiSettings['currency_name']) ?>:</strong>
              <span id="ki-balance-value" data-currency="<?= htmlspecialchars($kiSettings['currency_name']) ?>"><?= $user['ki_balance'] ?? 0 ?></span>
            </div>
            <button class="btn btn-outline-light btn-sm" type="button" id="open-ki-modal">Kazançları Görüntüle</button>
          </div>
          <div id="ki-context-details" class="alert alert-info d-none"></div>
          <div class="reader-header mb-4">
            <h1 class="h3" id="chapter-title"></h1>
            <div class="d-flex flex-wrap gap-2 align-items-center">
              <a class="btn btn-outline-light btn-sm" id="prev-chapter" href="#">Önceki</a>
              <a class="btn btn-outline-light btn-sm" id="next-chapter" href="#">Sonraki</a>
              <select class="form-select form-select-sm w-auto" id="chapter-select"></select>
            </div>
          </div>
          <div id="chapter-lock-state" class="mb-4"></div>
          <article class="chapter-content" id="chapter-content"></article>
          <section id="comment-section" class="mt-5" data-currency="<?= htmlspecialchars($kiSettings['currency_name']) ?>">
            <div class="d-flex justify-content-between align-items-center mb-3">
              <h2 class="h4 mb-0">Yorumlar</h2>
              <button class="btn btn-outline-light btn-sm" id="refresh-comments" type="button">Yenile</button>
            </div>
            <?php if ($user): ?>
            <form id="comment-form" class="mb-3">
              <div class="mb-2">
                <textarea class="form-control" name="body" rows="3" placeholder="Düşüncelerini paylaş..." required></textarea>
              </div>
              <input type="hidden" name="chapter_id" id="comment-chapter-id" value="">
              <input type="hidden" name="manga_id" id="comment-manga-id" value="">
              <div class="d-flex justify-content-between align-items-center gap-3">
                <small class="text-secondary">Yorum başına <?= (int) $kiSettings['comment_reward'] ?> <?= htmlspecialchars($kiSettings['currency_name']) ?> kazanırsınız.</small>
                <button class="btn btn-primary" type="submit">Yorumu Gönder</button>
              </div>
            </form>
            <?php else: ?>
            <div class="alert alert-warning">Yorum yapmak için <a class="alert-link" href="login.php">giriş yapın</a>.</div>
            <?php endif; ?>
            <div id="comment-list" class="list-group"></div>
          </section>
        </div>
        <?php if (!empty($ads['sidebar'])): ?>
          <aside class="col-lg-3">
            <div class="ad-slot ad-slot--sidebar sticky-lg-top">
              <?= $ads['sidebar'] ?>
            </div>
          </aside>
        <?php endif; ?>
      </div>
    </main>

    <div id="site-chat-widget" class="chat-widget minimized" data-page="chapter">
      <div class="chat-header">
        <strong>Sohbet</strong>
        <button class="btn-close btn-close-white" type="button" aria-label="Kapat"></button>
      </div>
      <div class="chat-body">
        <div class="chat-messages" id="chat-messages"></div>
      </div>
      <div class="chat-footer">
        <?php if ($user): ?>
          <form id="chat-form" class="d-flex gap-2">
            <input type="text" class="form-control" name="message" placeholder="Mesaj yaz..." autocomplete="off" required>
            <button class="btn btn-primary" type="submit">Gönder</button>
          </form>
        <?php else: ?>
          <div class="text-center small">Sohbete katılmak için <a href="login.php">giriş yapın</a>.</div>
        <?php endif; ?>
      </div>
      <button class="chat-toggle btn btn-primary rounded-circle" type="button" aria-label="Sohbeti aç">💬</button>
    </div>

    <footer class="py-4 bg-black text-secondary">
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

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
      window.currentUser = <?= json_encode($user ? [
          'id' => $user['id'],
          'username' => $user['username'],
          'ki_balance' => $user['ki_balance'] ?? 0,
      ] : null, JSON_UNESCAPED_UNICODE) ?>;
      window.kiSettings = <?= json_encode($kiSettings, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="assets/chat.js"></script>
    <script src="assets/chapter.js"></script>
  </body>
</html>
