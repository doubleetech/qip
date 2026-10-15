<?php
/** Shared document preview markup. */
?>
<div class="pv-doc" id="doc-preview<?= !empty($pvModal) ? '-modal' : '' ?>">
  <div class="pv-doc__head">
    <div class="pv-doc__biz" data-pv="biz">
      <?= e($biz['business_name']) ?>
      <small data-pv="biz-email"><?= e($biz['business_email']) ?></small>
    </div>
    <div class="pv-doc__num">
      <span data-pv="type">Quote</span>
      <strong data-pv="number">Draft</strong>
    </div>
  </div>

  <div class="pv-doc__meta">
    <div>
      <small>Client</small>
      <b data-pv="client" class="muted">No client selected</b>
    </div>
    <div style="text-align:right;">
      <div><small>Issue</small><b data-pv="issue">—</b></div>
      <div style="margin-top:8px;"><small data-pv="other-label">Valid until</small><b data-pv="other">—</b></div>
      <div style="margin-top:8px;"><small>Currency</small><b data-pv="currency">—</b></div>
    </div>
  </div>

  <table class="pv-doc__items">
    <thead>
      <tr>
        <th>Description</th>
        <th class="num">Qty</th>
        <th class="num">Unit</th>
        <th class="num">Total</th>
      </tr>
    </thead>
    <tbody data-pv="items">
      <tr><td class="muted" colspan="4" style="padding:16px 0;text-align:center;">No items yet</td></tr>
    </tbody>
  </table>

  <div class="pv-doc__totals">
    <div class="row"><span>Subtotal</span><b data-pv="subtotal">0.00</b></div>
    <div class="row" data-pv="discount-row" hidden>
      <span data-pv="discount-label">Discount</span>
      <b data-pv="discount">0.00</b>
    </div>
    <div class="row" data-pv="tax-row" hidden>
      <span data-pv="tax-label">Tax</span>
      <b data-pv="tax">0.00</b>
    </div>
    <div class="row grand">
      <span>Total</span>
      <b data-pv="total">0.00</b>
    </div>
  </div>

  <div class="pv-doc__notes" data-pv="notes-wrap" hidden>
    <strong>Notes</strong>
    <span data-pv="notes"></span>
  </div>

  <div class="pv-doc__notes" data-pv="terms-wrap" hidden>
    <strong>Terms</strong>
    <span data-pv="terms"></span>
  </div>
</div>