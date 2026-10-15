<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/quotes.php';
require_business();

$id = (int) get('id', 0);
$quote = quote_get($id);
if (!$quote) {
    http_response_code(404);
    exit('Quote not found.');
}

$items = quote_items($id);
$biz = current_business();
$eff = quote_effective_status($quote);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($quote['number']) ?> — Quote</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/variables.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/base.css')) ?>">
<style>
  body { background:#f3f5f7; padding:32px 16px; }

  .doc {
    background:#fff;
    max-width:820px;
    margin:0 auto;
    padding:56px 56px 40px;
    border-radius:12px;
    box-shadow:0 8px 24px rgba(15,23,42,.06);
    color:var(--text-light);
  }

  .doc__top { display:flex; justify-content:space-between; gap:32px; margin-bottom:40px; }
  .doc__brand {
    display:flex; align-items:center; gap:10px;
    font-family:var(--font-display); font-weight:700;
    font-size:1.35rem; letter-spacing:-.03em; color:var(--text-light);
  }
  .doc__brand-logo {
    width:36px; height:36px; border-radius:10px;
    background:linear-gradient(135deg,#4ade80,#16a34a);
    display:grid; place-items:center;
    color:#06190f; font-size:1rem; font-weight:800;
  }
  .doc__biz { margin-top:16px; font-size:.85rem; color:var(--text-light-muted); line-height:1.7; }

  .doc__meta { text-align:right; }
  .doc__meta h1 {
    font-family:var(--font-display); font-size:2rem;
    letter-spacing:-.03em; margin:0 0 4px; color:var(--text-light);
  }
  .doc__num { color:var(--text-light-muted); font-size:.95rem; }
  .doc__status {
    display:inline-block; margin-top:12px;
    padding:4px 12px; border-radius:9999px;
    font-size:.72rem; font-weight:700; letter-spacing:.06em;
    text-transform:uppercase;
    background:rgba(34,197,94,.12); color:#15803d;
  }

  .doc__grid {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:32px;
    margin-bottom:40px;
  }
  .doc__grid small {
    display:block; font-size:.7rem; text-transform:uppercase;
    letter-spacing:.1em; color:var(--text-light-muted);
    font-weight:600; margin-bottom:6px;
  }
  .doc__grid strong { display:block; font-size:.95rem; color:var(--text-light); font-weight:600; }
  .doc__grid > div > div { margin-bottom:14px; }

  table.doc-table { width:100%; border-collapse:collapse; margin-bottom:32px; table-layout:fixed; }
  table.doc-table col.c-desc  { width:auto; }
  table.doc-table col.c-qty   { width:80px; }
  table.doc-table col.c-price { width:130px; }
  table.doc-table col.c-total { width:130px; }
  table.doc-table th {
    text-align:left;
    font-size:.7rem; text-transform:uppercase;
    letter-spacing:.1em; color:var(--text-light-muted);
    font-weight:600; padding:10px 0;
    border-bottom:2px solid var(--light-200);
  }
  table.doc-table th.num { text-align:right; }
  table.doc-table td {
    padding:14px 0;
    border-bottom:1px solid var(--light-100);
    font-size:.92rem;
    color:var(--text-light);
    word-break:break-word;
  }
  table.doc-table td.num {
    text-align:right;
    font-variant-numeric:tabular-nums;
    white-space:nowrap;
  }

  .doc__totals { margin-left:auto; width:100%; max-width:320px; }
  .doc__totals .row {
    display:flex; justify-content:space-between;
    padding:8px 0; font-size:.9rem; color:var(--text-light-muted);
  }
  .doc__totals .row b {
    color:var(--text-light); font-variant-numeric:tabular-nums;
    font-weight:600;
  }
  .doc__totals .row.grand {
    border-top:1px solid var(--light-200);
    padding-top:14px; margin-top:6px;
    font-size:1.1rem; color:var(--text-light);
  }
  .doc__totals .row.grand b {
    font-family:var(--font-display); font-size:1.5rem;
  }

  .doc__notes {
    margin-top:40px;
    padding-top:24px;
    border-top:1px solid var(--light-200);
  }
  .doc__notes h3 {
    font-family:var(--font-display); font-size:1rem;
    margin:0 0 8px; color:var(--text-light);
  }
  .doc__notes p {
    font-size:.9rem; color:var(--text-light-muted);
    white-space:pre-wrap; margin:0 0 20px;
  }

  .doc__print { text-align:center; margin:24px auto 0; }

  @media print {
    @page { margin: 14mm; }
    body { background:#fff; padding:0; }
    .doc {
      box-shadow:none;
      border-radius:0;
      padding:0;
      max-width:none;
      margin:0;
    }
    .doc__print { display:none; }

    .doc__top,
    .doc__grid,
    .doc__notes,
    .doc__totals { page-break-inside: avoid; }
    table.doc-table tr { page-break-inside: avoid; }
    .doc__notes { page-break-before: auto; }
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
      <h1>QUOTE</h1>
      <div class="doc__num"><?= e($quote['number']) ?></div>
      <div class="doc__status"><?= e(ucfirst($eff)) ?></div>
    </div>
  </div>

  <div class="doc__grid">
    <div>
      <div>
        <small>Prepared for</small>
        <strong><?= e($quote['client_name']) ?></strong>
        <?php if ($quote['client_company']): ?>
          <div style="font-size:.9rem;color:var(--text-light-muted);"><?= e($quote['client_company']) ?></div>
        <?php endif; ?>
        <?php if ($quote['client_email']): ?>
          <div style="font-size:.9rem;color:var(--text-light-muted);"><?= e($quote['client_email']) ?></div>
        <?php endif; ?>
        <?php if ($quote['client_address']): ?>
          <div style="font-size:.9rem;color:var(--text-light-muted);white-space:pre-wrap;margin-top:6px;"><?= e($quote['client_address']) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <div style="text-align:right;">
      <div><small>Issue date</small><strong><?= e(date('j M Y', strtotime($quote['issue_date']))) ?></strong></div>
      <?php if ($quote['valid_until']): ?>
        <div><small>Valid until</small><strong><?= e(date('j M Y', strtotime($quote['valid_until']))) ?></strong></div>
      <?php endif; ?>
      <div><small>Currency</small><strong><?= e($quote['currency']) ?></strong></div>
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
    <div class="row"><span>Subtotal</span><b><?= e(number_format((float) $quote['subtotal'], 2)) ?></b></div>
    <?php if ($quote['discount_type'] !== 'none' && (float) $quote['discount_amount'] > 0): ?>
      <div class="row">
        <span>Discount<?= $quote['discount_type'] === 'percent' ? ' (' . e(number_format((float) $quote['discount_value'], 2)) . '%)' : '' ?></span>
        <b>- <?= e(number_format((float) $quote['discount_amount'], 2)) ?></b>
      </div>
    <?php endif; ?>
    <?php if ((float) $quote['tax_amount'] > 0): ?>
      <div class="row"><span>Tax (<?= e(number_format((float) $quote['tax_rate'], 2)) ?>%)</span><b><?= e(number_format((float) $quote['tax_amount'], 2)) ?></b></div>
    <?php endif; ?>
    <div class="row grand"><span>Total</span><b><?= e($quote['currency']) ?> <?= e(number_format((float) $quote['total'], 2)) ?></b></div>
  </div>

  <?php if ($quote['notes'] || $quote['terms']): ?>
    <div class="doc__notes">
      <?php if ($quote['notes']): ?><h3>Notes</h3><p><?= e($quote['notes']) ?></p><?php endif; ?>
      <?php if ($quote['terms']): ?><h3>Terms</h3><p><?= e($quote['terms']) ?></p><?php endif; ?>
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