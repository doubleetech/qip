<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/invoices.php';
require_business();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify(post('_csrf'))) {
    flash_set('error', 'Invalid request.');
    redirect('pages/invoices.php');
}

$id = (int) post('id', 0);
$newId = invoice_duplicate($id);

if (!$newId) {
    flash_set('error', 'Could not duplicate invoice.');
    redirect('pages/invoices.php');
}

flash_set('success', 'Invoice duplicated.');
redirect('pages/invoice-view.php?id=' . $newId);