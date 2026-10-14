/* QIP — Searchable currency dropdown */
(function () {
  var root = document.getElementById('currency-combo');
  if (!root) return;

  var trigger  = root.querySelector('.combo__trigger');
  var menu     = root.querySelector('.combo__menu');
  var search   = root.querySelector('.combo__search');
  var hidden   = document.getElementById('default_currency');
  var options  = Array.prototype.slice.call(root.querySelectorAll('.combo__option'));
  var emptyMsg = root.querySelector('.combo__empty');
  var active   = -1;

  // Read searchable text from element text, not data attrs
  options.forEach(function (opt) {
    var code   = (opt.dataset.code   || '').toLowerCase();
    var name   = (opt.querySelector('.combo__name')?.textContent || '').toLowerCase();
    var symbol = (opt.dataset.symbol || '').toLowerCase();
    opt._search = (code + ' ' + name + ' ' + symbol).trim();
  });

  function open() {
    menu.hidden = false;
    trigger.setAttribute('aria-expanded', 'true');
    search.value = '';
    filter('');
    setTimeout(function () { search.focus(); }, 10);
  }
  function close() {
    menu.hidden = true;
    trigger.setAttribute('aria-expanded', 'false');
    active = -1;
  }
  function visibleOptions() {
    return options.filter(function (o) { return !o.hidden; });
  }
  function filter(q) {
    q = (q || '').trim().toLowerCase();
    var shown = 0;
    options.forEach(function (opt) {
      var hit = !q || opt._search.indexOf(q) !== -1;
      opt.hidden = !hit;
      if (hit) shown++;
    });
    emptyMsg.hidden = shown > 0;
    active = -1;
    visibleOptions().forEach(function (o) { o.classList.remove('is-active'); });
  }
  function pick(opt) {
    hidden.value = opt.dataset.code;
    trigger.querySelector('.combo__code').textContent = opt.dataset.code;
    trigger.querySelector('.combo__label').textContent = opt.dataset.name;
    trigger.querySelector('.combo__symbol').textContent = opt.dataset.symbol;
    options.forEach(function (o) { o.classList.toggle('is-selected', o === opt); });
    close();
  }
  function highlight(dir) {
    var vis = visibleOptions();
    if (!vis.length) return;
    vis.forEach(function (o) { o.classList.remove('is-active'); });
    active = (active + dir + vis.length) % vis.length;
    vis[active].classList.add('is-active');
    vis[active].scrollIntoView({ block: 'nearest' });
  }

  trigger.addEventListener('click', function (e) {
    e.stopPropagation();
    menu.hidden ? open() : close();
  });
  trigger.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); open(); }
  });

  search.addEventListener('input', function () { filter(search.value); });
  search.addEventListener('keydown', function (e) {
    if (e.key === 'ArrowDown') { e.preventDefault(); highlight(1); }
    else if (e.key === 'ArrowUp') { e.preventDefault(); highlight(-1); }
    else if (e.key === 'Enter') {
      e.preventDefault();
      var vis = visibleOptions();
      if (vis[active]) pick(vis[active]);
    } else if (e.key === 'Escape') { close(); trigger.focus(); }
  });

  options.forEach(function (opt) {
    opt.addEventListener('click', function () { pick(opt); trigger.focus(); });
  });

  document.addEventListener('click', function (e) {
    if (!root.contains(e.target)) close();
  });
})();