<?php
require_once __DIR__ . '/../includes/auth.php';
$page_title = 'Payments';
include __DIR__ . '/../includes/app-header.php';
?>
<div class="page__head">
  <div>
    <h1 class="page__title">Payments</h1>
    <p class="page__subtitle">Every payment received against your invoices.</p>
  </div>
</div>
<div class="empty">
  <div class="empty__icon"><i class="fa-solid fa-money-bill-wave"></i></div>
  <h2 class="empty__title">No payments recorded</h2>
  <p class="empty__text">Payments will appear here as your clients pay their invoices.</p>
</div>
<?php include __DIR__ . '/../includes/app-footer.php'; ?>