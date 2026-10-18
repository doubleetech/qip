<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/expenses.php';
require_business();

$q        = trim((string) get('q', ''));
$category = (string) get('category', 'all');
$from     = trim((string) get('from', ''));
$to       = trim((string) get('to', ''));

$expenses = expenses_list(
    $q !== '' ? $q : null,
    $category,
    $from ?: null,
    $to ?: null
);
$total = expenses_total($from ?: null, $to ?: null);

$filters = ['all' => 'All'] + EXPENSE_CATEGORIES;

$page_title = 'Expenses';
include __DIR__ . '/../includes/app-header.php';
?>

<div class="page__head">
  <div>
    <h1 class="page__title">Expenses</h1>
    <p class="page__subtitle">Track what your business spends.</p>
  </div>
  <div class="page__actions">
    <a class="btn btn--primary" href="<?= e(url('pages/expense-form.php')) ?>">
      <i class="fa-solid fa-plus"></i> Add expense
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
  <div class="muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;">
    Total<?= $from || $to ? ' (filtered)' : ' (all time)' ?>
  </div>
  <div style="font-family:var(--font-display);font-size:1.8rem;font-weight:700;margin-top:4px;">
    <?= e(number_format($total, 2)) ?>
  </div>
</div></div>

<?php if (!$expenses && $q === '' && $category === 'all' && !$from && !$to): ?>

  <div class="empty">
    <div class="empty__icon"><i class="fa-solid fa-receipt"></i></div>
    <h2 class="empty__title">No expenses yet</h2>
    <p class="empty__text">Add your first expense to start tracking business spending.</p>
    <a class="btn btn--primary" href="<?= e(url('pages/expense-form.php')) ?>">
      <i class="fa-solid fa-plus"></i> Add expense
    </a>
  </div>

<?php else: ?>

  <form method="get" class="row" style="margin-bottom:16px;gap:8px;flex-wrap:wrap;">
    <div style="flex:1;min-width:220px;position:relative;">
      <input class="input" type="search" name="q" value="<?= e($q) ?>"
             placeholder="Search by description or reference…"
             aria-label="Search expenses"
             style="padding-left:38px;">
      <i class="fa-solid fa-magnifying-glass" aria-hidden="true"
         style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-light-muted);font-size:.85rem;"></i>
    </div>
    <input class="input" type="date" name="from" value="<?= e($from) ?>" aria-label="From date" style="max-width:160px;">
    <input class="input" type="date" name="to" value="<?= e($to) ?>" aria-label="To date" style="max-width:160px;">
    <input type="hidden" name="category" value="<?= e($category) ?>">
    <button class="btn btn--ghost" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    <?php if ($q !== '' || $category !== 'all' || $from || $to): ?>
      <a class="btn btn--ghost" href="<?= e(url('pages/expenses.php')) ?>">
        <i class="fa-solid fa-xmark"></i> Clear
      </a>
    <?php endif; ?>
  </form>

  <div class="row" style="gap:6px;margin-bottom:16px;flex-wrap:wrap;">
    <?php foreach ($filters as $key => $label): ?>
      <?php
        $qs = http_build_query(array_filter([
            'category' => $key,
            'q'        => $q,
            'from'     => $from,
            'to'       => $to,
        ], fn($v) => $v !== '' && $v !== null));
        $href = url('pages/expenses.php' . ($qs ? '?' . $qs : ''));
        $isOn = $category === $key;
      ?>
      <a class="btn btn--sm <?= $isOn ? 'btn--primary' : 'btn--ghost' ?>" href="<?= e($href) ?>">
        <?= e($label) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (!$expenses): ?>
    <div class="empty">
      <div class="empty__icon"><i class="fa-solid fa-magnifying-glass"></i></div>
      <h2 class="empty__title">No matches</h2>
      <p class="empty__text">Nothing matched those filters.</p>
      <a class="btn btn--ghost" href="<?= e(url('pages/expenses.php')) ?>">Clear filters</a>
    </div>
  <?php else: ?>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Date</th>
            <th>Description</th>
            <th>Category</th>
            <th>Reference</th>
            <th class="num">Amount</th>
            <th class="actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($expenses as $ex): ?>
            <tr>
              <td><?= e(date('j M Y', strtotime($ex['expense_date']))) ?></td>
              <td><?= e($ex['description']) ?></td>
              <td><?= e(EXPENSE_CATEGORIES[$ex['category']] ?? ucfirst($ex['category'])) ?></td>
              <td><?= $ex['reference'] ? e($ex['reference']) : '<span class="muted">—</span>' ?></td>
              <td class="num"><?= e(number_format((float) $ex['amount'], 2)) ?></td>
              <td class="actions">
                <a class="btn btn--ghost btn--sm"
                   href="<?= e(url('pages/expense-form.php?id=' . $ex['id'])) ?>"
                   aria-label="Edit expense">
                  <i class="fa-solid fa-pen"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <p class="muted" style="margin-top:12px;font-size:.85rem;">
      <?= count($expenses) ?> expense<?= count($expenses) === 1 ? '' : 's' ?>.
    </p>

  <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/app-footer.php'; ?>