<?php
/**
 * QIP — Invoice helpers. Scoped to current business.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/documents.php';

function invoices_list(?string $q = null, ?string $status = null): array
{
    $biz = current_business();
    if (!$biz) return [];

    $sql = 'SELECT i.*, c.name AS client_name
            FROM invoices i
            JOIN clients c ON c.id = i.client_id
            WHERE i.business_id = ?';
    $args = [$biz['id']];

    if ($q !== null && $q !== '') {
        $sql .= ' AND (i.number LIKE ? OR c.name LIKE ?)';
        $like = '%' . $q . '%';
        $args[] = $like; $args[] = $like;
    }

    if ($status !== null && $status !== '' && $status !== 'all') {
        $sql .= ' AND i.status = ?';
        $args[] = $status;
    }

    $sql .= ' ORDER BY i.id DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    return $stmt->fetchAll();
}

function invoice_get(int $id): ?array
{
    $biz = current_business();
    if (!$biz) return null;

    $stmt = db()->prepare(
        'SELECT i.*, c.name AS client_name, c.company AS client_company,
                c.email AS client_email, c.address AS client_address
         FROM invoices i
         JOIN clients c ON c.id = i.client_id
         WHERE i.id = ? AND i.business_id = ? LIMIT 1'
    );
    $stmt->execute([$id, $biz['id']]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function invoice_items(int $invoiceId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY position ASC, id ASC'
    );
    $stmt->execute([$invoiceId]);
    return $stmt->fetchAll();
}

function invoice_save_items(int $invoiceId, array $items): void
{
    db()->prepare('DELETE FROM invoice_items WHERE invoice_id = ?')->execute([$invoiceId]);

    $stmt = db()->prepare(
        'INSERT INTO invoice_items (invoice_id, position, description, quantity, unit_price, line_total)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    foreach ($items as $it) {
        $stmt->execute([
            $invoiceId,
            $it['position'],
            $it['description'],
            $it['quantity'],
            $it['unit_price'],
            $it['line_total'],
        ]);
    }
}

function invoice_create(array $meta, array $rawItems): int
{
    $biz = current_business();
    $pdo = db();

    $calc = doc_calculate(
        $rawItems,
        $meta['discount_type'],
        (float) $meta['discount_value'],
        (float) $meta['tax_rate']
    );

    $pdo->beginTransaction();
    try {
        $number = doc_next_number('invoice');

        $stmt = $pdo->prepare(
            'INSERT INTO invoices
             (business_id, client_id, source_quote_id, number, status, issue_date, due_date, currency,
              subtotal, discount_type, discount_value, discount_amount,
              tax_rate, tax_amount, total, notes, payment_terms)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $biz['id'],
            $meta['client_id'],
            $meta['source_quote_id'] ?? null,
            $number,
            $meta['status'] ?? 'draft',
            $meta['issue_date'],
            $meta['due_date'],
            $meta['currency'],
            $calc['subtotal'],
            $meta['discount_type'],
            $meta['discount_value'],
            $calc['discount_amount'],
            $meta['tax_rate'],
            $calc['tax_amount'],
            $calc['total'],
            $meta['notes'],
            $meta['terms'],
        ]);

        $id = (int) $pdo->lastInsertId();
        invoice_save_items($id, $calc['items']);

        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function invoice_update(int $id, array $meta, array $rawItems): bool
{
    $biz = current_business();
    $pdo = db();

    $calc = doc_calculate(
        $rawItems,
        $meta['discount_type'],
        (float) $meta['discount_value'],
        (float) $meta['tax_rate']
    );

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare(
            'UPDATE invoices SET
                client_id = ?, status = ?, issue_date = ?, due_date = ?, currency = ?,
                subtotal = ?, discount_type = ?, discount_value = ?, discount_amount = ?,
                tax_rate = ?, tax_amount = ?, total = ?, notes = ?, payment_terms = ?
             WHERE id = ? AND business_id = ?'
        );
        $stmt->execute([
            $meta['client_id'],
            $meta['status'],
            $meta['issue_date'],
            $meta['due_date'],
            $meta['currency'],
            $calc['subtotal'],
            $meta['discount_type'],
            $meta['discount_value'],
            $calc['discount_amount'],
            $meta['tax_rate'],
            $calc['tax_amount'],
            $calc['total'],
            $meta['notes'],
            $meta['terms'],
            $id,
            $biz['id'],
        ]);

        invoice_save_items($id, $calc['items']);
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function invoice_set_status(int $id, string $status): bool
{
    $allowed = ['draft', 'sent', 'pending', 'cancelled'];
    if (!in_array($status, $allowed, true)) return false;

    $biz = current_business();
    $stmt = db()->prepare(
        'UPDATE invoices SET status = ? WHERE id = ? AND business_id = ?'
    );
    $stmt->execute([$status, $id, $biz['id']]);
    return $stmt->rowCount() > 0;
}

function invoice_delete(int $id): bool
{
    $biz = current_business();
    $stmt = db()->prepare(
        "DELETE FROM invoices WHERE id = ? AND business_id = ? AND status = 'draft'"
    );
    $stmt->execute([$id, $biz['id']]);
    return $stmt->rowCount() > 0;
}

function invoice_duplicate(int $id): ?int
{
    $src = invoice_get($id);
    if (!$src) return null;

    $items = invoice_items($id);
    $meta = [
        'client_id'       => $src['client_id'],
        'source_quote_id' => null,
        'status'          => 'draft',
        'issue_date'      => date('Y-m-d'),
        'due_date'        => $src['due_date'],
        'currency'        => $src['currency'],
        'discount_type'   => $src['discount_type'],
        'discount_value'  => $src['discount_value'],
        'tax_rate'        => $src['tax_rate'],
        'notes'           => $src['notes'],
        'terms'           => $src['payment_terms'],
    ];
    return invoice_create($meta, $items);
}