<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/invoices.php';
require_once __DIR__ . '/../includes/payments.php';
require_once __DIR__ . '/../includes/analytics.php';
require_business();

$period = (string) get('period', 'this_month');
if (!in_array($period, ['this_month', 'last_month', 'this_year', 'all'], true)) {
    $period = 'this_month';
}

[$from, $to] = analytics_period_range($period);
$m           = analytics_metrics($from, $to);
$overdue     = analytics_overdue_invoices(5);
$recentPays  = analytics_recent_payments(5);

$periodLabels = [
    'this_month' => 'This month',
    'last_month' => 'Last month',
    'this_year'  => 'This year',
    'all'        => 'All time',
];

$biz = current_business();
$currency = $biz['default_currency'];

$page_title = 'Dashboard';
include __DIR__ . '/../includes/app-header.php';
?>

<div class="page__head">
  <div>
    <h1 class="page__title">Good to see you, <?= e(explode(' ', current_user()['name'])[0]) ?></h1>
    <p class="page__subtitle">Here's how your business is doing.</p>
  </div>
  <div class="page__actions">
    <div class="row" style="gap:6px;flex-wrap:wrap;">
      <?php foreach ($periodLabels as $k => $lbl): ?>
        <a class="btn btn--sm <?= $period === $k ? 'btn--primary' : 'btn--ghost' ?>"
           href="<?= e(url('pages/dashboard.php?period=' . urlencode($k))) ?>">
          <?= e($lbl) ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php if ($m['invoices_count'] === 0 && $m['payments_count'] === 0 && $m['expenses'] == 0 && $m['clients'] === 0): ?>

  <div class="empty" style="margin-bottom:32px;">
    <div class="empty__icon"><i class="fa-solid fa-chart-line"></i></div>
    <h2 class="empty__title">No activity yet</h2>
    <p class="empty__text">Create your first invoice to start seeing numbers here.</p>
    <a class="btn btn--primary" href="<?= e(url('pages/invoice-form.php')) ?>">
      <i class="fa-solid fa-plus"></i> Create invoice
    </a>
  </div>

<?php else: ?>

  <div class="grid-4" style="margin-bottom:20px;">
    <div class="card"><div class="card__body">
      <div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Total invoiced</div>
      <div style="font-family:var(--font-display);font-size:1.6rem;font-weight:700;margin-top:6px;">
        <?= e($currency) ?> <?= e(number_format($m['invoiced'], 2)) ?>
      </div>
    </div></div>

    <div class="card"><div class="card__body">
      <div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Total paid</div>
      <div style="font-family:var(--font-display);font-size:1.6rem;font-weight:700;margin-top:6px;">
        <?= e($currency) ?> <?= e(number_format($m['paid'], 2)) ?>
      </div>
    </div></div>

    <div class="card"><div class="card__body">
      <div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Outstanding</div>
      <div style="font-family:var(--font-display);font-size:1.6rem;font-weight:700;margin-top:6px;">
        <?= e($currency) ?> <?= e(number_format($m['outstanding'], 2)) ?>
      </div>
    </div></div>

    <div class="card"><div class="card__body">
      <div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Overdue</div>
      <div style="font-family:var(--font-display);font-size:1.6rem;font-weight:700;margin-top:6px;color:#dc2626;">
        <?= e($currency) ?> <?= e(number_format($m['overdue'], 2)) ?>
      </div>
    </div></div>
  </div>

  <div class="grid-3" style="margin-bottom:20px;">
    <div class="card"><div class="card__body">
      <div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Clients</div>
      <div style="font-family:var(--font-display);font-size:1.4rem;font-weight:700;margin-top:6px;">
        <?= e((string) $m['clients']) ?>
      </div>
      <a class="btn btn--ghost btn--sm" style="margin-top:8px;" href="<?= e(url('pages/clients.php')) ?>">
        <i class="fa-solid fa-users"></i> View all
      </a>
    </div></div>

    <div class="card"><div class="card__body">
      <div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Expenses <?= $period !== 'all' ? '(' . e($periodLabels[$period]) . ')' : '' ?></div>
      <div style="font-family:var(--font-display);font-size:1.4rem;font-weight:700;margin-top:6px;">
        <?= e($currency) ?> <?= e(number_format($m['expenses'], 2)) ?>
      </div>
      <a class="btn btn--ghost btn--sm" style="margin-top:8px;" href="<?= e(url('pages/expenses.php')) ?>">
        <i class="fa-solid fa-receipt"></i> View all
      </a>
    </div></div>

    <div class="card"><div class="card__body">
      <div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Payments <?= $period !== 'all' ? '(' . e($periodLabels[$period]) . ')' : '' ?></div>
      <div style="font-family:var(--font-display);font-size:1.4rem;font-weight:700;margin-top:6px;">
        <?= e((string) $m['payments_count']) ?>
      </div>
      <a class="btn btn--ghost btn--sm" style="margin-top:8px;" href="<?= e(url('pages/payments.php')) ?>">
        <i class="fa-solid fa-money-bill-wave"></i> View all
      </a>
    </div></div>
  </div>

  <div class="grid-2" style="margin-bottom:20px;">

    <div class="card">
      <div class="card__head">
        <h2 class="card__title">Overdue invoices</h2>
        <?php if ($overdue): ?>
          <div style="margin-left:auto;">
            <a class="btn btn--ghost btn--sm" href="<?= e(url('pages/invoices.php?status=overdue')) ?>">
              View all
            </a>
          </div>
        <?php endif; ?>
      </div>
      <?php if (!$overdue): ?>
        <div class="card__body">
          <p class="muted" style="margin:0;">Nothing overdue. Nice.</p>
        </div>
      <?php else: ?>
        <div class="table-wrap" style="border:0;border-radius:0;">
          <table class="table">
            <thead>
              <tr>
                <th>Invoice</th>
                <th>Client</th>
                <th>Due</th>
                <th class="num">Balance</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($overdue as $o): ?>
                <tr>
                  <td><a href="<?= e(url('pages/invoice-view.php?id=' . $o['id'])) ?>"><?= e($o['number']) ?></a></td>
                  <td><?= e($o['client_name']) ?></td>
                  <td><?= e(date('j M Y', strtotime($o['due_date']))) ?></td>
                  <td class="num"><?= e($o['currency']) ?> <?= e(number_format($o['balance'], 2)) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card__head">
        <h2 class="card__title">Recent payments</h2>
        <?php if ($recentPays): ?>
          <div style="margin-left:auto;">
            <a class="btn btn--ghost btn--sm" href="<?= e(url('pages/payments.php')) ?>">View all</a>
          </div>
        <?php endif; ?>
      </div>
      <?php if (!$recentPays): ?>
        <div class="card__body">
          <p class="muted" style="margin:0;">No payments recorded yet.</p>
        </div>
      <?php else: ?>
        <div class="table-wrap" style="border:0;border-radius:0;">
          <table class="table">
            <thead>
              <tr>
                <th>Date</th>
                <th>Client</th>
                <th>Invoice</th>
                <th class="num">Amount</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentPays as $p): ?>
                <tr>
                  <td><?= e(date('j M Y', strtotime($p['payment_date']))) ?></td>
                  <td><?= e($p['client_name']) ?></td>
                  <td><a href="<?= e(url('pages/payment-view.php?id=' . $p['id'])) ?>"><?= e($p['invoice_number']) ?></a></td>
                  <td class="num"><?= e($p['invoice_currency']) ?> <?= e(number_format((float) $p['amount'], 2)) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>

  </div>

<?php endif; ?>

<?php include __DIR__ . '/../includes/app-footer.php'; ?>