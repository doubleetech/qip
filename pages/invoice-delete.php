<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/invoices.php';
require_business();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify(post('_csrf'))) {
    flash_set('error', 'Invalid request.');
    redirect('pages/invoices.php');
}

$id = (int) post('id', 0);
$inv = invoice_get($id);
if (!$inv) {
    flash_set('error', 'Invoice not found.');
    redirect('pages/invoices.php');
}

if ($inv['status'] !== 'draft') {
    flash_set('error', 'Only draft invoices can be deleted.');
    redirect('pages/invoice-view.php?id=' . $id);
}

invoice_delete($id);
flash_set('success', 'Invoice deleted.');
redirect('pages/invoices.php');