<?php
require_once __DIR__ . '/auth.php';
$biz = current_business();
$current = current_page();

$nav = [
  ['label' => 'Overview', 'items' => [
    ['href' => 'pages/dashboard.php', 'icon' => 'fa-gauge-high',  'text' => 'Dashboard'],
  ]],
  ['label' => 'Billing', 'items' => [
    ['href' => 'pages/invoices.php', 'icon' => 'fa-file-invoice', 'text' => 'Invoices'],
    ['href' => 'pages/quotes.php',   'icon' => 'fa-file-lines',   'text' => 'Quotes'],
    ['href' => 'pages/payments.php', 'icon' => 'fa-money-bill-wave', 'text' => 'Payments'],
    ['href' => 'pages/expenses.php', 'icon' => 'fa-receipt',      'text' => 'Expenses'],
  ]],
  ['label' => 'Directory', 'items' => [
    ['href' => 'pages/clients.php', 'icon' => 'fa-users', 'text' => 'Clients'],
  ]],
  ['label' => 'Account', 'items' => [
    ['href' => 'pages/settings.php', 'icon' => 'fa-gear', 'text' => 'Settings'],
  ]],
];

$initial = $biz ? strtoupper(substr($biz['business_name'], 0, 1)) : 'Q';
?>
<aside class="sidebar" id="sidebar">
  <a class="sidebar__brand" href="<?= e(url('pages/dashboard.php')) ?>">
    <span class="sidebar__logo">Q</span>
    <span>QIP</span>
  </a>

  <?php if ($biz): ?>
    <div class="sidebar__biz">
      <div class="sidebar__biz-avatar"><?= e($initial) ?></div>
      <div class="sidebar__biz-info">
        <div class="sidebar__biz-name"><?= e($biz['business_name']) ?></div>
        <div class="sidebar__biz-meta"><?= e($biz['default_currency']) ?></div>
      </div>
    </div>
  <?php endif; ?>

  <nav class="sidebar__nav" aria-label="Primary">
    <?php foreach ($nav as $group): ?>
      <div class="sidebar__section"><?= e($group['label']) ?></div>
      <?php foreach ($group['items'] as $item):
        $isActive = str_ends_with($item['href'], $current);
      ?>
        <a class="sidebar__link<?= $isActive ? ' is-active' : '' ?>"
           href="<?= e(url($item['href'])) ?>"
           <?= $isActive ? 'aria-current="page"' : '' ?>>
          <i class="fa-solid <?= e($item['icon']) ?>" aria-hidden="true"></i>
          <span><?= e($item['text']) ?></span>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar__foot">
    <a class="sidebar__link" href="<?= e(url('logout.php')) ?>">
      <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
      <span>Sign out</span>
    </a>
  </div>
</aside>