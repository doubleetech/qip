<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/invoices.php';
require_once __DIR__ . '/../includes/clients.php';
require_business();

$editId  = (int) get('id', 0);
$editing = $editId > 0;

$existing = null;
$existingItems = [];
if ($editing) {
    $existing = invoice_get($editId);
    if (!$existing) {
        flash_set('error', 'Invoice not found.');
        redirect('pages/invoices.php');
    }
    $existingItems = invoice_items($editId);
}

$biz = current_business();
$clients = clients_list();
if (!$clients) {
    flash_set('info', 'Add a client first — an invoice needs someone to bill.');
    redirect('pages/clients.php');
}

$currencies = db()->query('SELECT code, name, symbol FROM currencies ORDER BY code ASC')->fetchAll();

$old = [
    'client_id'      => $existing['client_id']      ?? (int) get('client_id', 0),
    'issue_date'     => $existing['issue_date']     ?? date('Y-m-d'),
    'due_date'       => $existing['due_date']       ?? date('Y-m-d', strtotime('+14 days')),
    'currency'       => $existing['currency']       ?? $biz['default_currency'],
    'discount_type'  => $existing['discount_type']  ?? 'none',
    'discount_value' => $existing['discount_value'] ?? '0.00',
    'tax_rate'       => $existing['tax_rate']       ?? '0.00',
    'notes'          => $existing['notes']          ?? '',
    'terms'          => $existing['payment_terms']  ?? '',
];

$items = $existingItems ?: [
    ['description' => '', 'quantity' => '1', 'unit_price' => ''],
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify(post('_csrf'))) {
        $errors['form'] = 'Session expired. Please try again.';
    } else {
        $old['client_id']      = (int)    post('client_id', 0);
        $old['issue_date']     = (string) post('issue_date', '');
        $old['due_date']       = (string) post('due_date', '');
        $old['currency']       = (string) post('currency', '');
        $old['discount_type']  = (string) post('discount_type', 'none');
        $old['discount_value'] = (string) post('discount_value', '0');
        $old['tax_rate']       = (string) post('tax_rate', '0');
        $old['notes']          = (string) post('notes', '');
        $old['terms']          = (string) post('terms', '');

        $rawItems = $_POST['items'] ?? [];
        if (!is_array($rawItems)) $rawItems = [];

        $validatorInput = $old;
        $validatorInput['valid_until'] = $old['due_date'];
        [$meta, $errors] = doc_validate_input($validatorInput);
        $meta['due_date'] = $meta['valid_until'];
        unset($meta['valid_until']);

        if (!isset($errors['client_id'])) {
            $check = client_get((int) $meta['client_id']);
            if (!$check) $errors['client_id'] = 'That client does not belong to you.';
        }

        if (!isset($errors['currency'])) {
            $cs = db()->prepare('SELECT 1 FROM currencies WHERE code = ? LIMIT 1');
            $cs->execute([$meta['currency']]);
            if (!$cs->fetchColumn()) $errors['currency'] = 'Unknown currency.';
        }

        $hasItem = false;
        foreach ($rawItems as $r) {
            if (is_array($r) && trim((string) ($r['description'] ?? '')) !== '') {
                $hasItem = true; break;
            }
        }
        if (!$hasItem) $errors['items'] = 'Add at least one line item.';

        if (!$errors) {
            if ($editing) {
                invoice_update($editId, $meta + ['status' => $existing['status']], $rawItems);
                flash_set('success', 'Invoice updated.');
                redirect('pages/invoice-view.php?id=' . $editId);
            } else {
                $newId = invoice_create($meta + ['status' => 'draft'], $rawItems);
                flash_set('success', 'Invoice created.');
                redirect('pages/invoice-view.php?id=' . $newId);
            }
        } else {
            $items = [];
            foreach ($rawItems as $r) {
                if (!is_array($r)) continue;
                $items[] = [
                    'description' => (string) ($r['description'] ?? ''),
                    'quantity'    => (string) ($r['quantity']    ?? ''),
                    'unit_price'  => (string) ($r['unit_price']  ?? ''),
                ];
            }
            if (!$items) $items = [['description'=>'','quantity'=>'1','unit_price'=>'']];
        }
    }
}

$hasDiscount = $old['discount_type'] !== 'none' || (float) $old['discount_value'] > 0 || (float) $old['tax_rate'] > 0;
$hasExtras   = ($old['due_date'] !== date('Y-m-d', strtotime('+14 days'))) || $old['notes'] || $old['terms'] || $old['currency'] !== $biz['default_currency'];

$page_title = $editing ? 'Edit ' . $existing['number'] : 'New invoice';
$page_css   = ['app.css', 'form-polish.css'];
include __DIR__ . '/../includes/app-header.php';
?>

<div class="page__head">
  <div>
    <h1 class="page__title"><?= e($page_title) ?></h1>
    <p class="page__subtitle">
      <?= $editing ? 'Changes save immediately.' : 'Fill in the details, then save.' ?>
    </p>
  </div>
  <div class="page__actions">
    <button class="preview-toggle" type="button" id="preview-toggle">
      <i class="fa-solid fa-eye"></i> Preview
    </button>
    <a class="btn btn--ghost" href="<?= e(url($editing ? 'pages/invoice-view.php?id=' . $editId : 'pages/invoices.php')) ?>">
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

<form method="post" novalidate id="doc-form"
      data-doc-type="Invoice"
      data-doc-number="<?= $editing ? e($existing['number']) : '' ?>"
      data-currency="<?= e($old['currency']) ?>"
      data-biz-name="<?= e($biz['business_name']) ?>"
      data-biz-email="<?= e($biz['business_email']) ?>"
      data-other-date-label="Due date">

  <?= csrf_field() ?>

  <div class="doc-form">

    <div class="doc-form__main">

      <section class="ds">
        <div class="ds__head">
          <h2 class="ds__title">Client &amp; dates</h2>
        </div>
        <div class="ds__body">
          <div class="field <?= isset($errors['client_id']) ? 'field--error' : '' ?>">
            <label class="field__label" for="client_id">Client</label>
            <select class="select" id="client_id" name="client_id" required autofocus>
              <option value="">Select a client…</option>
              <?php foreach ($clients as $c): ?>
                <option value="<?= (int) $c['id'] ?>" <?= (int) $old['client_id'] === (int) $c['id'] ? 'selected' : '' ?>>
                  <?= e($c['name']) ?><?= $c['company'] ? ' — ' . e($c['company']) : '' ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['client_id'])): ?>
              <div class="field__error"><?= e($errors['client_id']) ?></div>
            <?php endif; ?>
          </div>

          <div class="grid-2" style="margin-top:16px;">
            <div class="field <?= isset($errors['issue_date']) ? 'field--error' : '' ?>" style="margin-bottom:0;">
              <label class="field__label" for="issue_date">Issue date</label>
              <input class="input" type="date" id="issue_date" name="issue_date"
                     value="<?= e($old['issue_date']) ?>" required>
              <?php if (isset($errors['issue_date'])): ?>
                <div class="field__error"><?= e($errors['issue_date']) ?></div>
              <?php endif; ?>
            </div>

            <div class="field" style="margin-bottom:0;">
              <label class="field__label" for="due_date">Due date <span class="muted">(optional)</span></label>
              <input class="input" type="date" id="due_date" name="due_date"
                     value="<?= e($old['due_date']) ?>">
            </div>
          </div>
        </div>
      </section>

      <section class="ds">
        <div class="ds__head">
          <h2 class="ds__title">Line items</h2>
        </div>
        <div class="ds__body">

          <?php if (isset($errors['items'])): ?>
            <div class="flash flash--error" style="margin-bottom:12px;">
              <i class="fa-solid fa-circle-exclamation"></i>
              <span><?= e($errors['items']) ?></span>
            </div>
          <?php endif; ?>

          <table class="line-items" id="line-items">
            <thead>
              <tr>
                <th class="col-desc">Description</th>
                <th class="col-qty num">Qty</th>
                <th class="col-price num">Unit price</th>
                <th class="col-total num">Line total</th>
                <th class="col-x"></th>
              </tr>
            </thead>
            <tbody>
              <?php $idx = 0; foreach ($items as $it): ?>
                <tr>
                  <td class="col-desc">
                    <input type="text" name="items[<?= $idx ?>][description]"
                           value="<?= e($it['description']) ?>"
                           placeholder="What are you charging for?">
                  </td>
                  <td class="col-qty">
                    <input type="number" name="items[<?= $idx ?>][quantity]"
                           value="<?= e($it['quantity']) ?>"
                           min="0" step="0.01" placeholder="Qty" aria-label="Quantity">
                  </td>
                  <td class="col-price">
                    <input type="number" name="items[<?= $idx ?>][unit_price]"
                           value="<?= e($it['unit_price']) ?>"
                           min="0" step="0.01" placeholder="Price" aria-label="Unit price">
                  </td>
                  <td class="col-total"><span class="line-total">0.00</span></td>
                  <td class="col-x">
                    <button type="button" class="remove-row" aria-label="Remove item">
                      <i class="fa-solid fa-xmark"></i>
                    </button>
                  </td>
                </tr>
              <?php $idx++; endforeach; ?>
            </tbody>
          </table>

          <template id="line-item-template">
            <tr>
              <td class="col-desc">
                <input type="text" name="items[__i__][description]" placeholder="What are you charging for?">
              </td>
              <td class="col-qty">
                <input type="number" name="items[__i__][quantity]" value="1" min="0" step="0.01" placeholder="Qty" aria-label="Quantity">
              </td>
              <td class="col-price">
                <input type="number" name="items[__i__][unit_price]" value="" min="0" step="0.01" placeholder="Price" aria-label="Unit price">
              </td>
              <td class="col-total"><span class="line-total">0.00</span></td>
              <td class="col-x">
                <button type="button" class="remove-row" aria-label="Remove item">
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </td>
            </tr>
          </template>

          <div class="row" style="margin-top:12px;">
            <button type="button" class="btn btn--ghost btn--sm" id="add-item">
              <i class="fa-solid fa-plus"></i> Add item
            </button>
          </div>

        </div>
      </section>

      <section class="ds <?= $hasDiscount ? '' : 'is-collapsed' ?>" data-collapsible>
        <div class="ds__head">
          <h2 class="ds__title">Discount &amp; tax</h2>
          <span class="ds__badge" <?= $hasDiscount ? '' : 'hidden' ?>>Applied</span>
          <button type="button" class="ds__toggle" aria-expanded="<?= $hasDiscount ? 'true' : 'false' ?>" aria-label="Toggle section">
            <i class="fa-solid fa-chevron-down"></i>
          </button>
        </div>
        <div class="ds__body">
          <div class="grid-3">
            <div class="field" style="margin-bottom:0;">
              <label class="field__label" for="discount_type">Discount type</label>
              <select class="select" id="discount_type" name="discount_type">
                <option value="none"    <?= $old['discount_type'] === 'none'    ? 'selected' : '' ?>>None</option>
                <option value="percent" <?= $old['discount_type'] === 'percent' ? 'selected' : '' ?>>Percent</option>
                <option value="fixed"   <?= $old['discount_type'] === 'fixed'   ? 'selected' : '' ?>>Fixed amount</option>
              </select>
            </div>

            <div class="field <?= isset($errors['discount_value']) ? 'field--error' : '' ?>" style="margin-bottom:0;">
              <label class="field__label" for="discount_value">Discount value</label>
              <input class="input" type="number" id="discount_value" name="discount_value"
                     value="<?= e($old['discount_value']) ?>" min="0" step="0.01" placeholder="0.00">
              <?php if (isset($errors['discount_value'])): ?>
                <div class="field__error"><?= e($errors['discount_value']) ?></div>
              <?php endif; ?>
            </div>

            <div class="field" style="margin-bottom:0;">
              <label class="field__label" for="tax_rate">Tax rate % <span class="muted">(optional)</span></label>
              <input class="input" type="number" id="tax_rate" name="tax_rate"
                     value="<?= e($old['tax_rate']) ?>" min="0" step="0.01" placeholder="0.00">
            </div>
          </div>
        </div>
      </section>

      <section class="ds <?= $hasExtras ? '' : 'is-collapsed' ?>" data-collapsible>
        <div class="ds__head">
          <h2 class="ds__title">More details</h2>
          <span class="ds__badge" <?= $hasExtras ? '' : 'hidden' ?>>Added</span>
          <button type="button" class="ds__toggle" aria-expanded="<?= $hasExtras ? 'true' : 'false' ?>" aria-label="Toggle section">
            <i class="fa-solid fa-chevron-down"></i>
          </button>
        </div>
        <div class="ds__body">
          <div class="field" style="margin-bottom:16px;">
            <label class="field__label" for="currency">Currency</label>
            <select class="select" id="currency" name="currency" required>
              <?php foreach ($currencies as $c): ?>
                <option value="<?= e($c['code']) ?>" <?= $old['currency'] === $c['code'] ? 'selected' : '' ?>>
                  <?= e($c['code']) ?> — <?= e($c['name']) ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if (isset($errors['currency'])): ?>
              <div class="field__error"><?= e($errors['currency']) ?></div>
            <?php endif; ?>
          </div>

          <div class="field" style="margin-bottom:16px;">
            <label class="field__label" for="notes">Notes <span class="muted">(optional)</span></label>
            <textarea class="textarea" id="notes" name="notes"
                      placeholder="Anything the client should know."><?= e($old['notes']) ?></textarea>
          </div>

          <div class="field" style="margin-bottom:0;">
            <label class="field__label" for="terms">Payment instructions <span class="muted">(optional)</span></label>
            <textarea class="textarea" id="terms" name="terms"
                      placeholder="Bank details, payment terms, how to pay."><?= e($old['terms']) ?></textarea>
          </div>
        </div>
      </section>

    </div>

    <aside class="doc-preview" aria-hidden="true">
      <?php include __DIR__ . '/_doc-preview.php'; ?>
    </aside>

  </div>

  <div class="doc-bar">
    <span class="doc-bar__status">
      <?= $editing ? 'Editing ' . e($existing['number']) : 'New invoice' ?>
    </span>
    <div class="doc-bar__spacer"></div>
    <a class="btn btn--ghost" href="<?= e(url($editing ? 'pages/invoice-view.php?id=' . $editId : 'pages/invoices.php')) ?>">Cancel</a>
    <button class="btn btn--primary" type="submit">
      <i class="fa-solid fa-check"></i> <?= $editing ? 'Save changes' : 'Save invoice' ?>
    </button>
  </div>

</form>

<div class="preview-modal" id="preview-modal" role="dialog" aria-modal="true" aria-label="Document preview">
  <div class="preview-modal__panel">
    <div class="preview-modal__head">
      <h2 class="modal__title" style="margin:0;">Preview</h2>
      <button type="button" class="modal__close" data-close-preview aria-label="Close preview">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
    <div class="preview-modal__body">
      <?php $pvModal = true; include __DIR__ . '/_doc-preview.php'; ?>
    </div>
  </div>
</div>

<?php include __DIR__ . '/../includes/app-footer.php'; ?>
<script src="<?= e(url('assets/js/line-items.js')) ?>"></script>
<script src="<?= e(url('assets/js/form-polish.js')) ?>"></script>