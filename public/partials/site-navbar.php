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
<nav class="px-4 mt-6">
  <div class="mx-auto max-w-7xl">
    <div class="site-navbar-shell rounded-3xl px-4 py-4 md:px-10 md:py-4 backdrop-blur flex items-center justify-between gap-4">
      <a class="flex items-center gap-3 text-white" href="/">
        <?php if (!empty($site['logo'])): ?>
          <img src="<?= htmlspecialchars($site['logo']) ?>" alt="<?= htmlspecialchars($site['name']) ?>" class="brand-logo">
        <?php endif; ?>
        <span class="text-lg font-semibold"><?= htmlspecialchars($site['name']) ?></span>
      </a>
      <div class="hidden md:flex items-center gap-6 text-sm">
        <ul class="flex items-center gap-3">
          <?php if (!empty($primaryMenuItems)): ?>
            <?php foreach ($primaryMenuItems as $item): ?>
              <li><a class="px-3 py-2 rounded-full text-muted hover:bg-white/10 transition" href="<?= htmlspecialchars($item['url']) ?>" target="<?= htmlspecialchars($item['target']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
            <?php endforeach; ?>
          <?php else: ?>
            <li><a class="px-3 py-2 rounded-full text-muted hover:bg-white/10 transition" href="/">Anasayfa</a></li>
          <?php endif; ?>
        </ul>
        <div class="flex items-center gap-2">
          <?php if (!empty($user)): ?>
            <span class="px-3 py-2 rounded-full bg-muted text-soft">Bakiye: <strong id="nav-ki-balance"><?= (int) ($user['ki_balance'] ?? 0) ?></strong> <?= htmlspecialchars($currencyName) ?></span>
            <a class="px-3 py-2 rounded-full text-muted hover:bg-white/10 transition" href="profile.php">Profilim</a>
            <?php $memberProfileUrl = 'member.php?u=' . urlencode($user['username']); ?>
            <a class="px-3 py-2 rounded-full text-muted hover:bg-white/10 transition" href="<?= htmlspecialchars($memberProfileUrl) ?>">Kamu Profili</a>
            <?php if (in_array($user['role'], ['admin', 'editor'], true)): ?>
              <a class="px-3 py-2 rounded-full text-muted hover:bg-white/10 transition" href="../admin/index.php">Yönetim</a>
            <?php endif; ?>
            <a class="px-3 py-2 rounded-full text-muted hover:bg-white/10 transition" href="logout.php">Çıkış Yap</a>
          <?php else: ?>
            <a class="px-3 py-2 rounded-full text-muted hover:bg-white/10 transition" href="login.php">Giriş Yap</a>
            <a class="px-3 py-2 rounded-full text-muted hover:bg-white/10 transition" href="register.php">Kayıt Ol</a>
          <?php endif; ?>
        </div>
        <?php if ($showSearchForm): ?>
          <form id="search-form" class="flex items-center gap-3 bg-muted rounded-full px-4 py-2" role="search">
            <span class="text-muted"><i class="bi bi-search"></i></span>
            <input type="search" id="search" class="w-full bg-transparent border-0 text-sm" placeholder="Manga ara...">
            <select id="status" class="bg-transparent border-0 text-sm text-muted">
              <option value="">Durum: Tümü</option>
              <option value="ongoing">Devam Ediyor</option>
              <option value="completed">Tamamlandı</option>
              <option value="hiatus">Ara Verildi</option>
            </select>
            <button class="btn btn-primary text-sm" type="submit">Ara</button>
          </form>
        <?php endif; ?>
      </div>
      <div class="flex items-center gap-3">
        <button class="btn btn-ghost nav-trigger" type="button" data-theme-toggle aria-label="Temayı değiştir">
          <i class="bi bi-moon-stars"></i>
        </button>
        <button class="nav-trigger md:hidden" type="button" data-nav-open aria-label="Menüyü aç">
          <i class="bi bi-list"></i>
        </button>
      </div>
    </div>
  </div>

  <div class="nav-drawer" aria-hidden="true" data-nav-drawer>
    <div class="nav-panel flex flex-col gap-5">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <?php if (!empty($site['logo'])): ?>
            <img src="<?= htmlspecialchars($site['logo']) ?>" alt="<?= htmlspecialchars($site['name']) ?>" class="brand-logo">
          <?php endif; ?>
          <span class="text-lg font-semibold"><?= htmlspecialchars($site['name']) ?></span>
        </div>
        <button class="nav-trigger" type="button" data-nav-close aria-label="Menüyü kapat">
          <i class="bi bi-x-lg"></i>
        </button>
      </div>
      <?php if ($showSearchForm): ?>
        <form id="search-form-mobile" class="flex flex-col gap-3 bg-muted rounded-2xl px-4 py-4" role="search">
          <div class="flex items-center gap-2">
            <span class="text-muted"><i class="bi bi-search"></i></span>
            <input type="search" id="search-mobile" class="w-full bg-transparent border-0" placeholder="Manga ara...">
          </div>
          <select id="status-mobile" class="bg-transparent border text-muted rounded-xl px-4 py-2">
            <option value="">Durum: Tümü</option>
            <option value="ongoing">Devam Ediyor</option>
            <option value="completed">Tamamlandı</option>
            <option value="hiatus">Ara Verildi</option>
          </select>
          <button class="btn btn-primary" type="submit">Ara</button>
        </form>
      <?php endif; ?>
      <div class="flex flex-col gap-4">
        <div class="flex flex-col gap-2">
          <span class="text-sm text-muted uppercase tracking-wide">Menü</span>
          <ul class="flex flex-col gap-2">
            <?php if (!empty($primaryMenuItems)): ?>
              <?php foreach ($primaryMenuItems as $item): ?>
                <li><a class="px-4 py-3 rounded-2xl bg-muted text-soft" href="<?= htmlspecialchars($item['url']) ?>" target="<?= htmlspecialchars($item['target']) ?>"><?= htmlspecialchars($item['label']) ?></a></li>
              <?php endforeach; ?>
            <?php else: ?>
              <li><a class="px-4 py-3 rounded-2xl bg-muted text-soft" href="/">Anasayfa</a></li>
            <?php endif; ?>
          </ul>
        </div>
        <div class="flex flex-col gap-2">
          <span class="text-sm text-muted uppercase tracking-wide">Hesap</span>
          <ul class="flex flex-col gap-2">
            <?php if (!empty($user)): ?>
              <li class="px-4 py-3 rounded-2xl bg-muted text-soft">Bakiye: <strong id="nav-ki-balance-mobile"><?= (int) ($user['ki_balance'] ?? 0) ?></strong> <?= htmlspecialchars($currencyName) ?></li>
              <li><a class="px-4 py-3 rounded-2xl bg-muted text-soft" href="profile.php">Profilim</a></li>
              <?php $memberProfileUrl = 'member.php?u=' . urlencode($user['username']); ?>
              <li><a class="px-4 py-3 rounded-2xl bg-muted text-soft" href="<?= htmlspecialchars($memberProfileUrl) ?>">Kamu Profili</a></li>
              <?php if (in_array($user['role'], ['admin', 'editor'], true)): ?>
                <li><a class="px-4 py-3 rounded-2xl bg-muted text-soft" href="../admin/index.php">Yönetim</a></li>
              <?php endif; ?>
              <li><a class="px-4 py-3 rounded-2xl bg-muted text-soft" href="logout.php">Çıkış Yap</a></li>
            <?php else: ?>
              <li><a class="px-4 py-3 rounded-2xl bg-muted text-soft" href="login.php">Giriş Yap</a></li>
              <li><a class="px-4 py-3 rounded-2xl bg-muted text-soft" href="register.php">Kayıt Ol</a></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>
  </div>
</nav>

<script>
  (function () {
    const drawer = document.querySelector('[data-nav-drawer]');
    const openers = document.querySelectorAll('[data-nav-open]');
    const closers = document.querySelectorAll('[data-nav-close]');
    if (!drawer) {
      return;
    }
    function setOpen(nextState) {
      drawer.setAttribute('aria-hidden', nextState ? 'false' : 'true');
      document.body.classList.toggle('overflow-hidden', nextState);
    }
    openers.forEach((button) => button.addEventListener('click', () => setOpen(true)));
    closers.forEach((button) => button.addEventListener('click', () => setOpen(false)));
    drawer.addEventListener('click', (event) => {
      if (event.target === drawer) {
        setOpen(false);
      }
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && drawer.getAttribute('aria-hidden') === 'false') {
        setOpen(false);
      }
    });

    const searchDesktop = document.getElementById('search');
    const searchMobile = document.getElementById('search-mobile');
    if (searchDesktop && searchMobile) {
      searchDesktop.addEventListener('input', () => {
        searchMobile.value = searchDesktop.value;
      });
      searchMobile.addEventListener('input', () => {
        searchDesktop.value = searchMobile.value;
      });
    }
    const statusDesktop = document.getElementById('status');
    const statusMobile = document.getElementById('status-mobile');
    if (statusDesktop && statusMobile) {
      statusDesktop.addEventListener('change', () => {
        statusMobile.value = statusDesktop.value;
      });
      statusMobile.addEventListener('change', () => {
        statusDesktop.value = statusMobile.value;
      });
    }
    const formDesktop = document.getElementById('search-form');
    const formMobile = document.getElementById('search-form-mobile');
    if (formDesktop && formMobile) {
      formMobile.addEventListener('submit', (event) => {
        event.preventDefault();
        setOpen(false);
        formDesktop.dispatchEvent(new Event('submit', { cancelable: true, bubbles: true }));
      });
    }
  })();
</script>
