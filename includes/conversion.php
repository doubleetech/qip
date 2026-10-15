<?php
/**
 * QIP — Quote to invoice conversion.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/quotes.php';
require_once __DIR__ . '/invoices.php';

/**
 * Convert an accepted quote into an invoice.
 * Returns the new invoice id, or null on failure.
 * Idempotent: if an invoice already exists for this quote, returns its id.
 */
function quote_convert_to_invoice(int $quoteId): ?int
{
    $biz = current_business();
    if (!$biz) return null;

    $pdo = db();

    // Fast path: already converted?
    $stmt = $pdo->prepare(
        'SELECT id FROM invoices WHERE source_quote_id = ? AND business_id = ? LIMIT 1'
    );
    $stmt->execute([$quoteId, $biz['id']]);
    if ($existing = $stmt->fetchColumn()) {
        return (int) $existing;
    }

    $quote = quote_get($quoteId);
    if (!$quote) return null;
    if ($quote['status'] !== 'accepted') return null;

    $items = quote_items($quoteId);

    // Snapshot items into invoice shape
    $rawItems = [];
    foreach ($items as $it) {
        $rawItems[] = [
            'description' => $it['description'],
            'quantity'    => $it['quantity'],
            'unit_price'  => $it['unit_price'],
        ];
    }

    $meta = [
        'client_id'       => $quote['client_id'],
        'source_quote_id' => $quoteId,
        'status'          => 'draft',
        'issue_date'      => date('Y-m-d'),
        'due_date'        => date('Y-m-d', strtotime('+14 days')),
        'currency'        => $quote['currency'],
        'discount_type'   => $quote['discount_type'],
        'discount_value'  => $quote['discount_value'],
        'tax_rate'        => $quote['tax_rate'],
        'notes'           => $quote['notes'],
        'terms'           => $quote['terms'],
    ];

    try {
        return invoice_create($meta, $rawItems);
    } catch (PDOException $e) {
        // Unique source_quote_id collision = someone beat us to it
        $stmt = $pdo->prepare(
            'SELECT id FROM invoices WHERE source_quote_id = ? AND business_id = ? LIMIT 1'
        );
        $stmt->execute([$quoteId, $biz['id']]);
        if ($existing = $stmt->fetchColumn()) {
            return (int) $existing;
        }
        throw $e;
    }
}