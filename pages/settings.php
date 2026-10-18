<?php
require_once __DIR__ . '/../includes/auth.php';
require_business();

$biz = current_business();
$errors = [];
$old = [
    'business_name'        => $biz['business_name'],
    'business_email'       => $biz['business_email'],
    'phone'                => $biz['phone'] ?? '',
    'address'              => $biz['address'] ?? '',
    'default_currency'     => $biz['default_currency'],
    'bank_name'            => $biz['bank_name'] ?? '',
    'bank_account_name'    => $biz['bank_account_name'] ?? '',
    'bank_account_number'  => $biz['bank_account_number'] ?? '',
    'bank_instructions'    => $biz['bank_instructions'] ?? '',
    'payment_url'          => $biz['payment_url'] ?? '',
    'crypto_network'       => $biz['crypto_network'] ?? '',
    'crypto_address'       => $biz['crypto_address'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify(post('_csrf'))) {
        $errors['form'] = 'Session expired. Please try again.';
    } else {
        foreach ($old as $k => $_) {
            $old[$k] = (string) post($k, '');
        }

        $name    = clean($old['business_name']);
        $email   = clean($old['business_email']);
        $phone   = clean($old['phone']);
        $address = clean($old['address']);
        $code    = clean($old['default_currency']);

        $bankName   = clean($old['bank_name']);
        $bankAccN   = clean($old['bank_account_name']);
        $bankAccNo  = clean($old['bank_account_number']);
        $bankInstr  = clean($old['bank_instructions']);
        $payUrl     = clean($old['payment_url']);
        $cryptoNet  = clean($old['crypto_network']);
        $cryptoAddr = clean($old['crypto_address']);

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

        if ($payUrl && !filter_var($payUrl, FILTER_VALIDATE_URL)) {
            $errors['payment_url'] = 'Enter a valid URL (including https://).';
        } elseif ($payUrl && !preg_match('#^https?://#i', $payUrl)) {
            $errors['payment_url'] = 'URL must start with http:// or https://.';
        }

        if (!$errors) {
            $stmt = db()->prepare(
                'UPDATE business_profiles
                 SET business_name = ?, business_email = ?, phone = ?, address = ?, default_currency = ?,
                     bank_name = ?, bank_account_name = ?, bank_account_number = ?, bank_instructions = ?,
                     payment_url = ?, crypto_network = ?, crypto_address = ?
                 WHERE user_id = ?'
            );
            $stmt->execute([
                $name, $email, $phone, $address, $code,
                $bankName, $bankAccN, $bankAccNo, $bankInstr,
                $payUrl, $cryptoNet, $cryptoAddr,
                current_user_id(),
            ]);
            flash_set('success', 'Settings updated.');
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
    <p class="page__subtitle">Business details, payment instructions, and preferences.</p>
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

<form method="post" novalidate>
  <?= csrf_field() ?>

  <div class="card" style="max-width:820px;margin-bottom:20px;">
    <div class="card__head"><h2 class="card__title">Business details</h2></div>
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

      <div class="field <?= isset($errors['default_currency']) ? 'field--error' : '' ?>" style="margin-bottom:0;">
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
  </div>

  <div class="card" style="max-width:820px;margin-bottom:20px;">
    <div class="card__head"><h2 class="card__title">Payment instructions</h2></div>
    <div class="card__body">
      <p class="muted" style="margin-top:0;font-size:.85rem;">
        Shown on invoice previews and printouts. Recording a payment is always manual — QIP never auto-marks invoices paid.
      </p>

      <div class="grid-2">
        <div class="field">
          <label class="field__label" for="bank_name">Bank name <span class="muted">(optional)</span></label>
          <input class="input" type="text" id="bank_name" name="bank_name" value="<?= e($old['bank_name']) ?>">
        </div>

        <div class="field">
          <label class="field__label" for="bank_account_name">Account name <span class="muted">(optional)</span></label>
          <input class="input" type="text" id="bank_account_name" name="bank_account_name" value="<?= e($old['bank_account_name']) ?>">
        </div>
      </div>

      <div class="field">
        <label class="field__label" for="bank_account_number">Account number <span class="muted">(optional)</span></label>
        <input class="input" type="text" id="bank_account_number" name="bank_account_number" value="<?= e($old['bank_account_number']) ?>">
      </div>

      <div class="field">
        <label class="field__label" for="bank_instructions">Bank transfer instructions <span class="muted">(optional)</span></label>
        <textarea class="textarea" id="bank_instructions" name="bank_instructions"
                  placeholder="Branch, SWIFT/IBAN, references the client should include."><?= e($old['bank_instructions']) ?></textarea>
      </div>

      <div class="field <?= isset($errors['payment_url']) ? 'field--error' : '' ?>">
        <label class="field__label" for="payment_url">Payment URL <span class="muted">(optional)</span></label>
        <input class="input" type="url" id="payment_url" name="payment_url"
               value="<?= e($old['payment_url']) ?>" placeholder="https://…">
        <div class="muted" style="font-size:.8rem;margin-top:6px;">
          Shown as a link only. Opening it does not mark anything as paid.
        </div>
        <?php if (isset($errors['payment_url'])): ?>
          <div class="field__error"><?= e($errors['payment_url']) ?></div>
        <?php endif; ?>
      </div>

      <div class="grid-2">
        <div class="field">
          <label class="field__label" for="crypto_network">Crypto network <span class="muted">(optional)</span></label>
          <input class="input" type="text" id="crypto_network" name="crypto_network"
                 value="<?= e($old['crypto_network']) ?>" placeholder="e.g. BTC, ETH, USDT-TRC20">
        </div>

        <div class="field">
          <label class="field__label" for="crypto_address">Wallet address <span class="muted">(optional)</span></label>
          <input class="input" type="text" id="crypto_address" name="crypto_address"
                 value="<?= e($old['crypto_address']) ?>">
        </div>
      </div>

    </div>

    <div class="card__foot" style="justify-content:flex-end;">
      <button class="btn btn--primary" type="submit">
        <i class="fa-solid fa-check"></i> Save changes
      </button>
    </div>
  </div>
</form>

<?php include __DIR__ . '/../includes/app-footer.php'; ?>