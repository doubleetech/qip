<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/invoices.php';
require_once __DIR__ . '/../includes/payments.php';
require_business();

$editId  = (int) get('id', 0);
$editing = $editId > 0;

$existing = null;
$invoice  = null;

if ($editing) {
    $existing = payment_get($editId);
    if (!$existing) {
        flash_set('error', 'Payment not found.');
        redirect('pages/payments.php');
    }
    $invoice = invoice_get((int) $existing['invoice_id']);
} else {
    $invoiceId = (int) get('invoice_id', 0);
    if ($invoiceId > 0) {
        $invoice = invoice_get($invoiceId);
    }
}

$eligible = $invoice ? [] : payments_eligible_invoices();

$old = [
    'invoice_id'   => $invoice['id'] ?? 0,
    'amount'       => $existing['amount']       ?? '',
    'payment_date' => $existing['payment_date'] ?? date('Y-m-d'),
    'method'       => $existing['method']       ?? 'bank_transfer',
    'reference'    => $existing['reference']    ?? '',
    'notes'        => $existing['notes']        ?? '',
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify(post('_csrf'))) {
        $errors['form'] = 'Session expired. Please try again.';
    } else {
        $old['invoice_id']   = (int)    post('invoice_id', 0);
        $old['amount']       = (string) post('amount', '');
        $old['payment_date'] = (string) post('payment_date', '');
        $old['method']       = (string) post('method', 'bank_transfer');
        $old['reference']    = (string) post('reference', '');
        $old['notes']        = (string) post('notes', '');

        $inv = invoice_get((int) $old['invoice_id']);
        if (!$inv) {
            $errors['invoice_id'] = 'Choose an eligible invoice.';
        } else {
            [$data, $errors] = payment_validate($old, $inv, $editing ? $editId : null);

            if (!$errors) {
                try {
                    if ($editing) {
                        payment_update($editId, $data);
                        flash_set('success', 'Payment updated.');
                        redirect('pages/payment-view.php?id=' . $editId);
                    } else {
                        $newId = payment_create((int) $inv['id'], $data);
                        flash_set('success', 'Payment recorded.');
                        redirect('pages/payment-view.php?id=' . $newId);
                    }
                } catch (Throwable $e) {
                    $errors['form'] = $e->getMessage();
                }
            }
        }
    }
}

$balancePreview = null;
if ($invoice) {
    $paid = payment_invoice_paid_total((int) $invoice['id'], $editing ? $editId : null);
    $bal  = round((float) $invoice['total'] - $paid, 2);
    if ($bal < 0) $bal = 0.0;
    $balancePreview = ['paid' => $paid, 'balance' => $bal];
}

$page_title = $editing ? 'Edit payment' : 'Record payment';
include __DIR__ . '/../includes/app-header.php';
?>

<div class="page__head">
  <div>
    <h1 class="page__title"><?= $editing ? 'Edit payment' : 'Record payment' ?></h1>
    <p class="page__subtitle">
      <?= $editing
          ? 'Adjust this payment against ' . e($existing['invoice_number']) . '.'
          : 'Log money received against an issued invoice.' ?>
    </p>
  </div>
  <div class="page__actions">
    <a class="btn btn--ghost" href="<?= e(url($editing ? 'pages/payment-view.php?id=' . $editId : 'pages/payments.php')) ?>">
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

<div class="card" style="max-width:640px;">
  <form method="post" novalidate>
    <?= csrf_field() ?>
    <div class="card__body">

      <?php if (!$editing): ?>
        <div class="field <?= isset($errors['invoice_id']) ? 'field--error' : '' ?>">
          <label class="field__label" for="invoice_id">Invoice</label>
          <?php if ($invoice): ?>
            <input class="input" type="text"
                   value="<?= e($invoice['number']) ?> — <?= e($invoice['client_name']) ?>"
                   readonly>
            <input type="hidden" name="invoice_id" value="<?= (int) $invoice['id'] ?>">
          <?php else: ?>
            <select class="select" id="invoice_id" name="invoice_id" required autofocus>
              <option value="">Select an invoice…</option>
              <?php foreach ($eligible as $inv): ?>
                <option value="<?= (int) $inv['id'] ?>" <?= (int) $old['invoice_id'] === (int) $inv['id'] ? 'selected' : '' ?>>
                  <?= e($inv['number']) ?> — <?= e($inv['client_name']) ?> — <?= e($inv['currency']) ?> <?= e(number_format((float) $inv['total'], 2)) ?>
                </option>
              <?php endforeach; ?>
            </select>
          <?php endif; ?>
          <?php if (isset($errors['invoice_id'])): ?>
            <div class="field__error"><?= e($errors['invoice_id']) ?></div>
          <?php endif; ?>
        </div>
      <?php else: ?>
        <input type="hidden" name="invoice_id" value="<?= (int) $existing['invoice_id'] ?>">
        <div class="field">
          <label class="field__label">Invoice</label>
          <input class="input" type="text"
                 value="<?= e($existing['invoice_number']) ?> — <?= e($existing['client_name']) ?>"
                 readonly>
        </div>
      <?php endif; ?>

      <?php if ($invoice && $balancePreview): ?>
        <div class="grid-3" style="margin:8px 0 20px;">
          <div class="card" style="margin:0;"><div class="card__body">
            <div class="muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;">Invoice total</div>
            <div style="font-family:var(--font-display);font-weight:700;margin-top:4px;">
              <?= e($invoice['currency']) ?> <?= e(number_format((float) $invoice['total'], 2)) ?>
            </div>
          </div></div>
          <div class="card" style="margin:0;"><div class="card__body">
            <div class="muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;">Paid</div>
            <div style="font-family:var(--font-display);font-weight:700;margin-top:4px;">
              <?= e($invoice['currency']) ?> <?= e(number_format($balancePreview['paid'], 2)) ?>
            </div>
          </div></div>
          <div class="card" style="margin:0;"><div class="card__body">
            <div class="muted" style="font-size:.72rem;text-transform:uppercase;letter-spacing:.08em;">Balance</div>
            <div style="font-family:var(--font-display);font-weight:700;margin-top:4px;">
              <?= e($invoice['currency']) ?> <?= e(number_format($balancePreview['balance'], 2)) ?>
            </div>
          </div></div>
        </div>
      <?php endif; ?>

      <div class="grid-2">
        <div class="field <?= isset($errors['amount']) ? 'field--error' : '' ?>">
          <label class="field__label" for="amount">Amount</label>
          <input class="input" type="number" id="amount" name="amount"
                 value="<?= e($old['amount']) ?>"
                 min="0.01" step="0.01" required
                 <?= !$editing ? 'autofocus' : '' ?>>
          <?php if (isset($errors['amount'])): ?>
            <div class="field__error"><?= e($errors['amount']) ?></div>
          <?php endif; ?>
        </div>

        <div class="field <?= isset($errors['payment_date']) ? 'field--error' : '' ?>">
          <label class="field__label" for="payment_date">Payment date</label>
          <input class="input" type="date" id="payment_date" name="payment_date"
                 value="<?= e($old['payment_date']) ?>" required>
          <?php if (isset($errors['payment_date'])): ?>
            <div class="field__error"><?= e($errors['payment_date']) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="field">
        <label class="field__label" for="method">Method</label>
        <select class="select" id="method" name="method">
          <?php foreach (PAYMENT_METHODS as $k => $label): ?>
            <option value="<?= e($k) ?>" <?= $old['method'] === $k ? 'selected' : '' ?>>
              <?= e($label) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="field">
        <label class="field__label" for="reference">Reference <span class="muted">(optional)</span></label>
        <input class="input" type="text" id="reference" name="reference"
               value="<?= e($old['reference']) ?>"
               placeholder="Transaction ID, cheque no., etc.">
      </div>

      <div class="field" style="margin-bottom:0;">
        <label class="field__label" for="notes">Notes <span class="muted">(optional)</span></label>
        <textarea class="textarea" id="notes" name="notes"><?= e($old['notes']) ?></textarea>
      </div>

    </div>

    <div class="card__foot" style="justify-content:flex-end;">
      <a class="btn btn--ghost" href="<?= e(url($editing ? 'pages/payment-view.php?id=' . $editId : 'pages/payments.php')) ?>">Cancel</a>
      <button class="btn btn--primary" type="submit">
        <i class="fa-solid fa-check"></i> <?= $editing ? 'Save changes' : 'Record payment' ?>
      </button>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../includes/app-footer.php'; ?>