/* QIP — Document form polish: collapsible sections, live preview, mobile preview modal */
(function () {
  var form = document.getElementById('doc-form');
  if (!form) return;

  /* ---------- Collapsible sections ---------- */
  form.querySelectorAll('.ds[data-collapsible]').forEach(function (sec) {
    var head = sec.querySelector('.ds__head');
    var tog  = sec.querySelector('.ds__toggle');
    if (!head || !tog) return;
    head.addEventListener('click', function (e) {
      if (e.target.closest('input, select, textarea, a, button:not(.ds__toggle)')) return;
      sec.classList.toggle('is-collapsed');
      tog.setAttribute('aria-expanded', sec.classList.contains('is-collapsed') ? 'false' : 'true');
    });
  });

  /* ---------- Preview modal toggle (below 1100px) ---------- */
  var modal = document.getElementById('preview-modal');
  var open  = document.getElementById('preview-toggle');
  if (modal && open) {
    open.addEventListener('click', function () { modal.classList.add('is-open'); });
    modal.addEventListener('click', function (e) {
      if (e.target === modal || e.target.closest('[data-close-preview]')) {
        modal.classList.remove('is-open');
      }
    });
    window.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') modal.classList.remove('is-open');
    });
  }

  /* ---------- Live preview ---------- */
  var pv = document.getElementById('doc-preview');
  if (!pv) return;

  var $ = function (sel) { return pv.querySelector(sel); };

  function esc(s) {
    return String(s == null ? '' : s)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
  }
  function num(v) { var n = parseFloat(v); return isNaN(n) ? 0 : n; }
  function fmt(n) {
    return (Math.round(n * 100) / 100).toLocaleString('en-US', {
      minimumFractionDigits: 2, maximumFractionDigits: 2
    });
  }

  // Config injected via data-* on #doc-form
  var cfg = {
    currency: form.dataset.currency || 'USD',
    bizName:  form.dataset.bizName  || '',
    bizEmail: form.dataset.bizEmail || '',
    docType:  form.dataset.docType  || 'Invoice'
  };

  function readForm() {
    var clientSel = form.querySelector('[name="client_id"]');
    var clientName = clientSel && clientSel.selectedIndex > 0
      ? clientSel.options[clientSel.selectedIndex].text
      : '';

    var currencySel = form.querySelector('[name="currency"]');
    var currency = currencySel ? currencySel.value : cfg.currency;

    var issueDate = form.querySelector('[name="issue_date"]');
    var otherDate = form.querySelector('[name="due_date"], [name="valid_until"]');

    var notes = form.querySelector('[name="notes"]');
    var terms = form.querySelector('[name="terms"]');

    var items = [];
    form.querySelectorAll('#line-items tbody tr').forEach(function (row) {
      var desc = row.querySelector('[name$="[description]"]');
      var qty  = row.querySelector('[name$="[quantity]"]');
      var prc  = row.querySelector('[name$="[unit_price]"]');
      var d = desc ? desc.value.trim() : '';
      var q = qty  ? num(qty.value)  : 0;
      var p = prc  ? num(prc.value)  : 0;
      if (d === '' && q === 0 && p === 0) return;
      items.push({ description: d, quantity: q, unit_price: p, line_total: q * p });
    });

    var subtotal = items.reduce(function (s, it) { return s + it.line_total; }, 0);
    var dtype = (form.querySelector('[name="discount_type"]') || {}).value || 'none';
    var dval  = num((form.querySelector('[name="discount_value"]') || {}).value);
    var discountAmount = 0;
    if (dtype === 'percent') discountAmount = subtotal * (dval / 100);
    else if (dtype === 'fixed') discountAmount = dval;
    if (discountAmount < 0) discountAmount = 0;
    if (discountAmount > subtotal) discountAmount = subtotal;

    var trate = num((form.querySelector('[name="tax_rate"]') || {}).value);
    var taxAmount = (subtotal - discountAmount) * (trate / 100);

    var total = subtotal - discountAmount + taxAmount;
    if (total < 0) total = 0;

    return {
      clientName: clientName,
      currency: currency,
      issueDate: issueDate ? issueDate.value : '',
      otherDate: otherDate ? otherDate.value : '',
      notes: notes ? notes.value : '',
      terms: terms ? terms.value : '',
      items: items,
      subtotal: subtotal,
      discountAmount: discountAmount,
      dtype: dtype, dval: dval,
      taxRate: trate,
      taxAmount: taxAmount,
      total: total
    };
  }

  function fmtDate(iso) {
    if (!iso) return '—';
    var d = new Date(iso + 'T00:00:00');
    if (isNaN(d)) return iso;
    return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short', year: 'numeric' });
  }

  function render() {
    var d = readForm();

    // Header
    $('[data-pv="biz"]').textContent = cfg.bizName || 'Your business';
    $('[data-pv="biz-email"]').textContent = cfg.bizEmail || '';
    $('[data-pv="type"]').textContent = cfg.docType;
    $('[data-pv="number"]').textContent = form.dataset.docNumber || 'Draft';

    // Meta
    $('[data-pv="client"]').textContent = d.clientName || 'No client selected';
    $('[data-pv="client"]').classList.toggle('muted', !d.clientName);
    $('[data-pv="issue"]').textContent = fmtDate(d.issueDate);

    var otherLabel = form.dataset.otherDateLabel || 'Due date';
    $('[data-pv="other-label"]').textContent = otherLabel;
    $('[data-pv="other"]').textContent = fmtDate(d.otherDate);

    $('[data-pv="currency"]').textContent = d.currency;

    // Items
    var tbody = $('[data-pv="items"]');
    tbody.innerHTML = '';
    if (!d.items.length) {
      var tr0 = document.createElement('tr');
      tr0.innerHTML = '<td class="muted" colspan="4" style="padding:16px 0;text-align:center;">No items yet</td>';
      tbody.appendChild(tr0);
    } else {
      d.items.forEach(function (it) {
        var tr = document.createElement('tr');
        tr.innerHTML =
          '<td>' + (esc(it.description) || '<span class="muted">Untitled</span>') + '</td>' +
          '<td class="num">' + esc(fmt(it.quantity)) + '</td>' +
          '<td class="num">' + esc(fmt(it.unit_price)) + '</td>' +
          '<td class="num">' + esc(fmt(it.line_total)) + '</td>';
        tbody.appendChild(tr);
      });
    }

    // Totals
    $('[data-pv="subtotal"]').textContent = fmt(d.subtotal);
    var discRow = $('[data-pv="discount-row"]');
    if (d.dtype !== 'none' && d.discountAmount > 0) {
      discRow.hidden = false;
      $('[data-pv="discount-label"]').textContent =
        d.dtype === 'percent' ? 'Discount (' + d.dval + '%)' : 'Discount';
      $('[data-pv="discount"]').textContent = '- ' + fmt(d.discountAmount);
    } else {
      discRow.hidden = true;
    }
    var taxRow = $('[data-pv="tax-row"]');
    if (d.taxAmount > 0) {
      taxRow.hidden = false;
      $('[data-pv="tax-label"]').textContent = 'Tax (' + d.taxRate + '%)';
      $('[data-pv="tax"]').textContent = fmt(d.taxAmount);
    } else {
      taxRow.hidden = true;
    }
    $('[data-pv="total"]').textContent = d.currency + ' ' + fmt(d.total);

    // Notes / Terms
    var notesWrap = $('[data-pv="notes-wrap"]');
    var termsWrap = $('[data-pv="terms-wrap"]');
    if (d.notes.trim()) {
      notesWrap.hidden = false;
      $('[data-pv="notes"]').textContent = d.notes;
    } else {
      notesWrap.hidden = true;
    }
    if (d.terms.trim()) {
      termsWrap.hidden = false;
      $('[data-pv="terms"]').textContent = d.terms;
    } else {
      termsWrap.hidden = true;
    }
  }

  // Debounced recalc
  var t = null;
  function queue() {
    if (t) clearTimeout(t);
    t = setTimeout(render, 120);
  }

  form.addEventListener('input', queue);
  form.addEventListener('change', queue);
  form.addEventListener('click', function (e) {
    if (e.target.closest('#add-item, .remove-row')) queue();
  });

  // Recalc after line-items.js mutates the DOM
  var mo = new MutationObserver(queue);
  var tbody = form.querySelector('#line-items tbody');
  if (tbody) mo.observe(tbody, { childList: true });

  render();
})();