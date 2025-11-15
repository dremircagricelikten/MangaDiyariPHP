<?php
/** @var string $pageTitle */
/** @var string|null $pageSubtitle */
/** @var array<int, array<string, string>> $headerActions */
/** @var array $user */
?>
<header class="admin-header d-flex flex-wrap justify-content-between align-items-center gap-3">
  <div>
    <div class="text-uppercase small text-muted">Hoş geldin, <?= htmlspecialchars($user['username']) ?></div>
    <h1 class="h3 mb-1"><?= htmlspecialchars($pageTitle) ?></h1>
    <?php if (!empty($pageSubtitle)): ?>
      <p class="text-muted mb-0"><?= htmlspecialchars($pageSubtitle) ?></p>
    <?php endif; ?>
  </div>
  <?php if (!empty($headerActions)): ?>
    <div class="d-flex flex-wrap gap-2">
      <?php foreach ($headerActions as $action): ?>
        <?php $attributes = trim($action['attributes'] ?? ''); ?>
        <a class="btn <?= htmlspecialchars($action['class'] ?? 'btn-outline-light btn-sm') ?>" href="<?= htmlspecialchars($action['href']) ?>" <?= $attributes !== '' ? $attributes . ' ' : '' ?>>
          <?php if (!empty($action['icon'])): ?><i class="<?= htmlspecialchars($action['icon']) ?> me-1"></i><?php endif; ?>
          <?= htmlspecialchars($action['label']) ?>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</header>
