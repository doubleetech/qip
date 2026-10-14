</main>
  </div>
</div>

<div class="scrim" id="scrim"></div>
<div class="toast-host" id="toast-host" aria-live="polite" aria-atomic="true"></div>

<script>
(function () {
  /* ---------- Sidebar drawer ---------- */
  var sidebar = document.getElementById('sidebar');
  var toggle  = document.getElementById('sidebar-toggle');
  var scrim   = document.getElementById('scrim');

  function openSidebar() {
    sidebar.classList.add('is-open');
    scrim.classList.add('is-open');
    if (toggle) toggle.setAttribute('aria-expanded', 'true');
  }
  function closeSidebar() {
    sidebar.classList.remove('is-open');
    scrim.classList.remove('is-open');
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
  }
  if (toggle) toggle.addEventListener('click', function () {
    sidebar.classList.contains('is-open') ? closeSidebar() : openSidebar();
  });
  if (scrim) scrim.addEventListener('click', closeSidebar);

  /* ---------- User menu ---------- */
  var userToggle = document.getElementById('user-menu-toggle');
  var userMenu   = document.getElementById('user-menu');

  function closeUserMenu() {
    if (!userMenu) return;
    userMenu.classList.remove('is-open');
    userMenu.hidden = true;
    userToggle.setAttribute('aria-expanded', 'false');
  }
  if (userToggle && userMenu) {
    userToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      var isOpen = userMenu.classList.contains('is-open');
      if (isOpen) {
        closeUserMenu();
      } else {
        userMenu.hidden = false;
        userMenu.classList.add('is-open');
        userToggle.setAttribute('aria-expanded', 'true');
      }
    });
    document.addEventListener('click', function (e) {
      if (!userMenu.contains(e.target) && !userToggle.contains(e.target)) closeUserMenu();
    });
  }

  /* ---------- Global escape closes everything ---------- */
  window.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeSidebar();
      closeUserMenu();
    }
  });
})();

/* Topbar denser on scroll */
var topbar = document.querySelector('.topbar');
if (topbar) {
  function onScroll() { topbar.classList.toggle('is-scrolled', window.scrollY > 8); }
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });
}
</script>

<?php include __DIR__ . '/footer.php'; ?>