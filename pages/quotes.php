<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/quotes.php';
require_business();

$q      = trim((string) get('q', ''));
$status = (string) get('status', 'all');

$quotes = quotes_list($q !== '' ? $q : null, $status);

$filters = [
    'all'      => 'All',
    'draft'    => 'Draft',
    'sent'     => 'Sent',
    'accepted' => 'Accepted',
    'rejected' => 'Rejected',
    'expired'  => 'Expired',
];

$page_title = 'Quotes';
include __DIR__ . '/../includes/app-header.php';
?>

<div class="page__head">
  <div>
    <h1 class="page__title">Quotes</h1>
    <p class="page__subtitle">Send a quote, win the job, convert to an invoice.</p>
  </div>
  <div class="page__actions">
    <a class="btn btn--primary" href="<?= e(url('pages/quote-form.php')) ?>">
      <i class="fa-solid fa-plus"></i> New quote
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

<?php if (!$quotes && $q === '' && $status === 'all'): ?>

  <div class="empty">
    <div class="empty__icon"><i class="fa-solid fa-file-lines"></i></div>
    <h2 class="empty__title">No quotes yet</h2>
    <p class="empty__text">Quotes you create will show up here until the client accepts them.</p>
    <a class="btn btn--primary" href="<?= e(url('pages/quote-form.php')) ?>">
      <i class="fa-solid fa-plus"></i> Create quote
    </a>
  </div>

<?php else: ?>

  <form method="get" class="row" style="margin-bottom:16px;gap:8px;flex-wrap:wrap;">
    <div style="flex:1;min-width:220px;position:relative;">
      <input class="input" type="search" name="q" value="<?= e($q) ?>"
             placeholder="Search by quote number or client…"
             aria-label="Search quotes"
             style="padding-left:38px;">
      <i class="fa-solid fa-magnifying-glass" aria-hidden="true"
         style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-light-muted);font-size:.85rem;"></i>
    </div>
    <input type="hidden" name="status" value="<?= e($status) ?>">
    <button class="btn btn--ghost" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    <?php if ($q !== '' || $status !== 'all'): ?>
      <a class="btn btn--ghost" href="<?= e(url('pages/quotes.php')) ?>">
        <i class="fa-solid fa-xmark"></i> Clear
      </a>
    <?php endif; ?>
  </form>

  <div class="row" style="gap:6px;margin-bottom:16px;flex-wrap:wrap;">
    <?php foreach ($filters as $key => $label): ?>
      <?php
        $href = url('pages/quotes.php?status=' . urlencode($key) . ($q !== '' ? '&q=' . urlencode($q) : ''));
        $isOn = $status === $key;
      ?>
      <a class="btn btn--sm <?= $isOn ? 'btn--primary' : 'btn--ghost' ?>" href="<?= e($href) ?>">
        <?= e($label) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (!$quotes): ?>

    <div class="empty">
      <div class="empty__icon"><i class="fa-solid fa-magnifying-glass"></i></div>
      <h2 class="empty__title">No matches</h2>
      <p class="empty__text">Nothing matched those filters. Try different ones.</p>
      <a class="btn btn--ghost" href="<?= e(url('pages/quotes.php')) ?>">Clear filters</a>
    </div>

  <?php else: ?>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Number</th>
            <th>Client</th>
            <th>Issued</th>
            <th>Valid until</th>
            <th class="num">Total</th>
            <th>Status</th>
            <th class="actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($quotes as $qt): ?>
            <?php $eff = quote_effective_status($qt); ?>
            <tr>
              <td>
                <a href="<?= e(url('pages/quote-view.php?id=' . $qt['id'])) ?>">
                  <?= e($qt['number']) ?>
                </a>
              </td>
              <td><?= e($qt['client_name']) ?></td>
              <td><?= e(date('j M Y', strtotime($qt['issue_date']))) ?></td>
              <td><?= $qt['valid_until'] ? e(date('j M Y', strtotime($qt['valid_until']))) : '<span class="muted">—</span>' ?></td>
              <td class="num"><?= e($qt['currency']) ?> <?= e(number_format((float) $qt['total'], 2)) ?></td>
              <td><?= doc_status_badge('quote', $eff) ?></td>
              <td class="actions">
                <a class="btn btn--ghost btn--sm"
                   href="<?= e(url('pages/quote-view.php?id=' . $qt['id'])) ?>"
                   aria-label="View <?= e($qt['number']) ?>">
                  <i class="fa-solid fa-eye"></i>
                </a>
                <a class="btn btn--ghost btn--sm"
                   href="<?= e(url('pages/quote-form.php?id=' . $qt['id'])) ?>"
                   aria-label="Edit <?= e($qt['number']) ?>">
                  <i class="fa-solid fa-pen"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <p class="muted" style="margin-top:12px;font-size:.85rem;">
      <?= count($quotes) ?> quote<?= count($quotes) === 1 ? '' : 's' ?>.
    </p>

  <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/app-footer.php'; ?>