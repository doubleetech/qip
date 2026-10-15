<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/clients.php';
require_business();

$id = (int) get('id', 0);
$client = client_get($id);

if (!$client) {
    flash_set('error', 'Client not found.');
    redirect('pages/clients.php');
}

$biz = current_business();

// Latest docs for this client (stubs until Phase 3/4 pages exist)
$stmt = db()->prepare(
    'SELECT id, number, status, issue_date, total, currency
     FROM quotes WHERE client_id = ? AND business_id = ?
     ORDER BY id DESC LIMIT 10'
);
$stmt->execute([$id, $biz['id']]);
$quotes = $stmt->fetchAll();

$stmt = db()->prepare(
    'SELECT id, number, status, issue_date, due_date, total, currency
     FROM invoices WHERE client_id = ? AND business_id = ?
     ORDER BY id DESC LIMIT 10'
);
$stmt->execute([$id, $biz['id']]);
$invoices = $stmt->fetchAll();

$page_title = $client['name'];
include __DIR__ . '/../includes/app-header.php';
?>

<div class="page__head">
  <div>
    <h1 class="page__title"><?= e($client['name']) ?></h1>
    <p class="page__subtitle">
      <?php if ($client['company']): ?>
        <?= e($client['company']) ?> &middot;
      <?php endif; ?>
      Added <?= e(date('j M Y', strtotime($client['created_at']))) ?>
    </p>
  </div>
  <div class="page__actions">
    <a class="btn btn--ghost" href="<?= e(url('pages/clients.php')) ?>">
      <i class="fa-solid fa-arrow-left"></i> All clients
    </a>
    <a class="btn btn--ghost" href="<?= e(url('pages/client-form.php?id=' . $client['id'])) ?>">
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

<div class="grid-2" style="margin-bottom:24px;">

  <div class="card">
    <div class="card__head"><h2 class="card__title">Contact</h2></div>
    <div class="card__body">
      <div class="stack" style="gap:12px;">
        <div>
          <div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Email</div>
          <div><?= $client['email'] ? '<a href="mailto:' . e($client['email']) . '">' . e($client['email']) . '</a>' : '<span class="muted">—</span>' ?></div>
        </div>
        <div>
          <div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Phone</div>
          <div><?= $client['phone'] ? e($client['phone']) : '<span class="muted">—</span>' ?></div>
        </div>
        <div>
          <div class="muted" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.08em;">Billing address</div>
          <div style="white-space:pre-wrap;"><?= $client['address'] ? e($client['address']) : '<span class="muted">—</span>' ?></div>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card__head"><h2 class="card__title">Notes</h2></div>
    <div class="card__body">
      <?php if ($client['notes']): ?>
        <div style="white-space:pre-wrap;"><?= e($client['notes']) ?></div>
      <?php else: ?>
        <p class="muted" style="margin:0;">No notes yet.</p>
      <?php endif; ?>
    </div>
  </div>

</div>

<div class="card" style="margin-bottom:24px;">
  <div class="card__head">
    <h2 class="card__title">Quotes</h2>
    <div style="margin-left:auto;">
      <a class="btn btn--ghost btn--sm" href="<?= e(url('pages/quote-form.php?client_id=' . $client['id'])) ?>">
        <i class="fa-solid fa-plus"></i> New quote
      </a>
    </div>
  </div>

  <?php if (!$quotes): ?>
    <div class="card__body">
      <p class="muted" style="margin:0;">No quotes for this client yet.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap" style="border:0;border-radius:0;">
      <table class="table">
        <thead>
          <tr>
            <th>Number</th>
            <th>Issued</th>
            <th>Status</th>
            <th class="num">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($quotes as $q): ?>
            <tr>
              <td><a href="<?= e(url('pages/quote-view.php?id=' . $q['id'])) ?>"><?= e($q['number']) ?></a></td>
              <td><?= e(date('j M Y', strtotime($q['issue_date']))) ?></td>
              <td>
                <span class="badge badge--<?= e($q['status'] === 'accepted' ? 'paid' : ($q['status'] === 'rejected' ? 'overdue' : ($q['status'] === 'sent' ? 'info' : 'draft'))) ?>">
                  <?= e(ucfirst($q['status'])) ?>
                </span>
              </td>
              <td class="num"><?= e($q['currency']) ?> <?= e(number_format((float) $q['total'], 2)) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card__head">
    <h2 class="card__title">Invoices</h2>
    <div style="margin-left:auto;">
      <a class="btn btn--ghost btn--sm" href="<?= e(url('pages/invoice-form.php?client_id=' . $client['id'])) ?>">
        <i class="fa-solid fa-plus"></i> New invoice
      </a>
    </div>
  </div>

  <?php if (!$invoices): ?>
    <div class="card__body">
      <p class="muted" style="margin:0;">No invoices for this client yet.</p>
    </div>
  <?php else: ?>
    <div class="table-wrap" style="border:0;border-radius:0;">
      <table class="table">
        <thead>
          <tr>
            <th>Number</th>
            <th>Issued</th>
            <th>Due</th>
            <th>Status</th>
            <th class="num">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($invoices as $inv): ?>
            <tr>
              <td><a href="<?= e(url('pages/invoice-view.php?id=' . $inv['id'])) ?>"><?= e($inv['number']) ?></a></td>
              <td><?= e(date('j M Y', strtotime($inv['issue_date']))) ?></td>
              <td><?= $inv['due_date'] ? e(date('j M Y', strtotime($inv['due_date']))) : '<span class="muted">—</span>' ?></td>
              <td>
                <span class="badge badge--<?= e($inv['status'] === 'sent' ? 'info' : ($inv['status'] === 'cancelled' ? 'overdue' : ($inv['status'] === 'pending' ? 'pending' : 'draft'))) ?>">
                  <?= e(ucfirst($inv['status'])) ?>
                </span>
              </td>
              <td class="num"><?= e($inv['currency']) ?> <?= e(number_format((float) $inv['total'], 2)) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/app-footer.php'; ?>