<?php
/** @var array $site */
/** @var array $user */
?>
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
  <nav class="sidebar-nav">
    <ul class="menu-block">
      <li class="menu-heading">Genel</li>
      <li><a class="menu-link <?= admin_nav_active('index') ?>" href="index.php"><i class="bi bi-speedometer2"></i><span>Gösterge Paneli</span></a></li>
      <li><a class="menu-link <?= admin_nav_active('manga') ?>" href="manga.php"><i class="bi bi-book-half"></i><span>Manga Yönetimi</span></a></li>
      <li><a class="menu-link <?= admin_nav_active('chapters') ?>" href="chapters.php"><i class="bi bi-collection"></i><span>Bölüm Yönetimi</span></a></li>
      <li><a class="menu-link <?= admin_nav_active('market') ?>" href="market.php"><i class="bi bi-shop"></i><span>Market Yönetimi</span></a></li>
    </ul>
    <ul class="menu-block">
      <li class="menu-heading">Görünüm</li>
      <li><a class="menu-link <?= admin_nav_active('appearance') ?>" href="appearance.php"><i class="bi bi-palette"></i><span>Tema Ayarları</span></a></li>
      <li><a class="menu-link <?= admin_nav_active('widgets') ?>" href="widgets.php"><i class="bi bi-stars"></i><span>Ana Sayfa Widgetları</span></a></li>
      <li><a class="menu-link <?= admin_nav_active('menus') ?>" href="menus.php"><i class="bi bi-menu-button"></i><span>Menü Yönetimi</span></a></li>
      <li><a class="menu-link <?= admin_nav_active('pages') ?>" href="pages.php"><i class="bi bi-file-earmark-text"></i><span>Sayfalar</span></a></li>
    </ul>
    <ul class="menu-block">
      <li class="menu-heading">Topluluk</li>
      <li><a class="menu-link <?= admin_nav_active('community') ?>" href="community.php"><i class="bi bi-people"></i><span>Üyeler &amp; Reklam</span></a></li>
      <li><a class="menu-link <?= admin_nav_active('integrations') ?>" href="integrations.php"><i class="bi bi-plug"></i><span>Entegrasyonlar</span></a></li>
    </ul>
    <ul class="menu-block">
      <li class="menu-heading">Yapılandırma</li>
      <li><a class="menu-link <?= admin_nav_active('settings') ?>" href="settings.php"><i class="bi bi-gear"></i><span>Site Ayarları</span></a></li>
    </ul>
  </nav>
  <div class="sidebar-footer d-flex flex-column gap-2">
    <a href="../public/index.php" class="btn btn-outline-light btn-sm w-100"><i class="bi bi-box-arrow-up-right me-1"></i> Siteyi Görüntüle</a>
    <a href="logout.php" class="btn btn-danger btn-sm w-100"><i class="bi bi-box-arrow-right me-1"></i> Çıkış Yap</a>
  </div>
</aside>
