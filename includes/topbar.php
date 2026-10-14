<?php
require_once __DIR__ . '/auth.php';
$user = current_user();
$title = $page_title ?? 'Dashboard';
$initials = '';
if ($user && !empty($user['name'])) {
    $parts = preg_split('/\s+/', trim($user['name']));
    $initials = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? '', 0, 1));
}
$firstName = $user && !empty($user['name']) ? explode(' ', trim($user['name']))[0] : '';
?>
<header class="topbar">
  <button class="topbar__menu" type="button" id="sidebar-toggle" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false">
    <i class="fa-solid fa-bars" aria-hidden="true"></i>
  </button>

  <a class="topbar__brand" href="<?= e(url('pages/dashboard.php')) ?>">
    <span class="topbar__brand-logo">Q</span>
    <span class="topbar__brand-text">QIP</span>
  </a>

  <div class="topbar__divider" aria-hidden="true"></div>

  <h1 class="topbar__title"><?= e($title) ?></h1>
  <div class="topbar__spacer"></div>

  <?php if ($user): ?>
    <button class="topbar__user" type="button" id="user-menu-toggle"
            aria-haspopup="menu" aria-expanded="false"
            aria-label="Account menu">
      <span class="topbar__avatar"><?= e($initials ?: 'Q') ?></span>
      <span class="topbar__user-name"><?= e($firstName) ?></span>
      <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
    </button>

    <div class="user-menu" id="user-menu" role="menu" hidden>
      <div class="user-menu__head">
        <div class="user-menu__name"><?= e($user['name']) ?></div>
        <div class="user-menu__email"><?= e($user['email']) ?></div>
      </div>
      <a class="user-menu__item" href="<?= e(url('pages/settings.php')) ?>" role="menuitem">
        <i class="fa-solid fa-gear" aria-hidden="true"></i>
        <span>Settings</span>
      </a>
      <a class="user-menu__item user-menu__item--danger" href="<?= e(url('logout.php')) ?>" role="menuitem">
        <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
        <span>Sign out</span>
      </a>
    </div>
  <?php endif; ?>
</header>