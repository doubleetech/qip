<?php
// pages/quote-duplicate.php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/quotes.php';
require_business();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify(post('_csrf'))) {
    flash_set('error', 'Invalid request.');
    redirect('pages/quotes.php');
}

$id = (int) post('id', 0);
$newId = quote_duplicate($id);

if (!$newId) {
    flash_set('error', 'Could not duplicate quote.');
    redirect('pages/quotes.php');
}

flash_set('success', 'Quote duplicated.');
redirect('pages/quote-view.php?id=' . $newId);