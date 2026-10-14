<?php
require_once __DIR__ . '/../includes/auth.php';
$page_title = 'Dashboard';
include __DIR__ . '/../includes/app-header.php';
?>

<div class="page__head">
  <div>
    <h1 class="page__title">Good to see you, <?= e(explode(' ', current_user()['name'])[0]) ?></h1>
  </div>
</div>

<div class="empty" style="margin-bottom:32px;">
  <div class="empty__icon"><i class="fa-solid fa-chart-line"></i></div>
  <h2 class="empty__title">No activity yet</h2>
  <p class="empty__text">Once you create your first invoice, your numbers will appear here.</p>
  <a class="btn btn--primary" href="#"><i class="fa-solid fa-plus"></i> Create invoice</a>
</div>

<div class="grid-4">
  <div class="card"><div class="card__body">
    <div class="muted" style="font-size:.8rem;">Total invoiced</div>
    <div style="font-family:var(--font-display);font-size:1.8rem;font-weight:700;margin-top:6px;">—</div>
  </div></div>
  <div class="card"><div class="card__body">
    <div class="muted" style="font-size:.8rem;">Paid</div>
    <div style="font-family:var(--font-display);font-size:1.8rem;font-weight:700;margin-top:6px;">—</div>
  </div></div>
  <div class="card"><div class="card__body">
    <div class="muted" style="font-size:.8rem;">Outstanding</div>
    <div style="font-family:var(--font-display);font-size:1.8rem;font-weight:700;margin-top:6px;">—</div>
  </div></div>
  <div class="card"><div class="card__body">
    <div class="muted" style="font-size:.8rem;">Clients</div>
    <div style="font-family:var(--font-display);font-size:1.8rem;font-weight:700;margin-top:6px;">—</div>
  </div></div>
</div>

<?php include __DIR__ . '/../includes/app-footer.php'; ?>