<?php
/**
 * QIP — Shared document helpers for quotes and invoices.
 */

require_once __DIR__ . '/auth.php';

/**
 * Next document number for the current business.
 * Format: QT-0001 or INV-0001. Transactional.
 * Caller MUST already be inside a transaction OR be ready to retry on duplicate.
 */
function doc_next_number(string $type): string
{
    $biz = current_business();
    $prefix = $type === 'quote' ? 'QT-' : 'INV-';
    $table  = $type === 'quote' ? 'quotes' : 'invoices';

    // Lock the max row so concurrent inserts serialize
    $stmt = db()->prepare(
        "SELECT number FROM {$table}
         WHERE business_id = ? AND number LIKE ?
         ORDER BY number DESC LIMIT 1 FOR UPDATE"
    );
    $stmt->execute([$biz['id'], $prefix . '%']);
    $last = $stmt->fetchColumn();

    $n = 1;
    if ($last) {
        $n = (int) substr($last, strlen($prefix)) + 1;
    }
    return $prefix . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
}

/**
 * Pure calculation. Never trusts browser totals.
 * $items = [ ['description'=>, 'quantity'=>, 'unit_price'=>], ... ]
 * Returns ['items'=>prepared, 'subtotal'=>, 'discount_amount'=>, 'tax_amount'=>, 'total'=>]
 */
function doc_calculate(array $items, string $discountType, float $discountValue, float $taxRate): array
{
    $prepared = [];
    $subtotal = 0.0;

    foreach ($items as $i => $row) {
        $desc = trim((string) ($row['description'] ?? ''));
        $qty  = (float)  ($row['quantity']    ?? 0);
        $price = (float) ($row['unit_price']  ?? 0);

        if ($desc === '' && $qty == 0 && $price == 0) {
            continue; // skip fully empty rows
        }

        if ($qty   < 0) $qty = 0;
        if ($price < 0) $price = 0;

        $line = round($qty * $price, 2);
        $subtotal += $line;

        $prepared[] = [
            'position'    => $i,
            'description' => $desc,
            'quantity'    => round($qty, 2),
            'unit_price'  => round($price, 2),
            'line_total'  => $line,
        ];
    }

    $subtotal = round($subtotal, 2);

    // Discount
    $discountAmount = 0.0;
    if ($discountType === 'percent') {
        $discountAmount = round($subtotal * ($discountValue / 100), 2);
    } elseif ($discountType === 'fixed') {
        $discountAmount = round($discountValue, 2);
    }
    if ($discountAmount < 0)             $discountAmount = 0.0;
    if ($discountAmount > $subtotal)     $discountAmount = $subtotal;

    $afterDiscount = $subtotal - $discountAmount;

    // Tax
    if ($taxRate < 0) $taxRate = 0.0;
    $taxAmount = round($afterDiscount * ($taxRate / 100), 2);

    $total = round($afterDiscount + $taxAmount, 2);
    if ($total < 0) $total = 0.0;

    return [
        'items'           => $prepared,
        'subtotal'        => $subtotal,
        'discount_amount' => $discountAmount,
        'tax_amount'      => $taxAmount,
        'total'           => $total,
    ];
}

/**
 * Validate raw form input for a quote/invoice.
 * Returns [ $clean, $errors ].
 */
function doc_validate_input(array $in): array
{
    $errors = [];

    $clientId    = (int)    ($in['client_id']      ?? 0);
    $issueDate   = trim((string) ($in['issue_date']    ?? ''));
    $validUntil  = trim((string) ($in['valid_until']   ?? $in['due_date'] ?? ''));
    $currency    = trim((string) ($in['currency']      ?? ''));
    $discType    = (string) ($in['discount_type']      ?? 'none');
    $discValue   = (float)  ($in['discount_value']     ?? 0);
    $taxRate     = (float)  ($in['tax_rate']           ?? 0);
    $notes       = clean($in['notes']    ?? null);
    $terms       = clean($in['terms']    ?? null);

    if ($clientId <= 0)  $errors['client_id']  = 'Choose a client.';
    if (!$issueDate)     $errors['issue_date'] = 'Issue date is required.';
    elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $issueDate)) {
        $errors['issue_date'] = 'Invalid date.';
    }

    if ($validUntil && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $validUntil)) {
        $errors['valid_until'] = 'Invalid date.';
    }

    if (!preg_match('/^[A-Z]{3}$/', $currency)) {
        $errors['currency'] = 'Choose a currency.';
    }

    if (!in_array($discType, ['none', 'percent', 'fixed'], true)) {
        $discType = 'none';
    }
    if ($discValue < 0) $discValue = 0;
    if ($taxRate  < 0)  $taxRate  = 0;
    if ($discType === 'percent' && $discValue > 100) {
        $errors['discount_value'] = 'Percent cannot exceed 100.';
    }

    $clean = [
        'client_id'      => $clientId,
        'issue_date'     => $issueDate,
        'valid_until'    => $validUntil ?: null,
        'currency'       => $currency,
        'discount_type'  => $discType,
        'discount_value' => round($discValue, 2),
        'tax_rate'       => round($taxRate, 2),
        'notes'          => $notes,
        'terms'          => $terms,
    ];

    return [$clean, $errors];
}

/**
 * Status badge HTML for a quote or invoice.
 */
function doc_status_badge(string $type, string $status): string
{
    $map = [
        // quotes
        'draft'     => ['draft',   'Draft'],
        'sent'      => ['info',    'Sent'],
        'accepted'  => ['paid',    'Accepted'],
        'rejected'  => ['overdue', 'Rejected'],
        'expired'   => ['pending', 'Expired'],
        // invoices
        'pending'   => ['pending', 'Pending'],
        'cancelled' => ['overdue', 'Cancelled'],
    ];

    [$cls, $label] = $map[$status] ?? ['draft', ucfirst($status)];
    return '<span class="badge badge--' . e($cls) . '">' . e($label) . '</span>';
}

/**
 * True if a quote should be considered expired (computed, not stored).
 */
function quote_is_expired(array $quote): bool
{
    if (!$quote['valid_until']) return false;
    if (in_array($quote['status'], ['accepted', 'rejected'], true)) return false;
    return $quote['valid_until'] < date('Y-m-d');
}

/**
 * Effective status for display (handles computed "expired").
 */
function quote_effective_status(array $quote): string
{
    return quote_is_expired($quote) ? 'expired' : $quote['status'];
}