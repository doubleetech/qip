<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/clients.php';
require_business();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify(post('_csrf'))) {
    flash_set('error', 'Invalid request.');
    redirect('pages/clients.php');
}

$id = (int) post('id', 0);
$client = client_get($id);

if (!$client) {
    flash_set('error', 'Client not found.');
    redirect('pages/clients.php');
}

$deps = client_dependency_counts($id);

if ($deps['quotes'] > 0 || $deps['invoices'] > 0) {
    $parts = [];
    if ($deps['quotes']   > 0) $parts[] = $deps['quotes']   . ' quote'   . ($deps['quotes']   === 1 ? '' : 's');
    if ($deps['invoices'] > 0) $parts[] = $deps['invoices'] . ' invoice' . ($deps['invoices'] === 1 ? '' : 's');
    flash_set(
        'error',
        'Cannot delete ' . $client['name'] . ' — they have ' . implode(' and ', $parts)
        . '. Delete or reassign those first.'
    );
    redirect('pages/client-view.php?id=' . $id);
}

client_delete($id);
flash_set('success', 'Client deleted.');
redirect('pages/clients.php');