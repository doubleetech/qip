<?php
require_once __DIR__ . '/../includes/auth.php';
http_response_code(404);
$page_title = 'Not found';
include __DIR__ . '/../includes/app-header.php';
?>

<div class="empty" style="margin-top:48px;">
  <div class="empty__icon"><i class="fa-solid fa-compass"></i></div>
  <h2 class="empty__title">Page not found</h2>
  <p class="empty__text">The page you're looking for doesn't exist or has moved.</p>
  <a class="btn btn--primary" href="<?= e(url('pages/dashboard.php')) ?>">
    <i class="fa-solid fa-house"></i> Back to dashboard
  </a>
</div>

<?php include __DIR__ . '/../includes/app-footer.php'; ?>