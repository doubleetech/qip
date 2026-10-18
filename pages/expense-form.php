<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/expenses.php';
require_business();

$editId  = (int) get('id', 0);
$editing = $editId > 0;

$existing = null;
if ($editing) {
    $existing = expense_get($editId);
    if (!$existing) {
        flash_set('error', 'Expense not found.');
        redirect('pages/expenses.php');
    }
}

$old = [
    'description'  => $existing['description']  ?? '',
    'category'     => $existing['category']     ?? 'other',
    'amount'       => $existing['amount']       ?? '',
    'expense_date' => $existing['expense_date'] ?? date('Y-m-d'),
    'method'       => $existing['method']       ?? '',
    'reference'    => $existing['reference']    ?? '',
    'notes'        => $existing['notes']        ?? '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify(post('_csrf'))) {
        $errors['form'] = 'Session expired. Please try again.';
    } else {
        foreach ($old as $k => $_) {
            $old[$k] = (string) post($k, '');
        }

        [$data, $errors] = expense_validate($old);

        if (!$errors) {
            if ($editing) {
                expense_update($editId, $data);
                flash_set('success', 'Expense updated.');
            } else {
                expense_create($data);
                flash_set('success', 'Expense added.');
            }
            redirect('pages/expenses.php');
        }
    }
}

$page_title = $editing ? 'Edit expense' : 'Add expense';
include __DIR__ . '/../includes/app-header.php';
?>

<style>
  @media (max-width: 640px) {
    .card__foot {
      flex-direction: column-reverse;
      align-items: stretch;
      gap: 12px;
    }
    .card__foot .row {
      flex-direction: column;
      align-items: stretch;
      gap: 8px;
    }
    .card__foot .btn {
      width: 100%;
      justify-content: center;
    }
  }
</style>

<div class="page__head">
  <div>
    <h1 class="page__title"><?= $editing ? 'Edit expense' : 'Add expense' ?></h1>
    <p class="page__subtitle">
      <?= $editing ? 'Update this expense record.' : 'Log a business expense.' ?>
    </p>
  </div>
  <div class="page__actions">
    <a class="btn btn--ghost" href="<?= e(url('pages/expenses.php')) ?>">
      <i class="fa-solid fa-arrow-left"></i> Cancel
    </a>
  </div>
</div>

<?php if (!empty($errors['form'])): ?>
  <div class="flash flash--error">
    <i class="fa-solid fa-circle-exclamation"></i>
    <span><?= e($errors['form']) ?></span>
  </div>
<?php endif; ?>

<div class="card" style="max-width:720px;">
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="card__body">

      <div class="field <?= isset($errors['description']) ? 'field--error' : '' ?>">
        <label class="field__label" for="description">Description</label>
        <input class="input" type="text" id="description" name="description"
               value="<?= e($old['description']) ?>" required autofocus
               placeholder="e.g. Figma subscription — October">
        <?php if (isset($errors['description'])): ?>
          <div class="field__error"><?= e($errors['description']) ?></div>
        <?php endif; ?>
      </div>

      <div class="grid-2">
        <div class="field">
          <label class="field__label" for="category">Category</label>
          <select class="select" id="category" name="category">
            <?php foreach (EXPENSE_CATEGORIES as $k => $label): ?>
              <option value="<?= e($k) ?>" <?= $old['category'] === $k ? 'selected' : '' ?>>
                <?= e($label) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field <?= isset($errors['amount']) ? 'field--error' : '' ?>">
          <label class="field__label" for="amount">Amount</label>
          <input class="input" type="number" id="amount" name="amount"
                 value="<?= e($old['amount']) ?>" min="0.01" step="0.01" required>
          <?php if (isset($errors['amount'])): ?>
            <div class="field__error"><?= e($errors['amount']) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="grid-2">
        <div class="field <?= isset($errors['expense_date']) ? 'field--error' : '' ?>">
          <label class="field__label" for="expense_date">Date</label>
          <input class="input" type="date" id="expense_date" name="expense_date"
                 value="<?= e($old['expense_date']) ?>" required>
          <?php if (isset($errors['expense_date'])): ?>
            <div class="field__error"><?= e($errors['expense_date']) ?></div>
          <?php endif; ?>
        </div>

        <div class="field">
          <label class="field__label" for="method">Payment method <span class="muted">(optional)</span></label>
          <input class="input" type="text" id="method" name="method"
                 value="<?= e($old['method']) ?>" placeholder="e.g. Card, Cash">
        </div>
      </div>

      <div class="field">
        <label class="field__label" for="reference">Vendor / reference <span class="muted">(optional)</span></label>
        <input class="input" type="text" id="reference" name="reference"
               value="<?= e($old['reference']) ?>">
      </div>

      <div class="field" style="margin-bottom:0;">
        <label class="field__label" for="notes">Notes <span class="muted">(optional)</span></label>
        <textarea class="textarea" id="notes" name="notes"><?= e($old['notes']) ?></textarea>
      </div>

    </div>

    <div class="card__foot">
      <?php if ($editing): ?>
        <a class="btn btn--danger"
           href="<?= e(url('pages/expense-delete.php?id=' . $editId)) ?>"
           onclick="return false;"
           data-delete-expense="<?= (int) $editId ?>">
          <i class="fa-solid fa-trash"></i> Delete
        </a>
      <?php else: ?>
        <span></span>
      <?php endif; ?>
      <div class="row" style="gap:8px;">
        <a class="btn btn--ghost" href="<?= e(url('pages/expenses.php')) ?>">Cancel</a>
        <button class="btn btn--primary" type="submit">
          <i class="fa-solid fa-check"></i> <?= $editing ? 'Save changes' : 'Add expense' ?>
        </button>
      </div>
    </div>
  </form>
</div>

<?php if ($editing): ?>
<form method="post" action="<?= e(url('pages/expense-delete.php')) ?>"
      id="delete-expense-form" style="display:none;">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) $editId ?>">
</form>
<script>
  document.querySelector('[data-delete-expense]')?.addEventListener('click', function (e) {
    e.preventDefault();
    if (confirm('Delete this expense? This cannot be undone.')) {
      document.getElementById('delete-expense-form').submit();
    }
  });
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/app-footer.php'; ?>