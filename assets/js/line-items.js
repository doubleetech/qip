/* QIP — Line item editor + live totals */
(function () {
  var root = document.getElementById('line-items');
  if (!root) return;

  var tbody      = root.querySelector('tbody');
  var addBtn     = document.getElementById('add-item');
  var tpl        = document.getElementById('line-item-template');

  var discType   = document.getElementById('discount_type');
  var discValue  = document.getElementById('discount_value');
  var taxRate    = document.getElementById('tax_rate');

  var outSub     = document.getElementById('out-subtotal');
  var outDisc    = document.getElementById('out-discount');
  var outTax     = document.getElementById('out-tax');
  var outTotal   = document.getElementById('out-total');
  var outDiscLbl = document.getElementById('out-discount-label');

  var nextIndex  = tbody.querySelectorAll('tr').length;

  function fmt(n) {
    return (Math.round(n * 100) / 100).toLocaleString('en-US', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  }
  function num(v) { var n = parseFloat(v); return isNaN(n) ? 0 : n; }

  function recalc() {
    var rows = tbody.querySelectorAll('tr');
    var subtotal = 0;

    rows.forEach(function (row) {
      var nums = row.querySelectorAll('input[type="number"]');
      var qty   = num(nums[0] ? nums[0].value : 0);
      var price = num(nums[1] ? nums[1].value : 0);
      if (qty   < 0) qty   = 0;
      if (price < 0) price = 0;
      var line = qty * price;
      row.querySelector('.line-total').textContent = fmt(line);
      subtotal += line;
    });

    subtotal = Math.round(subtotal * 100) / 100;

    var dtype = discType.value;
    var dval  = num(discValue.value);
    var discountAmount = 0;
    if (dtype === 'percent') discountAmount = subtotal * (dval / 100);
    else if (dtype === 'fixed') discountAmount = dval;
    if (discountAmount < 0) discountAmount = 0;
    if (discountAmount > subtotal) discountAmount = subtotal;
    discountAmount = Math.round(discountAmount * 100) / 100;

    var after = subtotal - discountAmount;
    var trate = num(taxRate.value);
    if (trate < 0) trate = 0;
    var taxAmount = Math.round(after * (trate / 100) * 100) / 100;

    var total = Math.round((after + taxAmount) * 100) / 100;

    outSub.textContent   = fmt(subtotal);
    outDisc.textContent  = fmt(discountAmount);
    outTax.textContent   = fmt(taxAmount);
    outTotal.textContent = fmt(total);

    outDiscLbl.textContent = dtype === 'percent' ? 'Discount (' + dval + '%)' : 'Discount';
    outDisc.parentElement.hidden = dtype === 'none' || discountAmount === 0;
    outTax.parentElement.hidden  = taxAmount === 0;
  }

  function bindRow(row) {
    row.querySelectorAll('input').forEach(function (inp) {
      inp.addEventListener('input', recalc);
    });
    var rm = row.querySelector('.remove-row');
    if (rm) rm.addEventListener('click', function () {
      if (tbody.querySelectorAll('tr').length > 1) {
        row.remove();
        recalc();
      }
    });
  }

  function addRow() {
    var html = tpl.innerHTML.replace(/__i__/g, nextIndex++);
    var wrap = document.createElement('tbody');
    wrap.innerHTML = html.trim();
    var node = wrap.firstElementChild;
    tbody.appendChild(node);
    bindRow(node);
    recalc();
  }

  tbody.querySelectorAll('tr').forEach(bindRow);
  if (!tbody.querySelectorAll('tr').length) addRow();

  addBtn.addEventListener('click', function () { addRow(); });

  [discType, discValue, taxRate].forEach(function (el) {
    el.addEventListener('input', recalc);
    el.addEventListener('change', recalc);
  });

  recalc();
})();