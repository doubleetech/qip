<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/invoices.php';
require_business();

$q      = trim((string) get('q', ''));
$status = (string) get('status', 'all');

$computedOnly = in_array($status, ['partially_paid', 'paid', 'overdue'], true);
$listStatus   = $computedOnly ? 'all' : $status;

$invoices = invoices_list($q !== '' ? $q : null, $listStatus);

if ($computedOnly) {
    $paidMap = invoices_paid_map(array_map(fn($i) => (int) $i['id'], $invoices));
    $invoices = array_values(array_filter($invoices, function ($inv) use ($paidMap, $status) {
        $paid = $paidMap[(int) $inv['id']] ?? 0.0;
        return invoice_effective_status($inv, $paid) === $status;
    }));
}

$filters = [
    'all'            => 'All',
    'draft'          => 'Draft',
    'sent'           => 'Sent',
    'pending'        => 'Pending',
    'partially_paid' => 'Partially paid',
    'paid'           => 'Paid',
    'overdue'        => 'Overdue',
    'cancelled'      => 'Cancelled',
];

$page_title = 'Invoices';
include __DIR__ . '/../includes/app-header.php';
?>

<div class="page__head">
  <div>
    <h1 class="page__title">Invoices</h1>
    <p class="page__subtitle">Every invoice you've sent, all in one place.</p>
  </div>
  <div class="page__actions">
    <a class="btn btn--primary" href="<?= e(url('pages/invoice-form.php')) ?>">
      <i class="fa-solid fa-plus"></i> New invoice
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

<?php if (!$invoices && $q === '' && $status === 'all'): ?>

  <div class="empty">
    <div class="empty__icon"><i class="fa-solid fa-file-invoice"></i></div>
    <h2 class="empty__title">No invoices yet</h2>
    <p class="empty__text">Your invoices will live here. Create your first one to get started.</p>
    <a class="btn btn--primary" href="<?= e(url('pages/invoice-form.php')) ?>">
      <i class="fa-solid fa-plus"></i> Create invoice
    </a>
  </div>

<?php else: ?>

  <form method="get" class="row" style="margin-bottom:16px;gap:8px;flex-wrap:wrap;">
    <div style="flex:1;min-width:220px;position:relative;">
      <input class="input" type="search" name="q" value="<?= e($q) ?>"
             placeholder="Search by invoice number or client…"
             aria-label="Search invoices"
             style="padding-left:38px;">
      <i class="fa-solid fa-magnifying-glass" aria-hidden="true"
         style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-light-muted);font-size:.85rem;"></i>
    </div>
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <button class="btn btn--ghost" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    <?php if ($q !== '' || $status !== 'all'): ?>
      <a class="btn btn--ghost" href="<?= e(url('pages/invoices.php')) ?>">
        <i class="fa-solid fa-xmark"></i> Clear
      </a>
    <?php endif; ?>
  </form>

  <div class="row" style="gap:6px;margin-bottom:16px;flex-wrap:wrap;">
    <?php foreach ($filters as $key => $label): ?>
      <?php
        $href = url('pages/invoices.php?status=' . urlencode($key) . ($q !== '' ? '&q=' . urlencode($q) : ''));
        $isOn = $status === $key;
      ?>
      <a class="btn btn--sm <?= $isOn ? 'btn--primary' : 'btn--ghost' ?>" href="<?= e($href) ?>">
        <?= e($label) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (!$invoices): ?>

    <div class="empty">
      <div class="empty__icon"><i class="fa-solid fa-magnifying-glass"></i></div>
      <h2 class="empty__title">No matches</h2>
      <p class="empty__text">Nothing matched those filters.</p>
      <a class="btn btn--ghost" href="<?= e(url('pages/invoices.php')) ?>">Clear filters</a>
    </div>

  <?php else: ?>

    <?php $paidMap = invoices_paid_map(array_map(fn($i) => (int) $i['id'], $invoices)); ?>
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Number</th>
            <th>Client</th>
            <th>Issued</th>
            <th>Due</th>
            <th class="num">Total</th>
            <th class="num">Balance</th>
            <th>Status</th>
            <th class="actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($invoices as $inv): ?>
            <?php
              $paid = $paidMap[(int) $inv['id']] ?? 0.0;
              $bal  = max(0.0, round((float) $inv['total'] - $paid, 2));
              $eff  = invoice_effective_status($inv, $paid);
            ?>
            <tr>
              <td>
                <a href="<?= e(url('pages/invoice-view.php?id=' . $inv['id'])) ?>">
                  <?= e($inv['number']) ?>
                </a>
              </td>
              <td><?= e($inv['client_name']) ?></td>
              <td><?= e(date('j M Y', strtotime($inv['issue_date']))) ?></td>
              <td><?= $inv['due_date'] ? e(date('j M Y', strtotime($inv['due_date']))) : '<span class="muted">—</span>' ?></td>
              <td class="num"><?= e($inv['currency']) ?> <?= e(number_format((float) $inv['total'], 2)) ?></td>
              <td class="num"><?= e($inv['currency']) ?> <?= e(number_format($bal, 2)) ?></td>
              <td><?= doc_status_badge('invoice', $eff) ?></td>
              <td class="actions">
                <a class="btn btn--ghost btn--sm"
                   href="<?= e(url('pages/invoice-view.php?id=' . $inv['id'])) ?>"
                   aria-label="View <?= e($inv['number']) ?>">
                  <i class="fa-solid fa-eye"></i>
                </a>
                <a class="btn btn--ghost btn--sm"
                   href="<?= e(url('pages/invoice-form.php?id=' . $inv['id'])) ?>"
                   aria-label="Edit <?= e($inv['number']) ?>">
                  <i class="fa-solid fa-pen"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <p class="muted" style="margin-top:12px;font-size:.85rem;">
      <?= count($invoices) ?> invoice<?= count($invoices) === 1 ? '' : 's' ?>.
    </p>

  <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/app-footer.php'; ?>