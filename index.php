<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/google_oauth.php';

redirect_if_ready('pages/dashboard.php');

if (is_logged_in() && !has_business_profile()) {
    redirect('setup/business.php');
}

$error   = flash_get('error');
$authUrl = google_auth_url();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="QIP — Quote. Invoice. Payment. All in one place.">
<title>QIP — Quote. Invoice. Payment.</title>
<script>document.documentElement.classList.add('js');</script>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

<link rel="stylesheet" href="<?= e(url('assets/css/variables.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/base.css')) ?>">
<link rel="stylesheet" href="<?= e(url('assets/css/landing.css')) ?>">
</head>
<body>

<div class="landing">

  <!-- Nav -->
  <header class="nav" id="nav">
    <div class="container">
      <div class="nav__inner">
        <a class="nav__brand" href="<?= e(url('index.php')) ?>">
          <span class="nav__logo" aria-hidden="true">Q</span>
          <span>QIP</span>
        </a>

        <div class="nav__actions">
          <a href="<?= e($authUrl) ?>" class="btn btn--primary">Get started</a>
        </div>
      </div>
    </div>
  </header>

  <main id="top">

    <!-- Hero -->
    <section class="hero">
      <div class="container hero__grid">

        <div class="hero__copy">
          <p class="eyebrow">Financial clarity for your business</p>

          <h1>
            Quote. Invoice. Payment.<br>
            <span class="accent">All in one place.</span>
          </h1>

          <p class="hero__lead">
            Create professional quotes and invoices in minutes and get paid
            faster. Manage clients, invoices, and payments from one clean workspace.
          </p>

          <?php if ($error): ?>
            <div class="flash flash--error" role="alert">
              <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
              <span><?= e($error) ?></span>
            </div>
          <?php endif; ?>

          <div class="hero__cta">
            <a href="<?= e($authUrl) ?>" class="btn btn--primary btn--lg">
              <i class="fa-brands fa-google" aria-hidden="true"></i>
              Continue with Google
            </a>
            <a href="#features" class="btn btn--glass btn--lg">
              See features
              <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </div>
        </div>

        <div class="hero__visual" aria-hidden="true">
          <div class="hero__glow"></div>
          <div class="hero__orbit"></div>

          <!-- Total revenue (counts up) -->
          <div class="mock-float mock-float--revenue">
            <div class="mock-float__icon"><i class="fa-solid fa-sack-dollar"></i></div>
            <div>
              <div class="mock-float__label">Total Revenue</div>
              <div class="mock-float__value"><span data-count="246580" data-prefix="$" data-delay="400" data-dur="1700">$246,580</span></div>
              <div class="mock-float__delta"><i class="fa-solid fa-arrow-trend-up"></i> +12%</div>
              <svg class="mock-float__spark" viewBox="0 0 130 28" fill="none">
                <path class="spark__line" pathLength="1"
                      d="M2 24 C 18 20, 24 26, 40 18 S 62 16, 76 12 S 100 12, 112 6 L 128 4"
                      stroke="#4ade80" stroke-width="2" stroke-linecap="round"/>
                <circle class="spark__dot" cx="128" cy="4" r="3" fill="#4ade80"/>
              </svg>
            </div>
          </div>

          <!-- Phone -->
          <div class="phone">
            <div class="phone__frame">
              <div class="phone__island"></div>
              <div class="phone__screen">
                <div class="phone__status">
                  <span>9:41</span>
                  <span class="phone__icons">
                    <i class="fa-solid fa-signal"></i>
                    <i class="fa-solid fa-wifi"></i>
                    <i class="fa-solid fa-battery-full"></i>
                  </span>
                </div>

                <div class="phone__bar">
                  <i class="fa-solid fa-arrow-left"></i>
                  <span class="phone__brand"><span class="nav__logo">Q</span>QIP</span>
                  <i class="fa-solid fa-ellipsis"></i>
                </div>

                <div class="invoice">
                  <div class="invoice__top">
                    <div>
                      <div class="invoice__title">INVOICE</div>
                      <div class="invoice__num">#INV-00456</div>
                    </div>
                    <div class="invoice__amount">$928.80</div>
                  </div>

                  <div class="invoice__meta">
                    <div><small>Bill to</small><strong>Double E</strong></div>
                    <div><small>Project</small><strong>Design Project</strong></div>
                    <div><small>Issue date</small><strong>1 Oct 2026</strong></div>
                    <div><small>Due date</small><strong>22 Oct 2026</strong></div>
                  </div>

                  <table class="invoice__table">
                    <thead><tr><th>Item</th><th>Qty</th><th>Amount</th></tr></thead>
                    <tbody>
                      <tr><td>Website Design</td><td>1.00</td><td>300</td></tr>
                      <tr><td>Web Package</td><td>1.00</td><td>500</td></tr>
                      <tr><td>Print Ad</td><td>1.00</td><td>80</td></tr>
                    </tbody>
                  </table>

                  <div class="invoice__totals">
                    <div><span>Sub total</span><b>$880.00</b></div>
                    <div><span>Discount</span><b>-$20.00</b></div>
                    <div><span>Sales tax (8%)</span><b>$68.80</b></div>
                    <div><span>Total</span><b>$928.80</b></div>
                  </div>

                  <div class="invoice__pay">
                    <svg class="invoice__qr" viewBox="0 0 21 21" shape-rendering="crispEdges">
                      <path fill="#0f172a" d="M0 0h7v7H0zM14 0h7v7h-7zM0 14h7v7H0z"/>
                      <path fill="#fff" d="M1 1h5v5H1zM15 1h5v5h-5zM1 15h5v5H1z"/>
                      <path fill="#0f172a" d="M2 2h3v3H2zM16 2h3v3h-3zM2 16h3v3H2zM9 1h2v2H9zM9 5h2v2H9zM12 3h1v3h-1zM9 9h3v2H9zM14 9h2v3h-2zM18 9h3v2h-3zM9 13h2v3H9zM13 14h3v2h-3zM17 13h2v3h-2zM10 18h3v3h-3zM15 18h2v3h-2zM19 17h2v4h-2zM0 9h2v2H0zM4 9h3v2H4z"/>
                    </svg>
                    <div>Scan or tap to pay<b>qip.app/pay/INV-00456</b></div>
                  </div>

                  <div class="invoice__status">
                    <div class="pill pill--pending"><i class="fa-solid fa-clock"></i> Pending</div>
                    <div class="pill pill--paid"><i class="fa-solid fa-check"></i> Paid</div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment received (arrives when the invoice flips to Paid) -->
          <div class="mock-float mock-float--paid">
            <div class="mock-float__icon mock-float__icon--check"><i class="fa-solid fa-check"></i></div>
            <div>
              <div class="mock-float__label">Payment Received</div>
              <div class="mock-float__value">$928.80</div>
              <div class="mock-float__delta mock-float__delta--muted">Just now</div>
            </div>
          </div>

          <!-- New client -->
          <div class="mock-float mock-float--client">
            <div class="mock-float__icon mock-float__icon--blue"><i class="fa-solid fa-user-plus"></i></div>
            <div>
              <div class="mock-float__label">New Client</div>
              <div class="mock-float__value">Olumide Fash</div>
            </div>
          </div>

          <!-- Invoice status (Paid ticks up when the payment lands) -->
          <div class="mock-float mock-float--status">
            <div class="status-row"><span class="status-dot status-dot--paid"></span>Paid<b data-count="12" data-from="11" data-delay="1500" data-dur="350">12</b></div>
            <div class="status-row"><span class="status-dot status-dot--pending"></span>Pending<b>5</b></div>
            <div class="status-row"><span class="status-dot status-dot--overdue"></span>Overdue<b>2</b></div>
          </div>
        </div>

      </div>
    </section>

    <!-- Flow strip -->
    <section class="flow" aria-label="How QIP works">
      <div class="container">
        <ol class="flow__grid">
          <li class="flow__step" data-reveal style="--i:0">
            <span class="flow__num">1</span>
            <div>
              <h3>Create a quote</h3>
              <p>Price the job and send it to your customer in minutes.</p>
            </div>
          </li>
          <li class="flow__step" data-reveal style="--i:1">
            <span class="flow__num">2</span>
            <div>
              <h3>Send the invoice</h3>
              <p>Turn the work into a clean, professional invoice.</p>
            </div>
          </li>
          <li class="flow__step" data-reveal style="--i:2">
            <span class="flow__num">3</span>
            <div>
              <h3>Get paid</h3>
              <p>Track every payment from one workspace as it comes in.</p>
            </div>
          </li>
        </ol>
      </div>
    </section>

    <!-- Features (bento) -->
    <section class="features" id="features">
      <div class="container">

        <div class="features__head" data-reveal>
          <p class="eyebrow eyebrow--dark">More than just invoices</p>
          <h2>
            Powerful <span class="accent">invoicing tools</span><br>
            for growing businesses
          </h2>
        </div>

        <div class="bento" id="bento">

          <article class="tile tile--list" data-reveal="scale" style="--i:0">
            <h3 class="tile__title">Every invoice, one view</h3>
            <p class="tile__text">See what's paid, pending and overdue at a glance, so nothing slips.</p>
            <div class="ilist" aria-hidden="true">
              <div class="ilist__chips">
                <span class="chip chip--on">All</span><span class="chip">Paid</span><span class="chip">Pending</span><span class="chip">Overdue</span>
              </div>
              <div class="ilist__row">
                <span class="avatar avatar--a">OF</span>
                <span class="ilist__name">Olumide Fash<small>INV-00456</small></span>
                <span class="ilist__amt">$928.80</span><span class="tag tag--paid">Paid</span>
              </div>
              <div class="ilist__row">
                <span class="avatar avatar--b">BS</span>
                <span class="ilist__name">Brightline Studio<small>INV-00455</small></span>
                <span class="ilist__amt">$2,400.00</span><span class="tag tag--pending">Pending</span>
              </div>
              <div class="ilist__row">
                <span class="avatar avatar--c">KA</span>
                <span class="ilist__name">Kemi Adeyemi<small>INV-00454</small></span>
                <span class="ilist__amt">$460.00</span><span class="tag tag--overdue">Overdue</span>
              </div>
              <div class="ilist__row">
                <span class="avatar avatar--d">NF</span>
                <span class="ilist__name">Northwind Foods<small>INV-00453</small></span>
                <span class="ilist__amt">$1,250.00</span><span class="tag tag--paid">Paid</span>
              </div>
              <div class="ilist__row">
                <span class="avatar avatar--e">TL</span>
                <span class="ilist__name">Tolu Logistics<small>INV-00452</small></span>
                <span class="ilist__amt">$3,180.00</span><span class="tag tag--paid">Paid</span>
              </div>
            </div>
          </article>

          <article class="tile tile--revenue" data-reveal="scale" style="--i:1">
            <h3 class="tile__title">Know where you stand</h3>
            <p class="tile__text">Revenue is tracked as payments come in.</p>
            <div class="rev" aria-hidden="true">
              <div>
                <div class="rev__total num">$12,480</div>
                <div class="rev__delta"><i class="fa-solid fa-arrow-trend-up"></i> +12% this week</div>
              </div>
              <div class="bars">
                <span class="bar bar--dim" style="--h:38;--i:0"></span>
                <span class="bar bar--dim" style="--h:52;--i:1"></span>
                <span class="bar bar--dim" style="--h:44;--i:2"></span>
                <span class="bar"          style="--h:68;--i:3"></span>
                <span class="bar"          style="--h:58;--i:4"></span>
                <span class="bar"          style="--h:84;--i:5"></span>
                <span class="bar"          style="--h:100;--i:6"></span>
              </div>
            </div>
          </article>

          <article class="tile tile--link" data-reveal="scale" style="--i:2">
            <h3 class="tile__title">Pay in one tap</h3>
            <p class="tile__text">Share a payment link and let customers pay online.</p>
            <div class="paylink">
              <div class="paylink__field">
                <span id="paylink-text">qip.app/pay/INV-00456</span>
                <button class="copy" type="button" id="copy-btn"><i class="fa-regular fa-copy" aria-hidden="true"></i><span aria-live="polite">Copy</span></button>
              </div>
              <div class="paylink__methods" aria-hidden="true">
                <i class="fa-regular fa-credit-card"></i>
                <i class="fa-solid fa-building-columns"></i>
                <i class="fa-solid fa-qrcode"></i>
              </div>
            </div>
          </article>

          <article class="tile tile--remind" data-reveal="scale" style="--i:3">
            <h3 class="tile__title">Automatic reminders</h3>
            <p class="tile__text">Gentle follow-ups, sent for you.</p>
            <ul class="timeline" aria-hidden="true">
              <li class="is-done"><span>Invoice sent<small>Mon, 9:02 AM</small></span></li>
              <li class="is-done"><span>Viewed by client<small>Mon, 11:47 AM</small></span></li>
              <li class="is-done"><span>Reminder sent<small>Thu, 9:00 AM</small></span></li>
              <li class="is-done"><span>Paid<small>Thu, 9:14 AM</small></span></li>
            </ul>
          </article>

        </div>
      </div>
    </section>

  </main>

  <!-- Footer -->
  <footer class="footer">
    <div class="container" data-reveal="fade">
      <div class="footer__top">
        <div>
          <a class="nav__brand" href="<?= e(url('index.php')) ?>">
            <span class="nav__logo" aria-hidden="true">Q</span>
            <span>QIP</span>
          </a>
          <p class="footer__blurb">Quote. Invoice. Payment. All in one place.</p>
        </div>

        <ul class="footer__links">
          <li><a href="#features">Features</a></li>
          <li><a href="#resources">Resources</a></li>
          <li><a href="#pricing">Pricing</a></li>
          <li><a href="<?= e($authUrl) ?>">Sign in</a></li>
        </ul>
      </div>

      <div class="footer__bottom">
        <span>&copy; <?= date('Y') ?> QIP. All rights reserved.</span>
        <span>Built for growing businesses.</span>
      </div>
    </div>
  </footer>

</div>

<script>
(function () {
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  /* Nav: capsule gets a touch denser once scrolled */
  var nav = document.getElementById('nav');
  function onScroll() { nav.classList.toggle('nav--scrolled', window.scrollY > 8); }
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });

  /* Hero: count-up numbers */
  document.querySelectorAll('[data-count]').forEach(function (el) {
    var to = +el.dataset.count;
    var from = +(el.dataset.from || 0);
    var delay = +(el.dataset.delay || 0);
    var dur = +(el.dataset.dur || 1600);
    var prefix = el.dataset.prefix || '';
    function fmt(v) { return prefix + Math.round(v).toLocaleString('en-US'); }
    if (reduce) { el.textContent = fmt(to); return; }
    el.textContent = fmt(from);
    setTimeout(function () {
      var t0 = performance.now();
      (function tick(now) {
        var p = Math.min((now - t0) / dur, 1);
        var eased = 1 - Math.pow(1 - p, 3);
        el.textContent = fmt(from + (to - from) * eased);
        if (p < 1) requestAnimationFrame(tick);
        else if (from) el.classList.add('pop');
      })(t0);
    }, delay);
  });

  /* Hero: mouse parallax (fine pointers only) */
  var hero = document.querySelector('.hero');
  var vis = document.querySelector('.hero__visual');
  if (hero && vis && !reduce && window.matchMedia('(pointer: fine)').matches) {
    function clamp(n) { return Math.max(-1, Math.min(1, n)); }
    hero.addEventListener('pointermove', function (e) {
      var r = vis.getBoundingClientRect();
      vis.style.setProperty('--px', clamp(((e.clientX - r.left) / r.width - 0.5) * 2).toFixed(3));
      vis.style.setProperty('--py', clamp(((e.clientY - r.top) / r.height - 0.5) * 2).toFixed(3));
    });
    hero.addEventListener('pointerleave', function () {
      vis.style.setProperty('--px', 0);
      vis.style.setProperty('--py', 0);
    });
  }

  /* Scroll reveal: anything with [data-reveal] fades in once as it enters view.
     The revenue tile also starts its bar-growth when it appears. */
  var bento = document.getElementById('bento');
  function reveal(el) {
    el.classList.add('is-visible');
    if (bento && el.classList.contains('tile--revenue')) bento.classList.add('in-view');
  }
  var revealEls = document.querySelectorAll('[data-reveal]');
  if (reduce || !('IntersectionObserver' in window)) {
    revealEls.forEach(reveal);
    if (bento) bento.classList.add('in-view');
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { reveal(en.target); io.unobserve(en.target); }
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -8% 0px' });
    revealEls.forEach(function (el) { io.observe(el); });
  }

  /* Payment link: copy */
  var copyBtn = document.getElementById('copy-btn');
  if (copyBtn) {
    var label = copyBtn.querySelector('span');
    copyBtn.addEventListener('click', function () {
      var text = document.getElementById('paylink-text').textContent;
      var done = function () {
        copyBtn.classList.add('is-copied');
        label.textContent = 'Copied';
        setTimeout(function () {
          copyBtn.classList.remove('is-copied');
          label.textContent = 'Copy';
        }, 1600);
      };
      if (navigator.clipboard) { navigator.clipboard.writeText(text).then(done, done); }
      else { done(); }
    });
  }
})();
</script>

</body>
</html>