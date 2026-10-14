<?php
require_once __DIR__ . '/../includes/auth.php';
$page_title = 'Clients';
include __DIR__ . '/../includes/app-header.php';
?>
<div class="page__head">
  <div>
    <h1 class="page__title">Clients</h1>
    <p class="page__subtitle">The people and businesses you bill.</p>
  </div>
  <div class="page__actions">
    <a class="btn btn--primary" href="#"><i class="fa-solid fa-plus"></i> Add client</a>
  </div>
</div>
<div class="empty">
  <div class="empty__icon"><i class="fa-solid fa-users"></i></div>
  <h2 class="empty__title">No clients yet</h2>
  <p class="empty__text">Add your first client to start sending invoices.</p>
</div>
<?php include __DIR__ . '/../includes/app-footer.php'; ?>