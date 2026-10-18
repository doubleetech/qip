<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/payments.php';
require_business();

$q      = trim((string) get('q', ''));
$method = (string) get('method', 'all');

$payments = payments_list($q !== '' ? $q : null, $method);
$total    = payments_total();

$filters = ['all' => 'All'] + PAYMENT_METHODS;

$page_title = 'Payments';
include __DIR__ . '/../includes/app-header.php';
?>

<div class="page__head">
  <div>
    <h1 class="page__title">Payments</h1>
    <p class="page__subtitle">Every payment recorded against your invoices.</p>
  </div>
  <div class="page__actions">
    <a class="btn btn--primary" href="<?= e(url('pages/payment-form.php')) ?>">
      <i class="fa-solid fa-plus"></i> Record payment
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

<div class="card" style="margin-bottom:20px;"><div class="card__body">
  <div class="muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;">Total received (all time)</div>
  <div style="font-family:var(--font-display);font-size:1.8rem;font-weight:700;margin-top:4px;">
    <?= e(number_format($total, 2)) ?>
  </div>
</div></div>

<?php if (!$payments && $q === '' && $method === 'all'): ?>

  <div class="empty">
    <div class="empty__icon"><i class="fa-solid fa-money-bill-wave"></i></div>
    <h2 class="empty__title">No payments yet</h2>
    <p class="empty__text">Record a payment to track what's been received against your invoices.</p>
    <a class="btn btn--primary" href="<?= e(url('pages/payment-form.php')) ?>">
      <i class="fa-solid fa-plus"></i> Record payment
    </a>
  </div>

<?php else: ?>

  <form method="get" class="row" style="margin-bottom:16px;gap:8px;flex-wrap:wrap;">
    <div style="flex:1;min-width:220px;position:relative;">
      <input class="input" type="search" name="q" value="<?= e($q) ?>"
             placeholder="Search by invoice, client or reference…"
             aria-label="Search payments"
             style="padding-left:38px;">
      <i class="fa-solid fa-magnifying-glass" aria-hidden="true"
         style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-light-muted);font-size:.85rem;"></i>
    </div>
    <input type="hidden" name="method" value="<?= e($method) ?>">
    <button class="btn btn--ghost" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    <?php if ($q !== '' || $method !== 'all'): ?>
      <a class="btn btn--ghost" href="<?= e(url('pages/payments.php')) ?>">
        <i class="fa-solid fa-xmark"></i> Clear
      </a>
    <?php endif; ?>
  </form>

  <div class="row" style="gap:6px;margin-bottom:16px;flex-wrap:wrap;">
    <?php foreach ($filters as $key => $label): ?>
      <?php
        $href = url('pages/payments.php?method=' . urlencode($key) . ($q !== '' ? '&q=' . urlencode($q) : ''));
        $isOn = $method === $key;
      ?>
      <a class="btn btn--sm <?= $isOn ? 'btn--primary' : 'btn--ghost' ?>" href="<?= e($href) ?>">
        <?= e($label) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (!$payments): ?>
    <div class="empty">
      <div class="empty__icon"><i class="fa-solid fa-magnifying-glass"></i></div>
      <h2 class="empty__title">No matches</h2>
      <p class="empty__text">Nothing matched those filters.</p>
      <a class="btn btn--ghost" href="<?= e(url('pages/payments.php')) ?>">Clear filters</a>
    </div>
  <?php else: ?>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Receipt</th>
            <th>Date</th>
            <th>Invoice</th>
            <th>Client</th>
            <th>Method</th>
            <th class="num">Amount</th>
            <th class="actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($payments as $p): ?>
            <tr>
              <td>
                <a href="<?= e(url('pages/payment-view.php?id=' . $p['id'])) ?>">
                  <?= e($p['receipt_number'] ?: '—') ?>
                </a>
              </td>
              <td><?= e(date('j M Y', strtotime($p['payment_date']))) ?></td>
              <td>
                <a href="<?= e(url('pages/invoice-view.php?id=' . $p['invoice_id'])) ?>">
                  <?= e($p['invoice_number']) ?>
                </a>
              </td>
              <td><?= e($p['client_name']) ?></td>
              <td><?= e(PAYMENT_METHODS[$p['method']] ?? ucfirst($p['method'])) ?></td>
              <td class="num"><?= e($p['invoice_currency']) ?> <?= e(number_format((float) $p['amount'], 2)) ?></td>
              <td class="actions">
                <a class="btn btn--ghost btn--sm" href="<?= e(url('pages/receipt.php?id=' . $p['id'])) ?>" target="_blank"
                   aria-label="Receipt for <?= e($p['receipt_number']) ?>">
                  <i class="fa-solid fa-receipt"></i>
                </a>
                <a class="btn btn--ghost btn--sm" href="<?= e(url('pages/payment-view.php?id=' . $p['id'])) ?>"
                   aria-label="View payment">
                  <i class="fa-solid fa-eye"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <p class="muted" style="margin-top:12px;font-size:.85rem;">
      <?= count($payments) ?> payment<?= count($payments) === 1 ? '' : 's' ?>.
    </p>

  <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/app-footer.php'; ?>