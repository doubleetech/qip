<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/invoices.php';
require_once __DIR__ . '/../includes/payments.php';
require_business();

$id = (int) get('id', 0);
$inv = invoice_get($id);

if (!$inv) {
    flash_set('error', 'Invoice not found.');
    redirect('pages/invoices.php');
}

$items   = invoice_items($id);
$paid    = invoice_paid_total($id);
$balance = max(0.0, round((float) $inv['total'] - $paid, 2));
$eff     = invoice_effective_status($inv, $paid);
$history = payments_for_invoice($id);
$canPay  = !in_array($inv['status'], ['draft', 'cancelled'], true) && $balance > 0.0001;

$page_title = $inv['number'];
include __DIR__ . '/../includes/app-header.php';
?>

<div class="page__head">
  <div>
    <h1 class="page__title"><?= e($inv['number']) ?></h1>
    <p class="page__subtitle">
      For <?= e($inv['client_name']) ?> &middot; Issued <?= e(date('j M Y', strtotime($inv['issue_date']))) ?>
    </p>
  </div>
  <div class="page__actions">
    <a class="btn btn--ghost" href="<?= e(url('pages/invoices.php')) ?>">
      <i class="fa-solid fa-arrow-left"></i> All invoices
    </a>
    <a class="btn btn--ghost" href="<?= e(url('pages/invoice-preview.php?id=' . $inv['id'])) ?>" target="_blank">
      <i class="fa-solid fa-print"></i> Preview
    </a>
    <a class="btn btn--ghost" href="<?= e(url('pages/invoice-form.php?id=' . $inv['id'])) ?>">
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

<div class="grid-4" style="margin-bottom:20px;">
  <div class="card"><div class="card__body">
    <div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Status</div>
    <div style="margin-top:8px;"><?= doc_status_badge('invoice', $eff) ?></div>
  </div></div>
  <div class="card"><div class="card__body">
    <div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Total</div>
    <div style="font-family:var(--font-display);font-size:1.35rem;font-weight:700;margin-top:4px;">
      <?= e($inv['currency']) ?> <?= e(number_format((float) $inv['total'], 2)) ?>
    </div>
  </div></div>
  <div class="card"><div class="card__body">
    <div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Paid</div>
    <div style="font-family:var(--font-display);font-size:1.35rem;font-weight:700;margin-top:4px;">
      <?= e($inv['currency']) ?> <?= e(number_format($paid, 2)) ?>
    </div>
  </div></div>
  <div class="card"><div class="card__body">
    <div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Balance</div>
    <div style="font-family:var(--font-display);font-size:1.35rem;font-weight:700;margin-top:4px;">
      <?= e($inv['currency']) ?> <?= e(number_format($balance, 2)) ?>
    </div>
  </div></div>
</div>

<div class="card" style="margin-bottom:20px;">
  <div class="card__head"><h2 class="card__title">Actions</h2></div>
  <div class="card__body">
    <div class="row" style="flex-wrap:wrap;gap:8px;">

      <?php if ($canPay): ?>
        <a class="btn btn--primary" href="<?= e(url('pages/payment-form.php?invoice_id=' . $inv['id'])) ?>">
          <i class="fa-solid fa-money-bill-wave"></i> Record payment
        </a>
      <?php endif; ?>

      <?php if ($inv['status'] === 'draft'): ?>
        <form method="post" action="<?= e(url('pages/invoice-status.php')) ?>" style="display:inline;">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $inv['id'] ?>">
          <input type="hidden" name="status" value="sent">
          <button class="btn btn--ghost"><i class="fa-solid fa-paper-plane"></i> Mark as sent</button>
        </form>
      <?php endif; ?>

      <?php if (in_array($inv['status'], ['sent', 'pending'], true)): ?>
        <form method="post" action="<?= e(url('pages/invoice-status.php')) ?>" style="display:inline;"
              onsubmit="return confirm('Cancel this invoice?');">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $inv['id'] ?>">
          <input type="hidden" name="status" value="cancelled">
          <button class="btn btn--danger"><i class="fa-solid fa-ban"></i> Cancel</button>
        </form>
      <?php endif; ?>

      <form method="post" action="<?= e(url('pages/invoice-duplicate.php')) ?>" style="display:inline;">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $inv['id'] ?>">
        <button class="btn btn--ghost"><i class="fa-regular fa-copy"></i> Duplicate</button>
      </form>

      <?php if ($inv['status'] === 'draft'): ?>
        <form method="post" action="<?= e(url('pages/invoice-delete.php')) ?>" style="display:inline;margin-left:auto;"
              onsubmit="return confirm('Delete this draft invoice? This cannot be undone.');">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $inv['id'] ?>">
          <button class="btn btn--danger"><i class="fa-solid fa-trash"></i> Delete</button>
        </form>
      <?php endif; ?>

    </div>
  </div>
</div>

<div class="card" style="margin-bottom:20px;">
  <div class="card__head">
    <h2 class="card__title">Payment history</h2>
    <?php if ($canPay): ?>
      <div style="margin-left:auto;">
        <a class="btn btn--ghost btn--sm" href="<?= e(url('pages/payment-form.php?invoice_id=' . $inv['id'])) ?>">
          <i class="fa-solid fa-plus"></i> Add payment
        </a>
      </div>
    <?php endif; ?>
  </div>

  <?php if (!$history): ?>
    <div class="card__body">
      <p class="muted" style="margin:0;">No payments recorded against this invoice.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap" style="border:0;border-radius:0;">
      <table class="table">
        <thead>
          <tr>
            <th>Receipt</th>
            <th>Date</th>
            <th>Method</th>
            <th>Reference</th>
            <th class="num">Amount</th>
            <th class="actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($history as $h): ?>
            <tr>
              <td><a href="<?= e(url('pages/payment-view.php?id=' . $h['id'])) ?>"><?= e($h['receipt_number'] ?: '—') ?></a></td>
              <td><?= e(date('j M Y', strtotime($h['payment_date']))) ?></td>
              <td><?= e(PAYMENT_METHODS[$h['method']] ?? ucfirst($h['method'])) ?></td>
              <td><?= $h['reference'] ? e($h['reference']) : '<span class="muted">—</span>' ?></td>
              <td class="num"><?= e($inv['currency']) ?> <?= e(number_format((float) $h['amount'], 2)) ?></td>
              <td class="actions">
                <a class="btn btn--ghost btn--sm" href="<?= e(url('pages/receipt.php?id=' . $h['id'])) ?>" target="_blank"
                   aria-label="Receipt">
                  <i class="fa-solid fa-receipt"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card__head"><h2 class="card__title">Line items</h2></div>
  <?php if (!$items): ?>
    <div class="card__body"><p class="muted" style="margin:0;">No items.</p></div>
  <?php else: ?>
    <div class="table-wrap" style="border:0;border-radius:0;">
      <table class="table">
        <thead>
          <tr>
            <th>Description</th>
            <th class="num">Qty</th>
            <th class="num">Unit price</th>
            <th class="num">Line total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td><?= e($it['description']) ?></td>
              <td class="num"><?= e(number_format((float) $it['quantity'], 2)) ?></td>
              <td class="num"><?= e(number_format((float) $it['unit_price'], 2)) ?></td>
              <td class="num"><?= e(number_format((float) $it['line_total'], 2)) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <div class="card__body" style="border-top:1px solid var(--light-200);">
    <div class="totals">
      <div class="totals__row"><span>Subtotal</span><b><?= e($inv['currency']) ?> <?= e(number_format((float) $inv['subtotal'], 2)) ?></b></div>
      <?php if ($inv['discount_type'] !== 'none' && (float) $inv['discount_amount'] > 0): ?>
        <div class="totals__row">
          <span>Discount<?= $inv['discount_type'] === 'percent' ? ' (' . e(number_format((float) $inv['discount_value'], 2)) . '%)' : '' ?></span>
          <b>- <?= e(number_format((float) $inv['discount_amount'], 2)) ?></b>
        </div>
      <?php endif; ?>
      <?php if ((float) $inv['tax_amount'] > 0): ?>
        <div class="totals__row">
          <span>Tax (<?= e(number_format((float) $inv['tax_rate'], 2)) ?>%)</span>
          <b><?= e(number_format((float) $inv['tax_amount'], 2)) ?></b>
        </div>
      <?php endif; ?>
      <div class="totals__row totals__row--grand">
        <span>Total</span>
        <b><?= e($inv['currency']) ?> <?= e(number_format((float) $inv['total'], 2)) ?></b>
      </div>
    </div>
  </div>
</div>

<?php if ($inv['notes'] || $inv['payment_terms']): ?>
  <div class="grid-2" style="margin-top:20px;">
    <?php if ($inv['notes']): ?>
      <div class="card">
        <div class="card__head"><h2 class="card__title">Notes</h2></div>
        <div class="card__body" style="white-space:pre-wrap;"><?= e($inv['notes']) ?></div>
      </div>
    <?php endif; ?>
    <?php if ($inv['payment_terms']): ?>
      <div class="card">
        <div class="card__head"><h2 class="card__title">Payment instructions</h2></div>
        <div class="card__body" style="white-space:pre-wrap;"><?= e($inv['payment_terms']) ?></div>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/app-footer.php'; ?>