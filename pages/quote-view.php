<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/quotes.php';
require_business();

$id = (int) get('id', 0);
$quote = quote_get($id);

if (!$quote) {
    flash_set('error', 'Quote not found.');
    redirect('pages/quotes.php');
}

$items = quote_items($id);
$eff   = quote_effective_status($quote);
$hasInvoice = quote_has_invoice($id);

$page_title = $quote['number'];
include __DIR__ . '/../includes/app-header.php';
?>

<div class="page__head">
  <div>
    <h1 class="page__title"><?= e($quote['number']) ?></h1>
    <p class="page__subtitle">
      For <?= e($quote['client_name']) ?> &middot; Issued <?= e(date('j M Y', strtotime($quote['issue_date']))) ?>
    </p>
  </div>
  <div class="page__actions">
    <a class="btn btn--ghost" href="<?= e(url('pages/quotes.php')) ?>">
      <i class="fa-solid fa-arrow-left"></i> All quotes
    </a>
    <a class="btn btn--ghost" href="<?= e(url('pages/quote-preview.php?id=' . $quote['id'])) ?>" target="_blank">
      <i class="fa-solid fa-print"></i> Preview
    </a>
    <a class="btn btn--ghost" href="<?= e(url('pages/quote-form.php?id=' . $quote['id'])) ?>">
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
    <div style="margin-top:8px;"><?= doc_status_badge('quote', $eff) ?></div>
  </div></div>
  <div class="card"><div class="card__body">
    <div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Valid until</div>
    <div style="font-family:var(--font-display);font-size:1.15rem;font-weight:700;margin-top:6px;">
      <?= $quote['valid_until'] ? e(date('j M Y', strtotime($quote['valid_until']))) : '—' ?>
    </div>
  </div></div>
  <div class="card"><div class="card__body">
    <div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Total</div>
    <div style="font-family:var(--font-display);font-size:1.5rem;font-weight:700;margin-top:4px;">
      <?= e($quote['currency']) ?> <?= e(number_format((float) $quote['total'], 2)) ?>
    </div>
  </div></div>
</div>

<div class="card" style="margin-bottom:20px;">
  <div class="card__head"><h2 class="card__title">Actions</h2></div>
  <div class="card__body">
    <div class="row" style="flex-wrap:wrap;gap:8px;">

      <?php if ($quote['status'] === 'draft'): ?>
        <form method="post" action="<?= e(url('pages/quote-status.php')) ?>" style="display:inline;">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $quote['id'] ?>">
          <input type="hidden" name="status" value="sent">
          <button class="btn btn--primary"><i class="fa-solid fa-paper-plane"></i> Mark as sent</button>
        </form>
      <?php endif; ?>

      <?php if ($quote['status'] === 'sent'): ?>
        <form method="post" action="<?= e(url('pages/quote-status.php')) ?>" style="display:inline;">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $quote['id'] ?>">
          <input type="hidden" name="status" value="accepted">
          <button class="btn btn--primary"><i class="fa-solid fa-check"></i> Mark as accepted</button>
        </form>
        <form method="post" action="<?= e(url('pages/quote-status.php')) ?>" style="display:inline;"
              onsubmit="return confirm('Reject this quote?');">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $quote['id'] ?>">
          <input type="hidden" name="status" value="rejected">
          <button class="btn btn--danger"><i class="fa-solid fa-xmark"></i> Mark as rejected</button>
        </form>
      <?php endif; ?>

      <?php if ($quote['status'] === 'accepted'): ?>
        <?php if ($hasInvoice): ?>
          <?php
            $linked = db()->prepare('SELECT id, number FROM invoices WHERE source_quote_id = ? AND business_id = ? LIMIT 1');
            $linked->execute([$quote['id'], current_business()['id']]);
            $linkedInv = $linked->fetch();
          ?>
          <?php if ($linkedInv): ?>
            <a class="btn btn--ghost" href="<?= e(url('pages/invoice-view.php?id=' . $linkedInv['id'])) ?>">
              <i class="fa-solid fa-file-invoice"></i> View invoice <?= e($linkedInv['number']) ?>
            </a>
          <?php else: ?>
            <span class="badge badge--paid"><i class="fa-solid fa-check"></i> Invoiced</span>
          <?php endif; ?>
        <?php else: ?>
          <form method="post" action="<?= e(url('pages/quote-convert.php')) ?>" style="display:inline;"
                onsubmit="return confirm('Convert this quote into an invoice? A new draft invoice will be created with today\'s date.');">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $quote['id'] ?>">
            <button class="btn btn--primary">
              <i class="fa-solid fa-file-invoice"></i> Convert to invoice
            </button>
          </form>
        <?php endif; ?>
      <?php endif; ?>

      <form method="post" action="<?= e(url('pages/quote-duplicate.php')) ?>" style="display:inline;">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int) $quote['id'] ?>">
        <button class="btn btn--ghost"><i class="fa-regular fa-copy"></i> Duplicate</button>
      </form>

      <?php if ($quote['status'] === 'draft'): ?>
        <form method="post" action="<?= e(url('pages/quote-delete.php')) ?>" style="display:inline;margin-left:auto;"
              onsubmit="return confirm('Delete this draft quote? This cannot be undone.');">
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $quote['id'] ?>">
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
      <div class="totals__row"><span>Subtotal</span><b><?= e($quote['currency']) ?> <?= e(number_format((float) $quote['subtotal'], 2)) ?></b></div>
      <?php if ($quote['discount_type'] !== 'none' && (float) $quote['discount_amount'] > 0): ?>
        <div class="totals__row">
          <span>Discount<?= $quote['discount_type'] === 'percent' ? ' (' . e(number_format((float) $quote['discount_value'], 2)) . '%)' : '' ?></span>
          <b>- <?= e(number_format((float) $quote['discount_amount'], 2)) ?></b>
        </div>
      <?php endif; ?>
      <?php if ((float) $quote['tax_amount'] > 0): ?>
        <div class="totals__row">
          <span>Tax (<?= e(number_format((float) $quote['tax_rate'], 2)) ?>%)</span>
          <b><?= e(number_format((float) $quote['tax_amount'], 2)) ?></b>
        </div>
      <?php endif; ?>
      <div class="totals__row totals__row--grand">
        <span>Total</span>
        <b><?= e($quote['currency']) ?> <?= e(number_format((float) $quote['total'], 2)) ?></b>
      </div>
    </div>
  </div>
</div>

<?php if ($quote['notes'] || $quote['terms']): ?>
  <div class="grid-2" style="margin-top:20px;">
    <?php if ($quote['notes']): ?>
      <div class="card">
        <div class="card__head"><h2 class="card__title">Notes</h2></div>
        <div class="card__body" style="white-space:pre-wrap;"><?= e($quote['notes']) ?></div>
      </div>
    <?php endif; ?>
    <?php if ($quote['terms']): ?>
      <div class="card">
        <div class="card__head"><h2 class="card__title">Terms</h2></div>
        <div class="card__body" style="white-space:pre-wrap;"><?= e($quote['terms']) ?></div>
      </div>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/app-footer.php'; ?>