<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/invoices.php';
require_once __DIR__ . '/../includes/payments.php';
require_business();

$id = (int) get('id', 0);
$p  = payment_get($id);

if (!$p) {
    flash_set('error', 'Payment not found.');
    redirect('pages/payments.php');
}

$invoice = invoice_get((int) $p['invoice_id']);
$paid    = payment_invoice_paid_total((int) $p['invoice_id']);
$balance = $invoice ? max(0.0, round((float) $invoice['total'] - $paid, 2)) : 0.0;

$page_title = 'Payment ' . $p['receipt_number'];
include __DIR__ . '/../includes/app-header.php';
?>

<div class="page__head">
  <div>
    <h1 class="page__title">Payment <?= e($p['receipt_number']) ?></h1>
    <p class="page__subtitle">
      <?= e($p['client_name']) ?> &middot; <?= e(date('j M Y', strtotime($p['payment_date']))) ?>
    </p>
  </div>
  <div class="page__actions">
    <a class="btn btn--ghost" href="<?= e(url('pages/payments.php')) ?>">
      <i class="fa-solid fa-arrow-left"></i> All payments
    </a>
    <a class="btn btn--ghost" href="<?= e(url('pages/receipt.php?id=' . $p['id'])) ?>" target="_blank">
      <i class="fa-solid fa-receipt"></i> Receipt
    </a>
    <a class="btn btn--ghost" href="<?= e(url('pages/payment-form.php?id=' . $p['id'])) ?>">
      <i class="fa-solid fa-pen"></i> Edit
    </a>
  </div>
</div>

<?php if (flash_has('success')): ?>
  <div class="flash flash--success">
    <i class="fa-solid fa-circle-check"></i>
    <span><?= e(flash_get('success')) ?></span>
  </div>
<?php endif; ?>
<?php if (flash_has('error')): ?>
  <div class="flash flash--error">
    <i class="fa-solid fa-circle-exclamation"></i>
    <span><?= e(flash_get('error')) ?></span>
  </div>
<?php endif; ?>

<div class="grid-3" style="margin-bottom:20px;">
  <div class="card"><div class="card__body">
    <div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Amount</div>
    <div style="font-family:var(--font-display);font-size:1.6rem;font-weight:700;margin-top:4px;">
      <?= e($p['invoice_currency']) ?> <?= e(number_format((float) $p['amount'], 2)) ?>
    </div>
  </div></div>
  <div class="card"><div class="card__body">
    <div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Method</div>
    <div style="font-family:var(--font-display);font-size:1.1rem;font-weight:700;margin-top:6px;">
      <?= e(PAYMENT_METHODS[$p['method']] ?? ucfirst($p['method'])) ?>
    </div>
  </div></div>
  <div class="card"><div class="card__body">
    <div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Invoice balance</div>
    <div style="font-family:var(--font-display);font-size:1.1rem;font-weight:700;margin-top:6px;">
      <?= e($p['invoice_currency']) ?> <?= e(number_format($balance, 2)) ?>
    </div>
  </div></div>
</div>

<div class="card" style="margin-bottom:20px;">
  <div class="card__head"><h2 class="card__title">Details</h2></div>
  <div class="card__body">
    <div class="grid-2">
      <div>
        <div class="muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;">Invoice</div>
        <div style="margin-top:4px;">
          <a href="<?= e(url('pages/invoice-view.php?id=' . $p['invoice_id'])) ?>">
            <?= e($p['invoice_number']) ?>
          </a>
        </div>
      </div>
      <div>
        <div class="muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;">Reference</div>
        <div style="margin-top:4px;"><?= $p['reference'] ? e($p['reference']) : '<span class="muted">—</span>' ?></div>
      </div>
      <div style="margin-top:16px;">
        <div class="muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;">Recorded</div>
        <div style="margin-top:4px;"><?= e(date('j M Y H:i', strtotime($p['created_at']))) ?></div>
      </div>
      <div style="margin-top:16px;">
        <div class="muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;">Updated</div>
        <div style="margin-top:4px;"><?= e(date('j M Y H:i', strtotime($p['updated_at']))) ?></div>
      </div>
    </div>

    <?php if ($p['notes']): ?>
      <div style="margin-top:20px;">
        <div class="muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;">Notes</div>
        <div style="margin-top:6px;white-space:pre-wrap;"><?= e($p['notes']) ?></div>
      </div>
    <?php endif; ?>
  </div>
</div>

<div class="card">
  <div class="card__head"><h2 class="card__title">Actions</h2></div>
  <div class="card__body">
    <div class="row" style="flex-wrap:wrap;gap:8px;">
      <a class="btn btn--ghost" href="<?= e(url('pages/receipt.php?id=' . $p['id'])) ?>" target="_blank">
        <i class="fa-solid fa-receipt"></i> View receipt
      </a>
      <a class="btn btn--ghost" href="<?= e(url('pages/payment-form.php?id=' . $p['id'])) ?>">
        <i class="fa-solid fa-pen"></i> Edit payment
      </a>
      <form method="post" action="<?= e(url('pages/payment-delete.php')) ?>" style="display:inline;margin-left:auto;"
            onsubmit="return confirm('Delete this payment? The invoice balance will be recalculated.');">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
        <button class="btn btn--danger"><i class="fa-solid fa-trash"></i> Delete payment</button>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/app-footer.php'; ?>