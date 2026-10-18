<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/invoices.php';
require_once __DIR__ . '/../includes/payments.php';
require_business();

$id = (int) get('id', 0);
$p  = payment_get($id);

if (!$p) {
    http_response_code(404);
    exit('Receipt not found.');
}

$inv     = invoice_get((int) $p['invoice_id']);
$paid    = invoice_paid_total((int) $p['invoice_id']);
$balance = $inv ? max(0.0, round((float) $inv['total'] - $paid, 2)) : 0.0;
$biz     = current_business();

$currency = $inv['currency'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($p['receipt_number'] ?: 'Receipt') ?> — Receipt</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= e(url('assets/css/variables.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/base.css')) ?>">
<style>
  body { background:#f3f5f7; padding:32px 16px; }
  .doc { background:#fff; max-width:820px; margin:0 auto; padding:56px 56px 40px; border-radius:12px; box-shadow:0 8px 24px rgba(15,23,42,.06); color:var(--text-light); }
  .doc__top { display:flex; justify-content:space-between; gap:32px; margin-bottom:40px; }
  .doc__brand { display:flex; align-items:center; gap:10px; font-family:var(--font-display); font-weight:700; font-size:1.35rem; letter-spacing:-.03em; color:var(--text-light); }
  .doc__brand-logo { width:36px; height:36px; border-radius:10px; background:linear-gradient(135deg,#4ade80,#16a34a); display:grid; place-items:center; color:#06190f; font-size:1rem; font-weight:800; flex-shrink: 0; }
  .doc__biz { margin-top:16px; font-size:.85rem; color:var(--text-light-muted); line-height:1.7; }
  .doc__meta { text-align:right; }
  .doc__meta h1 { font-family:var(--font-display); font-size:2rem; letter-spacing:-.03em; margin:0 0 4px; color:var(--text-light); }
  .doc__num { color:var(--text-light-muted); font-size:.95rem; }
  .doc__status { display:inline-block; margin-top:12px; padding:4px 12px; border-radius:9999px; font-size:.72rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; background:rgba(34,197,94,.12); color:#15803d; }
  .doc__grid { display:grid; grid-template-columns:1fr 1fr; gap:32px; margin-bottom:32px; }
  .doc__grid small { display:block; font-size:.7rem; text-transform:uppercase; letter-spacing:.1em; color:var(--text-light-muted); font-weight:600; margin-bottom:6px; }
  .doc__grid strong { display:block; font-size:.95rem; color:var(--text-light); font-weight:600; }
  .doc__grid > div > div { margin-bottom:14px; }

  .receipt-box { border:1px solid var(--light-200); border-radius:10px; padding:20px 24px; margin-bottom:32px; }
  .receipt-box .row { display:flex; justify-content:space-between; padding:8px 0; font-size:.92rem; color:var(--text-light-muted); }
  .receipt-box .row b { color:var(--text-light); font-weight:600; font-variant-numeric:tabular-nums; text-align:right; }
  .receipt-box .row.grand { border-top:1px solid var(--light-200); margin-top:6px; padding-top:14px; font-size:1.05rem; color:var(--text-light); }
  .receipt-box .row.grand b { font-family:var(--font-display); font-size:1.5rem; }

  .doc__notes { margin-top:32px; padding-top:24px; border-top:1px solid var(--light-200); }
  .doc__notes h3 { font-family:var(--font-display); font-size:1rem; margin:0 0 8px; color:var(--text-light); }
  .doc__notes p { font-size:.9rem; color:var(--text-light-muted); white-space:pre-wrap; margin:0 0 20px; }

  .doc__print { text-align:center; margin:24px auto 0; }
  
  @media (max-width: 640px) {
    .doc { padding:32px 24px; }
    .doc__top { flex-direction: column; gap:24px; }
    .doc__meta { text-align: left; }
    .doc__grid { grid-template-columns: 1fr; gap:16px; }
    .doc__grid > div[style*="text-align:right"] { text-align: left !important; }
    .receipt-box { padding:16px; }
  }

  @media print {
    @page { margin: 14mm; }
    body { background:#fff; padding:0; }
    .doc { box-shadow:none; border-radius:0; padding:0; max-width:none; margin:0; }
    .doc__print { display:none; }
    .doc__top, .doc__grid, .doc__notes, .receipt-box { page-break-inside: avoid; }
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
      <h1>RECEIPT</h1>
      <div class="doc__num"><?= e($p['receipt_number'] ?: '—') ?></div>
      <div class="doc__status">Recorded</div>
    </div>
  </div>

  <div class="doc__grid">
    <div>
      <div>
        <small>Received from</small>
        <strong><?= e($p['client_name']) ?></strong>
      </div>
    </div>
    <div style="text-align:right;">
      <div><small>Payment date</small><strong><?= e(date('j M Y', strtotime($p['payment_date']))) ?></strong></div>
      <div><small>Method</small><strong><?= e(PAYMENT_METHODS[$p['method']] ?? ucfirst($p['method'])) ?></strong></div>
      <?php if ($p['reference']): ?>
        <div><small>Reference</small><strong><?= e($p['reference']) ?></strong></div>
      <?php endif; ?>
      <div><small>Currency</small><strong><?= e($currency) ?></strong></div>
    </div>
  </div>

  <div class="receipt-box">
    <div class="row"><span>Amount received</span><b><?= e($currency) ?> <?= e(number_format((float) $p['amount'], 2)) ?></b></div>
    <?php if ($inv): ?>
      <div class="row"><span>Against invoice</span><b><?= e($inv['number']) ?></b></div>
      <div class="row"><span>Invoice total</span><b><?= e($currency) ?> <?= e(number_format((float) $inv['total'], 2)) ?></b></div>
      <div class="row"><span>Total paid to date</span><b><?= e($currency) ?> <?= e(number_format($paid, 2)) ?></b></div>
      <div class="row grand"><span>Remaining balance</span><b><?= e($currency) ?> <?= e(number_format($balance, 2)) ?></b></div>
    <?php endif; ?>
  </div>

  <?php 
    $showNotes = $p['notes'];
    $showInstructions = ($inv && $inv['payment_terms'] && $balance > 0.0001);
  ?>
  <?php if ($showNotes || $showInstructions): ?>
    <div class="doc__notes">
      <?php if ($showNotes): ?>
        <h3>Notes</h3>
        <p><?= e($p['notes']) ?></p>
      <?php endif; ?>
      <?php if ($showInstructions): ?>
        <h3>Payment instructions</h3>
        <p><?= e($inv['payment_terms']) ?></p>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <div class="doc__notes" style="margin-top:24px;padding-top:16px;">
    <p style="font-size:.8rem;margin:0;">
      This receipt confirms a payment was recorded in QIP against the invoice above.
      It does not constitute independent verification of the money transfer.
    </p>
  </div>

  <div class="doc__print">
    <button class="btn btn--primary" type="button" onclick="window.print()">
      <i class="fa-solid fa-print"></i> Print / Save as PDF
    </button>
  </div>

</div>

</body>
</html>