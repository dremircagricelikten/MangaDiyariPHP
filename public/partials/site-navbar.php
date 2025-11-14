<?php
/** @var array $site */
/** @var array $menus */
/** @var array|null $user */
/** @var array|null $kiSettings */
/** @var bool $showSearchForm */

$primaryMenuItems = $menus['primary']['items'] ?? [];
$currencyName = $kiSettings['currency_name'] ?? 'Ki';
$showSearchForm = $showSearchForm ?? false;
?>
<nav class="navbar navbar-expand-lg site-navbar shadow-sm">
  <div class="container-xxl">
    <a class="navbar-brand d-flex align-items-center gap-2" href="/">
      <?php if (!empty($site['logo'])): ?>
        <img src="<?= htmlspecialchars($site['logo']) ?>" alt="<?= htmlspecialchars($site['name']) ?>" class="brand-logo">
      <?php endif; ?>
      <span class="brand-text"><?= htmlspecialchars($site['name']) ?></span>
    </a>
    <div class="d-flex align-items-center gap-2 order-lg-3">
      <button class="btn btn-outline-light btn-sm btn-theme-toggle" type="button" data-theme-toggle aria-label="Temayı değiştir">
        <i class="bi bi-moon-stars"></i>
      </button>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-controls="navbarContent" aria-expanded="false" aria-label="Menüyü aç">
        <span class="navbar-toggler-icon"></span>
      </button>
    </div>
    <div class="collapse navbar-collapse order-lg-2" id="navbarContent">
      <ul class="navbar-nav me-lg-auto mb-3 mb-lg-0 align-items-lg-center gap-lg-1">
        <?php if (!empty($primaryMenuItems)): ?>
          <?php foreach ($primaryMenuItems as $item): ?>
            <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($item['url']) ?>" target="<?= htmlspecialchars($item['target']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
          <?php endforeach; ?>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="/">Anasayfa</a></li>
        <?php endif; ?>
      </ul>
      <ul class="navbar-nav ms-lg-3 mb-3 mb-lg-0 align-items-lg-center gap-lg-1 nav-user-links">
        <?php if (!empty($user)): ?>
          <li class="nav-item"><span class="nav-link">Bakiye: <strong id="nav-ki-balance"><?= (int) ($user['ki_balance'] ?? 0) ?></strong> <?= htmlspecialchars($currencyName) ?></span></li>
          <?php $memberProfileUrl = 'member.php?u=' . urlencode($user['username']); ?>
          <li class="nav-item"><a class="nav-link" href="profile.php">Profilim</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= htmlspecialchars($memberProfileUrl) ?>">Kamu Profili</a></li>
          <?php if (in_array($user['role'], ['admin', 'editor'], true)): ?>
            <li class="nav-item"><a class="nav-link" href="../admin/index.php">Yönetim</a></li>
          <?php endif; ?>
          <li class="nav-item"><a class="nav-link" href="logout.php">Çıkış Yap</a></li>
        <?php else: ?>
          <li class="nav-item"><a class="nav-link" href="login.php">Giriş Yap</a></li>
          <li class="nav-item"><a class="nav-link" href="register.php">Kayıt Ol</a></li>
        <?php endif; ?>
      </ul>
      <?php if ($showSearchForm): ?>
        <form id="search-form" class="navbar-search d-lg-flex align-items-center gap-2 ms-lg-4" role="search">
          <span class="search-icon"><i class="bi bi-search"></i></span>
          <input type="search" id="search" class="form-control" placeholder="Manga ara...">
          <select id="status" class="form-select">
            <option value="">Durum: Tümü</option>
            <option value="ongoing">Devam Ediyor</option>
            <option value="completed">Tamamlandı</option>
            <option value="hiatus">Ara Verildi</option>
          </select>
          <button class="btn btn-primary" type="submit">Ara</button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</nav>
