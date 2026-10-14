<?php
require_once __DIR__ . '/../includes/auth.php';
$page_title = 'Invoices';
include __DIR__ . '/../includes/app-header.php';
?>

<div class="page__head">
  <div>
    <h1 class="page__title">Invoices</h1>
    <p class="page__subtitle">Every invoice you've sent, all in one place.</p>
  </div>
  <div class="page__actions">
    <a class="btn btn--primary" href="#"><i class="fa-solid fa-plus"></i> New invoice</a>
  </div>
</div>

<div class="empty">
  <div class="empty__icon"><i class="fa-solid fa-file-invoice"></i></div>
  <h2 class="empty__title">No invoices yet</h2>
  <p class="empty__text">Your invoices will live here. Create your first one to get started.</p>
  <a class="btn btn--primary" href="#"><i class="fa-solid fa-plus"></i> Create invoice</a>
</div>

<?php include __DIR__ . '/../includes/app-footer.php'; ?>