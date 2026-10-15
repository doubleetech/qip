<?php
// pages/quote-status.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/quotes.php';
require_business();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify(post('_csrf'))) {
    flash_set('error', 'Invalid request.');
    redirect('pages/quotes.php');
}

$id = (int) post('id', 0);
$status = (string) post('status', '');

$quote = quote_get($id);
if (!$quote) {
    flash_set('error', 'Quote not found.');
    redirect('pages/quotes.php');
}

if (!quote_set_status($id, $status)) {
    flash_set('error', 'Could not update status.');
} else {
    flash_set('success', 'Quote marked as ' . $status . '.');
}
redirect('pages/quote-view.php?id=' . $id);