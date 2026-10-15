<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/clients.php';
require_business();

$editId = (int) get('id', 0);
$editing = $editId > 0;

$existing = null;
if ($editing) {
    $existing = client_get($editId);
    if (!$existing) {
        flash_set('error', 'Client not found.');
        redirect('pages/clients.php');
    }
}

$errors = [];
$old = [
    'name'    => $existing['name']    ?? '',
    'company' => $existing['company'] ?? '',
    'email'   => $existing['email']   ?? '',
    'phone'   => $existing['phone']   ?? '',
    'address' => $existing['address'] ?? '',
    'notes'   => $existing['notes']   ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify(post('_csrf'))) {
        $errors['form'] = 'Session expired. Please try again.';
    } else {
        foreach ($old as $k => $_) {
            $old[$k] = (string) post($k, '');
        }

        [$data, $errors] = client_validate($old);

        if (!$errors) {
            if ($editing) {
                client_update($editId, $data);
                flash_set('success', 'Client updated.');
                redirect('pages/client-view.php?id=' . $editId);
            } else {
                $newId = client_create($data);
                flash_set('success', 'Client added.');
                redirect('pages/client-view.php?id=' . $newId);
            }
        }
    }
}

$page_title = $editing ? 'Edit client' : 'Add client';
include __DIR__ . '/../includes/app-header.php';
?>

<div class="page__head">
  <div>
    <h1 class="page__title"><?= $editing ? 'Edit client' : 'Add client' ?></h1>
    <p class="page__subtitle">
      <?= $editing ? 'Update the details for this client.' : 'Only the name is required — add the rest later.' ?>
    </p>
  </div>
  <div class="page__actions">
    <a class="btn btn--ghost" href="<?= e(url($editing ? 'pages/client-view.php?id=' . $editId : 'pages/clients.php')) ?>">
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

      <div class="field <?= isset($errors['name']) ? 'field--error' : '' ?>">
        <label class="field__label" for="name">Client name</label>
        <input class="input" type="text" id="name" name="name"
               value="<?= e($old['name']) ?>" required autofocus
               placeholder="e.g. Ada Lovelace">
        <?php if (isset($errors['name'])): ?>
          <div class="field__error"><?= e($errors['name']) ?></div>
        <?php endif; ?>
      </div>

      <div class="field">
        <label class="field__label" for="company">Company <span class="muted">(optional)</span></label>
        <input class="input" type="text" id="company" name="company"
               value="<?= e($old['company']) ?>">
      </div>

      <div class="grid-2">
        <div class="field <?= isset($errors['email']) ? 'field--error' : '' ?>">
          <label class="field__label" for="email">Email <span class="muted">(optional)</span></label>
          <input class="input" type="email" id="email" name="email"
                 value="<?= e($old['email']) ?>">
          <?php if (isset($errors['email'])): ?>
            <div class="field__error"><?= e($errors['email']) ?></div>
          <?php endif; ?>
        </div>

        <div class="field">
          <label class="field__label" for="phone">Phone <span class="muted">(optional)</span></label>
          <input class="input" type="tel" id="phone" name="phone"
                 value="<?= e($old['phone']) ?>">
        </div>
      </div>

      <div class="field">
        <label class="field__label" for="address">Billing address <span class="muted">(optional)</span></label>
        <textarea class="textarea" id="address" name="address"
                  placeholder="Street, City, Country"><?= e($old['address']) ?></textarea>
      </div>

      <div class="field" style="margin-bottom:0;">
        <label class="field__label" for="notes">Notes <span class="muted">(optional)</span></label>
        <textarea class="textarea" id="notes" name="notes"
                  placeholder="Anything worth remembering about this client."><?= e($old['notes']) ?></textarea>
      </div>

    </div>

    <div class="card__foot" style="justify-content:space-between;">
      <?php if ($editing): ?>
        <a class="btn btn--danger"
           href="<?= e(url('pages/client-delete.php?id=' . $editId)) ?>"
           onclick="return false;"
           data-delete-client="<?= (int) $editId ?>">
          <i class="fa-solid fa-trash"></i> Delete
        </a>
      <?php else: ?>
        <span></span>
      <?php endif; ?>
      <div class="row" style="gap:8px;">
        <a class="btn btn--ghost" href="<?= e(url($editing ? 'pages/client-view.php?id=' . $editId : 'pages/clients.php')) ?>">Cancel</a>
        <button class="btn btn--primary" type="submit">
          <i class="fa-solid fa-check"></i> <?= $editing ? 'Save changes' : 'Add client' ?>
        </button>
      </div>
    </div>
  </form>
</div>

<?php if ($editing): ?>
<form method="post" action="<?= e(url('pages/client-delete.php')) ?>"
      id="delete-client-form" style="display:none;">
  <?= csrf_field() ?>
  <input type="hidden" name="id" value="<?= (int) $editId ?>">
</form>
<script>
  document.querySelector('[data-delete-client]')?.addEventListener('click', function (e) {
    e.preventDefault();
    if (confirm('Delete this client? This cannot be undone.')) {
      document.getElementById('delete-client-form').submit();
    }
  });
</script>
<?php endif; ?>

<?php include __DIR__ . '/../includes/app-footer.php'; ?>