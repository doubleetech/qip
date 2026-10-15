<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/conversion.php';
require_business();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify(post('_csrf'))) {
    flash_set('error', 'Invalid request.');
    redirect('pages/quotes.php');
}

$quoteId = (int) post('id', 0);
$quote = quote_get($quoteId);

if (!$quote) {
    flash_set('error', 'Quote not found.');
    redirect('pages/quotes.php');
}

if ($quote['status'] !== 'accepted') {
    flash_set('error', 'Only accepted quotes can be converted.');
    redirect('pages/quote-view.php?id=' . $quoteId);
}

$invoiceId = quote_convert_to_invoice($quoteId);

if (!$invoiceId) {
    flash_set('error', 'Could not convert this quote.');
    redirect('pages/quote-view.php?id=' . $quoteId);
}

// Mark the quote as "invoiced" conceptually — no status change, but flash
flash_set('success', 'Quote converted to invoice.');
redirect('pages/invoice-view.php?id=' . $invoiceId);