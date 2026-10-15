<?php
// pages/quote-delete.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/quotes.php';
require_business();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify(post('_csrf'))) {
    flash_set('error', 'Invalid request.');
    redirect('pages/quotes.php');
}

$id = (int) post('id', 0);
$quote = quote_get($id);
if (!$quote) {
    flash_set('error', 'Quote not found.');
    redirect('pages/quotes.php');
}

if ($quote['status'] !== 'draft') {
    flash_set('error', 'Only draft quotes can be deleted.');
    redirect('pages/quote-view.php?id=' . $id);
}

quote_delete($id);
flash_set('success', 'Quote deleted.');
redirect('pages/quotes.php');