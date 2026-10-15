<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/invoices.php';
require_business();

$id = (int) get('id', 0);
$inv = invoice_get($id);

if (!$inv) {
    flash_set('error', 'Invoice not found.');
    redirect('pages/invoices.php');
}

$items = invoice_items($id);

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

<div class="grid-3" style="margin-bottom:20px;">
  <div class="card"><div class="card__body">
    <div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Status</div>
    <div style="margin-top:8px;"><?= doc_status_badge('invoice', $inv['status']) ?></div>
  </div></div>
  <div class="card"><div class="card__body">
    <div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Due date</div>
    <div style="font-family:var(--font-display);font-size:1.15rem;font-weight:700;margin-top:6px;">
      <?= $inv['due_date'] ? e(date('j M Y', strtotime($inv['due_date']))) : '—' ?>
    </div>
  </div></div>
  <div class="card"><div class="card__body">
    <div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Total</div>
    <div style="font-family:var(--font-display);font-size:1.5rem;font-weight:700;margin-top:4px;">
      <?= e($inv['currency']) ?> <?= e(number_format((float) $inv['total'], 2)) ?>
    </div>
  </div></div>
</div>

<div class="card" style="margin-bottom:20px;">
  <div class="card__head"><h2 class="card__title">Actions</h2></div>
  <div class="card__body">
    <div class="row" style="flex-wrap:wrap;gap:8px;">

      <?php if ($inv['status'] === 'draft'): ?>
        <form method="post" action="<?= e(url('pages/invoice-status.php')) ?>" style="display:inline;">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $inv['id'] ?>">
          <input type="hidden" name="status" value="sent">
          <button class="btn btn--primary"><i class="fa-solid fa-paper-plane"></i> Mark as sent</button>
        </form>
      <?php endif; ?>

      <?php if ($inv['status'] === 'sent'): ?>
        <form method="post" action="<?= e(url('pages/invoice-status.php')) ?>" style="display:inline;">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $inv['id'] ?>">
          <input type="hidden" name="status" value="pending">
          <button class="btn btn--primary"><i class="fa-solid fa-clock"></i> Mark as pending</button>
        </form>
      <?php endif; ?>

      <?php if (in_array($inv['status'], ['sent', 'pending'], true)): ?>
        <form method="post" action="<?= e(url('pages/invoice-status.php')) ?>" style="display:inline;"
              onsubmit="return confirm('Cancel this invoice?');">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $inv['id'] ?>">
          <input type="hidden" name="status" value="cancelled">
          <button class="btn btn--danger"><i class="fa-solid fa-ban"></i> Cancel invoice</button>
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