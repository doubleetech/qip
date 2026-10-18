<?php
/**
 * QIP — Payment helpers. Scoped to current business.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/invoices.php';

const PAYMENT_METHODS = [
    'bank_transfer' => 'Bank transfer',
    'cash'          => 'Cash',
    'card'          => 'Card / POS',
    'payment_link'  => 'Payment link',
    'crypto'        => 'Cryptocurrency',
    'other'         => 'Other',
];

/**
 * Total amount paid against an invoice, from persisted payment rows.
 * Excludes a single payment id when given (used while editing).
 */
function payment_invoice_paid_total(int $invoiceId, ?int $excludePaymentId = null): float
{
    $biz = current_business();
    if (!$biz) return 0.0;

    $sql = 'SELECT COALESCE(SUM(amount), 0)
            FROM payments
            WHERE invoice_id = ? AND business_id = ?';
    $args = [$invoiceId, $biz['id']];

    if ($excludePaymentId !== null) {
        $sql .= ' AND id <> ?';
        $args[] = $excludePaymentId;
    }

    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    return round((float) $stmt->fetchColumn(), 2);
}

/**
 * Outstanding balance for an invoice. Never below zero.
 * Pass $excludePaymentId while editing a payment.
 */
function payment_invoice_balance(array $invoice, ?int $excludePaymentId = null): float
{
    $paid = payment_invoice_paid_total((int) $invoice['id'], $excludePaymentId);
    $bal  = round((float) $invoice['total'] - $paid, 2);
    return $bal < 0 ? 0.0 : $bal;
}

/**
 * List payments for the current business.
 * $q matches invoice number, client name, or reference.
 */
function payments_list(?string $q = null, ?string $method = null): array
{
    $biz = current_business();
    if (!$biz) return [];

    $sql = 'SELECT p.*,
                   i.number        AS invoice_number,
                   i.currency      AS invoice_currency,
                   i.total         AS invoice_total,
                   i.status        AS invoice_status,
                   c.name          AS client_name
            FROM payments p
            JOIN invoices i ON i.id = p.invoice_id
            JOIN clients  c ON c.id = i.client_id
            WHERE p.business_id = ?';
    $args = [$biz['id']];

    if ($q !== null && $q !== '') {
        $sql .= ' AND (i.number LIKE ? OR c.name LIKE ? OR p.reference LIKE ?)';
        $like = '%' . $q . '%';
        array_push($args, $like, $like, $like);
    }

    if ($method !== null && $method !== '' && $method !== 'all') {
        $sql .= ' AND p.method = ?';
        $args[] = $method;
    }

    $sql .= ' ORDER BY p.payment_date DESC, p.id DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    return $stmt->fetchAll();
}

/**
 * Payments attached to a single invoice (history).
 */
function payments_for_invoice(int $invoiceId): array
{
    $biz = current_business();
    if (!$biz) return [];

    $stmt = db()->prepare(
        'SELECT * FROM payments
         WHERE invoice_id = ? AND business_id = ?
         ORDER BY payment_date ASC, id ASC'
    );
    $stmt->execute([$invoiceId, $biz['id']]);
    return $stmt->fetchAll();
}

/**
 * Fetch one payment owned by the current business.
 */
function payment_get(int $id): ?array
{
    $biz = current_business();
    if (!$biz) return null;

    $stmt = db()->prepare(
        'SELECT p.*,
                i.number   AS invoice_number,
                i.total    AS invoice_total,
                i.currency AS invoice_currency,
                i.status   AS invoice_status,
                i.due_date AS invoice_due_date,
                c.name     AS client_name
         FROM payments p
         JOIN invoices i ON i.id = p.invoice_id
         JOIN clients  c ON c.id = i.client_id
         WHERE p.id = ? AND p.business_id = ?
         LIMIT 1'
    );
    $stmt->execute([$id, $biz['id']]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Invoices eligible for a new payment (not draft, not cancelled).
 */
function payments_eligible_invoices(): array
{
    $biz = current_business();
    if (!$biz) return [];

    $stmt = db()->prepare(
        "SELECT i.id, i.number, i.total, i.currency, i.due_date, i.status,
                c.name AS client_name
         FROM invoices i
         JOIN clients c ON c.id = i.client_id
         WHERE i.business_id = ?
           AND i.status NOT IN ('draft','cancelled')
         ORDER BY i.id DESC"
    );
    $stmt->execute([$biz['id']]);
    return $stmt->fetchAll();
}

/**
 * Validate payment input. Returns [clean, errors].
 * $invoice must be the invoice row; $excludePaymentId when editing.
 */
function payment_validate(array $in, array $invoice, ?int $excludePaymentId = null): array
{
    $errors = [];

    $amount = (float) ($in['amount'] ?? 0);
    $date   = trim((string) ($in['payment_date'] ?? ''));
    $method = (string) ($in['method'] ?? 'bank_transfer');
    $ref    = clean($in['reference'] ?? null);
    $notes  = clean($in['notes'] ?? null);

    if (!in_array($invoice['status'], ['sent', 'pending'], true) && $invoice['status'] !== 'partially_paid' && $invoice['status'] !== 'paid' && $invoice['status'] !== 'overdue') {
        // Base lifecycle only: draft and cancelled are rejected.
        if (in_array($invoice['status'], ['draft', 'cancelled'], true)) {
            $errors['invoice_id'] = 'This invoice cannot receive payments.';
        }
    }

    if ($amount <= 0) {
        $errors['amount'] = 'Enter an amount greater than zero.';
    } else {
        $balance = payment_invoice_balance($invoice, $excludePaymentId);
        if ($amount > $balance + 0.0001) {
            $errors['amount'] = 'Amount exceeds the outstanding balance of '
                . number_format($balance, 2) . '.';
        }
    }

    if ($date === '') {
        $errors['payment_date'] = 'Payment date is required.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $errors['payment_date'] = 'Invalid date.';
    }

    if (!isset(PAYMENT_METHODS[$method])) {
        $method = 'bank_transfer';
    }

    $clean = [
        'amount'       => round($amount, 2),
        'payment_date' => $date,
        'method'       => $method,
        'reference'    => $ref,
        'notes'        => $notes,
    ];

    return [$clean, $errors];
}

/**
 * Next receipt number for the current business. FOR UPDATE lock.
 * Format: REC-0001.
 */
function payment_next_receipt_number(): string
{
    $biz = current_business();
    $stmt = db()->prepare(
        "SELECT receipt_number FROM payments
         WHERE business_id = ? AND receipt_number LIKE 'REC-%'
         ORDER BY receipt_number DESC LIMIT 1 FOR UPDATE"
    );
    $stmt->execute([$biz['id']]);
    $last = $stmt->fetchColumn();

    $n = 1;
    if ($last) {
        $n = (int) substr($last, 4) + 1;
    }
    return 'REC-' . str_pad((string) $n, 4, '0', STR_PAD_LEFT);
}

/**
 * Record a payment. Transactional; recomputes balance under lock.
 * Returns the new payment id, or throws.
 */
function payment_create(int $invoiceId, array $data): int
{
    $biz = current_business();
    $pdo = db();

    $pdo->beginTransaction();
    try {
        // Lock invoice row to serialize concurrent payments.
        $lock = $pdo->prepare(
            'SELECT * FROM invoices WHERE id = ? AND business_id = ? FOR UPDATE'
        );
        $lock->execute([$invoiceId, $biz['id']]);
        $invoice = $lock->fetch();
        if (!$invoice) {
            throw new RuntimeException('Invoice not found.');
        }
        if (in_array($invoice['status'], ['draft', 'cancelled'], true)) {
            throw new RuntimeException('This invoice cannot receive payments.');
        }

        $paid    = payment_invoice_paid_total($invoiceId);
        $balance = round((float) $invoice['total'] - $paid, 2);
        if ($balance < 0) $balance = 0.0;
        if ($data['amount'] > $balance + 0.0001) {
            throw new RuntimeException('Amount exceeds the outstanding balance.');
        }

        $receipt = payment_next_receipt_number();

        $stmt = $pdo->prepare(
            'INSERT INTO payments
             (business_id, invoice_id, amount, payment_date, method, reference, notes, receipt_number)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $biz['id'],
            $invoiceId,
            $data['amount'],
            $data['payment_date'],
            $data['method'],
            $data['reference'],
            $data['notes'],
            $receipt,
        ]);

        $id = (int) $pdo->lastInsertId();
        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Update a payment. Re-validates against balance excluding this row.
 */
function payment_update(int $id, array $data): bool
{
    $biz = current_business();
    $pdo = db();

    $existing = payment_get($id);
    if (!$existing) return false;

    $pdo->beginTransaction();
    try {
        $lock = $pdo->prepare(
            'SELECT * FROM invoices WHERE id = ? AND business_id = ? FOR UPDATE'
        );
        $lock->execute([(int) $existing['invoice_id'], $biz['id']]);
        $invoice = $lock->fetch();
        if (!$invoice) {
            throw new RuntimeException('Invoice not found.');
        }

        $paid    = payment_invoice_paid_total((int) $invoice['id'], $id);
        $balance = round((float) $invoice['total'] - $paid, 2);
        if ($balance < 0) $balance = 0.0;
        if ($data['amount'] > $balance + 0.0001) {
            throw new RuntimeException('Amount exceeds the outstanding balance.');
        }

        $stmt = $pdo->prepare(
            'UPDATE payments
             SET amount = ?, payment_date = ?, method = ?, reference = ?, notes = ?
             WHERE id = ? AND business_id = ?'
        );
        $stmt->execute([
            $data['amount'],
            $data['payment_date'],
            $data['method'],
            $data['reference'],
            $data['notes'],
            $id,
            $biz['id'],
        ]);

        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Delete a payment. Invoice status recomputes automatically on read.
 */
function payment_delete(int $id): bool
{
    $biz = current_business();
    $stmt = db()->prepare(
        'DELETE FROM payments WHERE id = ? AND business_id = ?'
    );
    $stmt->execute([$id, $biz['id']]);
    return $stmt->rowCount() > 0;
}

/**
 * Total payments recorded in a date range (inclusive). Optional.
 */
function payments_total(?string $from = null, ?string $to = null): float
{
    $biz = current_business();
    if (!$biz) return 0.0;

    $sql = 'SELECT COALESCE(SUM(amount), 0) FROM payments WHERE business_id = ?';
    $args = [$biz['id']];
    if ($from) { $sql .= ' AND payment_date >= ?'; $args[] = $from; }
    if ($to)   { $sql .= ' AND payment_date <= ?'; $args[] = $to; }

    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    return round((float) $stmt->fetchColumn(), 2);
}