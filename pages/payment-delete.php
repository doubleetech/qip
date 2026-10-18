<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/payments.php';
require_business();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify(post('_csrf'))) {
    flash_set('error', 'Invalid request.');
    redirect('pages/payments.php');
}

$id = (int) post('id', 0);
$p  = payment_get($id);
if (!$p) {
    flash_set('error', 'Payment not found.');
    redirect('pages/payments.php');
}

payment_delete($id);
flash_set('success', 'Payment deleted.');
redirect('pages/invoice-view.php?id=' . (int) $p['invoice_id']);