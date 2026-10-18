<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/invoices.php';
require_once __DIR__ . '/../includes/payments.php';
require_business();

$id = (int) get('id', 0);
$inv = invoice_get($id);
if (!$inv) {
    http_response_code(404);
    exit('Invoice not found.');
}

$items   = invoice_items($id);
$biz     = current_business();
$paid    = invoice_paid_total($id);
$balance = max(0.0, round((float) $inv['total'] - $paid, 2));
$eff     = invoice_effective_status($inv, $paid);

$hasBank   = $biz['bank_name'] || $biz['bank_account_name'] || $biz['bank_account_number'] || $biz['bank_instructions'];
$hasPayUrl = !empty($biz['payment_url']);
$hasCrypto = $biz['crypto_network'] || $biz['crypto_address'];
$hasInstr  = $hasBank || $hasPayUrl || $hasCrypto;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($inv['number']) ?> — Invoice</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/variables.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/base.css')) ?>">
<style>
  body { background:#f3f5f7; padding:32px 16px; }
  .doc { background:#fff; max-width:820px; margin:0 auto; padding:56px 56px 40px; border-radius:12px; box-shadow:0 8px 24px rgba(15,23,42,.06); color:var(--text-light); }
  .doc__top { display:flex; justify-content:space-between; gap:32px; margin-bottom:40px; }
  .doc__brand { display:flex; align-items:center; gap:10px; font-family:var(--font-display); font-weight:700; font-size:1.35rem; letter-spacing:-.03em; color:var(--text-light); }
  .doc__brand-logo { width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,#4ade80,#16a34a); display:grid; place-items:center; color:#06190f; font-size:1rem; font-weight:800; }
  .doc__biz { margin-top:16px; font-size:.85rem; color:var(--text-light-muted); line-height:1.7; }
  .doc__meta { text-align:right; }
  .doc__meta h1 { font-family:var(--font-display); font-size:2rem; letter-spacing:-.03em; margin:0 0 4px; color:var(--text-light); }
  .doc__num { color:var(--text-light-muted); font-size:.95rem; }
  .doc__status { display:inline-block; margin-top:12px; padding:4px 12px; border-radius:9999px; font-size:.72rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; background:rgba(34,197,94,.12); color:#15803d; }
  .doc__grid { display:grid; grid-template-columns:1fr 1fr; gap:32px; margin-bottom:40px; }
  .doc__grid small { display:block; font-size:.7rem; text-transform:uppercase; letter-spacing:.1em; color:var(--text-light-muted); font-weight:600; margin-bottom:6px; }
  .doc__grid strong { display:block; font-size:.95rem; color:var(--text-light); font-weight:600; }
  .doc__grid > div > div { margin-bottom:14px; }
  table.doc-table { width:100%; border-collapse:collapse; margin-bottom:32px; table-layout:fixed; }
  table.doc-table col.c-desc  { width:auto; }
  table.doc-table col.c-qty   { width:80px; }
  table.doc-table col.c-price { width:130px; }
  table.doc-table col.c-total { width:130px; }
  table.doc-table th { text-align:left; font-size:.7rem; text-transform:uppercase; letter-spacing:.1em; color:var(--text-light-muted); font-weight:600; padding:10px 0; border-bottom:2px solid var(--light-200); }
  table.doc-table th.num { text-align:right; }
  table.doc-table td { padding:14px 0; border-bottom:1px solid var(--light-100); font-size:.92rem; color:var(--text-light); word-break:break-word; }
  table.doc-table td.num { text-align:right; font-variant-numeric:tabular-nums; white-space:nowrap; }
  .doc__totals { margin-left:auto; width:100%; max-width:320px; }
  .doc__totals .row { display:flex; justify-content:space-between; padding:8px 0; font-size:.9rem; color:var(--text-light-muted); }
  .doc__totals .row b { color:var(--text-light); font-variant-numeric:tabular-nums; font-weight:600; }
  .doc__totals .row.grand { border-top:1px solid var(--light-200); padding-top:14px; margin-top:6px; font-size:1.1rem; color:var(--text-light); }
  .doc__totals .row.grand b { font-family:var(--font-display); font-size:1.5rem; }
  .doc__totals .row.paid b { color:#15803d; }
  .doc__totals .row.balance b { color:#b91c1c; }
  .doc__notes { margin-top:40px; padding-top:24px; border-top:1px solid var(--light-200); }
  .doc__notes h3 { font-family:var(--font-display); font-size:1rem; margin:0 0 8px; color:var(--text-light); }
  .doc__notes p { font-size:.9rem; color:var(--text-light-muted); white-space:pre-wrap; margin:0 0 20px; }
  .doc__instr { margin-top:32px; padding:20px 24px; border:1px solid var(--light-200); border-radius:10px; }
  .doc__instr h3 { font-family:var(--font-display); font-size:1rem; margin:0 0 12px; }
  .doc__instr .row { display:flex; gap:8px; font-size:.9rem; color:var(--text-light-muted); margin-bottom:6px; }
  .doc__instr .row strong { color:var(--text-light); min-width:120px; }
  .doc__instr .row a { color:#15803d; word-break:break-all; }
  .doc__print { text-align:center; margin:24px auto 0; }
  @media print {
    @page { margin: 14mm; }
    body { background:#fff; padding:0; }
    .doc { box-shadow:none; border-radius:0; padding:0; max-width:none; margin:0; }
    .doc__print { display:none; }
    .doc__top, .doc__grid, .doc__notes, .doc__totals, .doc__instr { page-break-inside: avoid; }
    table.doc-table tr { page-break-inside: avoid; }
  }
</style>
</head>
<body>

<div class="doc">

  <div class="doc__top">
    <div>
      <div class="doc__brand">
        <span class="doc__brand-logo">Q</span>
        <span><?= e($biz['business_name']) ?></span>
      </div>
      <div class="doc__biz">
        <?php if ($biz['business_email']): ?><div><?= e($biz['business_email']) ?></div><?php endif; ?>
        <?php if ($biz['phone']): ?><div><?= e($biz['phone']) ?></div><?php endif; ?>
        <?php if ($biz['address']): ?><div style="white-space:pre-wrap;"><?= e($biz['address']) ?></div><?php endif; ?>
      </div>
    </div>
    <div class="doc__meta">
      <h1>INVOICE</h1>
      <div class="doc__num"><?= e($inv['number']) ?></div>
      <div class="doc__status"><?= e(str_replace('_', ' ', ucfirst($eff))) ?></div>
    </div>
  </div>

  <div class="doc__grid">
    <div>
      <div>
        <small>Billed to</small>
        <strong><?= e($inv['client_name']) ?></strong>
        <?php if ($inv['client_company']): ?>
          <div style="font-size:.9rem;color:var(--text-light-muted);"><?= e($inv['client_company']) ?></div>
        <?php endif; ?>
        <?php if ($inv['client_email']): ?>
          <div style="font-size:.9rem;color:var(--text-light-muted);"><?= e($inv['client_email']) ?></div>
        <?php endif; ?>
        <?php if ($inv['client_address']): ?>
          <div style="font-size:.9rem;color:var(--text-light-muted);white-space:pre-wrap;margin-top:6px;"><?= e($inv['client_address']) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <div style="text-align:right;">
      <div><small>Issue date</small><strong><?= e(date('j M Y', strtotime($inv['issue_date']))) ?></strong></div>
      <?php if ($inv['due_date']): ?>
        <div><small>Due date</small><strong><?= e(date('j M Y', strtotime($inv['due_date']))) ?></strong></div>
      <?php endif; ?>
      <div><small>Currency</small><strong><?= e($inv['currency']) ?></strong></div>
    </div>
  </div>

  <table class="doc-table">
    <colgroup>
      <col class="c-desc">
      <col class="c-qty">
      <col class="c-price">
      <col class="c-total">
    </colgroup>
    <thead>
      <tr>
        <th>Description</th>
        <th class="num">Qty</th>
        <th class="num">Unit price</th>
        <th class="num">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $it): ?>
        <tr>
          <td><?= e($it['description']) ?></td>
          <td class="num"><?= e(number_format((float) $it['quantity'], 2)) ?></td>
          <td class="num"><?= e(number_format((float) $it['unit_price'], 2)) ?></td>
          <td class="num"><?= e(number_format((float) $it['line_total'], 2)) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <div class="doc__totals">
    <div class="row"><span>Subtotal</span><b><?= e(number_format((float) $inv['subtotal'], 2)) ?></b></div>
    <?php if ($inv['discount_type'] !== 'none' && (float) $inv['discount_amount'] > 0): ?>
      <div class="row">
        <span>Discount<?= $inv['discount_type'] === 'percent' ? ' (' . e(number_format((float) $inv['discount_value'], 2)) . '%)' : '' ?></span>
        <b>- <?= e(number_format((float) $inv['discount_amount'], 2)) ?></b>
      </div>
    <?php endif; ?>
    <?php if ((float) $inv['tax_amount'] > 0): ?>
      <div class="row"><span>Tax (<?= e(number_format((float) $inv['tax_rate'], 2)) ?>%)</span><b><?= e(number_format((float) $inv['tax_amount'], 2)) ?></b></div>
    <?php endif; ?>
    <div class="row grand"><span>Total</span><b><?= e($inv['currency']) ?> <?= e(number_format((float) $inv['total'], 2)) ?></b></div>

    <?php if ($paid > 0): ?>
      <div class="row paid"><span>Paid</span><b>- <?= e($inv['currency']) ?> <?= e(number_format($paid, 2)) ?></b></div>
      <div class="row balance"><span>Balance due</span><b><?= e($inv['currency']) ?> <?= e(number_format($balance, 2)) ?></b></div>
    <?php endif; ?>
  </div>

  <?php if ($inv['notes'] || $inv['payment_terms']): ?>
    <div class="doc__notes">
      <?php if ($inv['notes']): ?><h3>Notes</h3><p><?= e($inv['notes']) ?></p><?php endif; ?>
      <?php if ($inv['payment_terms']): ?><h3>Payment instructions (invoice)</h3><p><?= e($inv['payment_terms']) ?></p><?php endif; ?>
    </div>
  <?php endif; ?>

  <?php if ($hasInstr): ?>
    <div class="doc__instr">
      <h3>How to pay</h3>

      <?php if ($hasBank): ?>
        <?php if ($biz['bank_name']): ?>
          <div class="row"><strong>Bank</strong><span><?= e($biz['bank_name']) ?></span></div>
        <?php endif; ?>
        <?php if ($biz['bank_account_name']): ?>
          <div class="row"><strong>Account name</strong><span><?= e($biz['bank_account_name']) ?></span></div>
        <?php endif; ?>
        <?php if ($biz['bank_account_number']): ?>
          <div class="row"><strong>Account number</strong><span><?= e($biz['bank_account_number']) ?></span></div>
        <?php endif; ?>
        <?php if ($biz['bank_instructions']): ?>
          <div class="row" style="display:block;white-space:pre-wrap;margin-top:6px;">
            <?= e($biz['bank_instructions']) ?>
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <?php if ($hasPayUrl): ?>
        <div class="row" style="margin-top:10px;">
          <strong>Pay online</strong>
          <span><a href="<?= e($biz['payment_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($biz['payment_url']) ?></a></span>
        </div>
      <?php endif; ?>

      <?php if ($hasCrypto): ?>
        <?php if ($biz['crypto_network']): ?>
          <div class="row" style="margin-top:10px;"><strong>Network</strong><span><?= e($biz['crypto_network']) ?></span></div>
        <?php endif; ?>
        <?php if ($biz['crypto_address']): ?>
          <div class="row"><strong>Wallet</strong><span style="word-break:break-all;"><?= e($biz['crypto_address']) ?></span></div>
        <?php endif; ?>
      <?php endif; ?>

      <div class="muted" style="font-size:.78rem;margin-top:12px;">
        Instructions only — QIP does not verify transfers or mark this invoice paid automatically.
      </div>
    </div>
  <?php endif; ?>

  <div class="doc__print">
    <button class="btn btn--primary" type="button" onclick="window.print()">
      <i class="fa-solid fa-print"></i> Print / Save as PDF
    </button>
  </div>

</div>

</body>
</html>