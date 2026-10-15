<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/invoices.php';
require_business();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify(post('_csrf'))) {
    flash_set('error', 'Invalid request.');
    redirect('pages/invoices.php');
}

$id = (int) post('id', 0);
$status = (string) post('status', '');

$inv = invoice_get($id);
if (!$inv) {
    flash_set('error', 'Invoice not found.');
    redirect('pages/invoices.php');
}

if (!invoice_set_status($id, $status)) {
    flash_set('error', 'Could not update status.');
} else {
    flash_set('success', 'Invoice marked as ' . $status . '.');
}
redirect('pages/invoice-view.php?id=' . $id);