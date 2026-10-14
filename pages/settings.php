<?php
require_once __DIR__ . '/../includes/auth.php';
require_business();

$biz = current_business();
$errors = [];
$success = false;
$old = [
    'business_name'    => $biz['business_name'],
    'business_email'   => $biz['business_email'],
    'phone'            => $biz['phone'] ?? '',
    'address'          => $biz['address'] ?? '',
    'default_currency' => $biz['default_currency'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify(post('_csrf'))) {
        $errors['form'] = 'Session expired. Please try again.';
    } else {
        $old['business_name']    = (string) post('business_name', '');
        $old['business_email']   = (string) post('business_email', '');
        $old['phone']            = (string) post('phone', '');
        $old['address']          = (string) post('address', '');
        $old['default_currency'] = (string) post('default_currency', '');

        $name    = clean($old['business_name']);
        $email   = clean($old['business_email']);
        $phone   = clean($old['phone']);
        $address = clean($old['address']);
        $code    = clean($old['default_currency']);

        if (!$name)  $errors['business_name']  = 'Business name is required.';
        if (!$email) $errors['business_email'] = 'Business email is required.';
        elseif (!is_valid_email($email)) $errors['business_email'] = 'Enter a valid email address.';

        if ($code) {
            $stmt = db()->prepare('SELECT 1 FROM currencies WHERE code = ? LIMIT 1');
            $stmt->execute([$code]);
            if (!$stmt->fetchColumn()) $errors['default_currency'] = 'Choose a valid currency.';
        } else {
            $errors['default_currency'] = 'Choose a default currency.';
        }

        if (!$errors) {
            $stmt = db()->prepare(
                'UPDATE business_profiles
                 SET business_name = ?, business_email = ?, phone = ?, address = ?, default_currency = ?
                 WHERE user_id = ?'
            );
            $stmt->execute([$name, $email, $phone, $address, $code, current_user_id()]);
            flash_set('success', 'Business details updated.');
            redirect('pages/settings.php');
        }
    }
}

$currencies = db()->query('SELECT code, name, symbol FROM currencies ORDER BY code ASC')->fetchAll();
$selectedCurrency = null;
foreach ($currencies as $c) {
    if ($c['code'] === $old['default_currency']) { $selectedCurrency = $c; break; }
}
if (!$selectedCurrency) {
    $selectedCurrency = ['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$'];
}

$page_title = 'Settings';
$page_css   = ['app.css'];
$page_js    = ['currency-combo.js'];
include __DIR__ . '/../includes/app-header.php';
?>

<div class="page__head">
  <div>
    <h1 class="page__title">Settings</h1>
    <p class="page__subtitle">Business details and preferences.</p>
  </div>
</div>

<?php if (flash_has('success')): ?>
  <div class="flash flash--success">
    <i class="fa-solid fa-circle-check"></i>
    <span><?= e(flash_get('success')) ?></span>
  </div>
<?php endif; ?>

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

      <div class="field <?= isset($errors['business_name']) ? 'field--error' : '' ?>">
        <label class="field__label" for="business_name">Business name</label>
        <input class="input" type="text" id="business_name" name="business_name"
               value="<?= e($old['business_name']) ?>" required>
        <?php if (isset($errors['business_name'])): ?>
          <div class="field__error"><?= e($errors['business_name']) ?></div>
        <?php endif; ?>
      </div>

      <div class="grid-2">
        <div class="field <?= isset($errors['business_email']) ? 'field--error' : '' ?>">
          <label class="field__label" for="business_email">Business email</label>
          <input class="input" type="email" id="business_email" name="business_email"
                 value="<?= e($old['business_email']) ?>" required>
          <?php if (isset($errors['business_email'])): ?>
            <div class="field__error"><?= e($errors['business_email']) ?></div>
          <?php endif; ?>
        </div>

        <div class="field">
          <label class="field__label" for="phone">Phone <span class="muted">(optional)</span></label>
          <input class="input" type="tel" id="phone" name="phone" value="<?= e($old['phone']) ?>">
        </div>
      </div>

      <div class="field">
        <label class="field__label" for="address">Address <span class="muted">(optional)</span></label>
        <textarea class="textarea" id="address" name="address"><?= e($old['address']) ?></textarea>
      </div>

      <div class="field <?= isset($errors['default_currency']) ? 'field--error' : '' ?>">
        <label class="field__label" for="combo-trigger">Default currency</label>

        <div class="combo" id="currency-combo">
          <div class="input combo__trigger" id="combo-trigger" tabindex="0" role="combobox"
               aria-haspopup="listbox" aria-expanded="false" aria-controls="currency-menu">
            <span class="combo__code"><?= e($selectedCurrency['code']) ?></span>
            <span class="combo__label"><?= e($selectedCurrency['name']) ?></span>
            <span class="combo__symbol" style="margin-left:auto;margin-right:24px;"><?= e($selectedCurrency['symbol']) ?></span>
          </div>

          <input type="hidden" name="default_currency" id="default_currency" value="<?= e($selectedCurrency['code']) ?>">

          <div class="combo__menu" id="currency-menu" role="listbox" hidden>
            <input class="input combo__search" type="text" placeholder="Search currencies…"
                   autocomplete="off" style="margin-bottom:6px;">
            <?php foreach ($currencies as $c): ?>
              <div class="combo__option<?= $c['code'] === $selectedCurrency['code'] ? ' is-selected' : '' ?>"
                   role="option"
                   data-code="<?= e($c['code']) ?>"
                   data-name="<?= e($c['name']) ?>"
                   data-symbol="<?= e($c['symbol']) ?>">
                <span class="combo__code"><?= e($c['code']) ?></span>
                <span class="combo__name"><?= e($c['name']) ?></span>
                <span class="combo__symbol"><?= e($c['symbol']) ?></span>
              </div>
            <?php endforeach; ?>
            <div class="combo__empty" hidden>No matches.</div>
          </div>
        </div>

        <?php if (isset($errors['default_currency'])): ?>
          <div class="field__error"><?= e($errors['default_currency']) ?></div>
        <?php endif; ?>
      </div>

    </div>

    <div class="card__foot" style="justify-content:flex-end;">
      <button class="btn btn--primary" type="submit">
        <i class="fa-solid fa-check"></i> Save changes
      </button>
    </div>
  </form>
</div>

<?php include __DIR__ . '/../includes/app-footer.php'; ?>