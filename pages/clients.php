<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/clients.php';
require_business();

$q = trim((string) get('q', ''));
$clients = clients_list($q !== '' ? $q : null);

$page_title = 'Clients';
include __DIR__ . '/../includes/app-header.php';
?>

<div class="page__head">
  <div>
    <h1 class="page__title">Clients</h1>
    <p class="page__subtitle">The people and businesses you bill.</p>
  </div>
  <div class="page__actions">
    <a class="btn btn--primary" href="<?= e(url('pages/client-form.php')) ?>">
      <i class="fa-solid fa-plus"></i> Add client
    </a>
  </div>
</div>

<?php if (flash_has('success')): ?>
  <div class="flash flash--success">
    <i class="fa-solid fa-circle-check"></i>
    <span><?= e(flash_get('success')) ?></span>
  </div>
<?php endif; ?>
<?php if (flash_has('error')): ?>
  <div class="flash flash--error">
    <i class="fa-solid fa-circle-exclamation"></i>
    <span><?= e(flash_get('error')) ?></span>
  </div>
<?php endif; ?>

<?php if (!$clients && $q === ''): ?>

  <div class="empty">
    <div class="empty__icon"><i class="fa-solid fa-users"></i></div>
    <h2 class="empty__title">No clients yet</h2>
    <p class="empty__text">Add your first client to start sending invoices.</p>
    <a class="btn btn--primary" href="<?= e(url('pages/client-form.php')) ?>">
      <i class="fa-solid fa-plus"></i> Add client
    </a>
  </div>

<?php else: ?>

  <form method="get" class="row" style="margin-bottom:16px;gap:8px;">
    <div style="flex:1;position:relative;">
      <input class="input" type="search" name="q"
             value="<?= e($q) ?>"
             placeholder="Search clients by name, company or email…"
             aria-label="Search clients"
             style="padding-left:38px;">
      <i class="fa-solid fa-magnifying-glass" aria-hidden="true"
         style="position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--text-light-muted);font-size:.85rem;"></i>
    </div>
    <button class="btn btn--ghost" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search</button>
    <?php if ($q !== ''): ?>
      <a class="btn btn--ghost" href="<?= e(url('pages/clients.php')) ?>">
        <i class="fa-solid fa-xmark"></i> Clear
      </a>
    <?php endif; ?>
  </form>

  <?php if (!$clients): ?>

    <div class="empty">
      <div class="empty__icon"><i class="fa-solid fa-magnifying-glass"></i></div>
      <h2 class="empty__title">No matches</h2>
      <p class="empty__text">Nothing matched &ldquo;<?= e($q) ?>&rdquo;. Try a different search.</p>
      <a class="btn btn--ghost" href="<?= e(url('pages/clients.php')) ?>">Clear search</a>
    </div>

  <?php else: ?>

    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr>
            <th>Name</th>
            <th>Company</th>
            <th>Email</th>
            <th>Phone</th>
            <th class="actions">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($clients as $c): ?>
            <tr>
              <td>
                <a href="<?= e(url('pages/client-view.php?id=' . $c['id'])) ?>">
                  <?= e($c['name']) ?>
                </a>
              </td>
              <td><?= $c['company'] ? e($c['company']) : '<span class="muted">—</span>' ?></td>
              <td>
                <?php if ($c['email']): ?>
                  <a href="mailto:<?= e($c['email']) ?>"><?= e($c['email']) ?></a>
                <?php else: ?>
                  <span class="muted">—</span>
                <?php endif; ?>
              </td>
              <td><?= $c['phone'] ? e($c['phone']) : '<span class="muted">—</span>' ?></td>
              <td class="actions">
                <a class="btn btn--ghost btn--sm"
                   href="<?= e(url('pages/client-view.php?id=' . $c['id'])) ?>"
                   aria-label="View <?= e($c['name']) ?>">
                  <i class="fa-solid fa-eye"></i>
                </a>
                <a class="btn btn--ghost btn--sm"
                   href="<?= e(url('pages/client-form.php?id=' . $c['id'])) ?>"
                   aria-label="Edit <?= e($c['name']) ?>">
                  <i class="fa-solid fa-pen"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <p class="muted" style="margin-top:12px;font-size:.85rem;">
      <?= count($clients) ?> client<?= count($clients) === 1 ? '' : 's' ?>
      <?= $q !== '' ? ' matching "' . e($q) . '"' : '' ?>.
    </p>

  <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../includes/app-footer.php'; ?>