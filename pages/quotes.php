<?php
require_once __DIR__ . '/../includes/auth.php';
$page_title = 'Quotes';
include __DIR__ . '/../includes/app-header.php';
?>
<div class="page__head">
  <div>
    <h1 class="page__title">Quotes</h1>
    <p class="page__subtitle">Send a quote, win the job, convert to an invoice.</p>
  </div>
  <div class="page__actions">
    <a class="btn btn--primary" href="#"><i class="fa-solid fa-plus"></i> New quote</a>
  </div>
</div>
<div class="empty">
  <div class="empty__icon"><i class="fa-solid fa-file-lines"></i></div>
  <h2 class="empty__title">No quotes yet</h2>
  <p class="empty__text">Quotes you create will show up here until the client accepts them.</p>
</div>
<?php include __DIR__ . '/../includes/app-footer.php'; ?>