<?php
/**
 * QIP — Quote helpers. All scoped to current business.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/documents.php';

function quotes_list(?string $q = null, ?string $status = null): array
{
    $biz = current_business();
    if (!$biz) return [];

    $sql = 'SELECT q.*, c.name AS client_name
            FROM quotes q
            JOIN clients c ON c.id = q.client_id
            WHERE q.business_id = ?';
    $args = [$biz['id']];

    if ($q !== null && $q !== '') {
        $sql .= ' AND (q.number LIKE ? OR c.name LIKE ?)';
        $like = '%' . $q . '%';
        $args[] = $like; $args[] = $like;
    }

    if ($status !== null && $status !== '' && $status !== 'all') {
        if ($status === 'expired') {
            $sql .= " AND q.status IN ('draft','sent') AND q.valid_until IS NOT NULL AND q.valid_until < CURDATE()";
        } else {
            $sql .= ' AND q.status = ?';
            $args[] = $status;
        }
    }

    $sql .= ' ORDER BY q.id DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    return $stmt->fetchAll();
}

function quote_get(int $id): ?array
{
    $biz = current_business();
    if (!$biz) return null;

    $stmt = db()->prepare(
        'SELECT q.*, c.name AS client_name, c.company AS client_company,
                c.email AS client_email, c.address AS client_address
         FROM quotes q
         JOIN clients c ON c.id = q.client_id
         WHERE q.id = ? AND q.business_id = ? LIMIT 1'
    );
    $stmt->execute([$id, $biz['id']]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function quote_items(int $quoteId): array
{
    $stmt = db()->prepare(
        'SELECT * FROM quote_items WHERE quote_id = ? ORDER BY position ASC, id ASC'
    );
    $stmt->execute([$quoteId]);
    return $stmt->fetchAll();
}

/**
 * Insert or replace items for a quote (inside a transaction).
 */
function quote_save_items(int $quoteId, array $items): void
{
    db()->prepare('DELETE FROM quote_items WHERE quote_id = ?')->execute([$quoteId]);

    $stmt = db()->prepare(
        'INSERT INTO quote_items (quote_id, position, description, quantity, unit_price, line_total)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    foreach ($items as $it) {
        $stmt->execute([
            $quoteId,
            $it['position'],
            $it['description'],
            $it['quantity'],
            $it['unit_price'],
            $it['line_total'],
        ]);
    }
}

/**
 * Create a quote with items. Returns new id.
 */
function quote_create(array $meta, array $rawItems, array $clientSnapshotSkip = []): int
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
        $number = doc_next_number('quote');

        $stmt = $pdo->prepare(
            'INSERT INTO quotes
             (business_id, client_id, number, status, issue_date, valid_until, currency,
              subtotal, discount_type, discount_value, discount_amount,
              tax_rate, tax_amount, total, notes, terms)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $biz['id'],
            $meta['client_id'],
            $number,
            $meta['status'] ?? 'draft',
            $meta['issue_date'],
            $meta['valid_until'],
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
        quote_save_items($id, $calc['items']);

        $pdo->commit();
        return $id;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function quote_update(int $id, array $meta, array $rawItems): bool
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
            'UPDATE quotes SET
                client_id = ?, status = ?, issue_date = ?, valid_until = ?, currency = ?,
                subtotal = ?, discount_type = ?, discount_value = ?, discount_amount = ?,
                tax_rate = ?, tax_amount = ?, total = ?, notes = ?, terms = ?
             WHERE id = ? AND business_id = ?'
        );
        $stmt->execute([
            $meta['client_id'],
            $meta['status'],
            $meta['issue_date'],
            $meta['valid_until'],
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

        quote_save_items($id, $calc['items']);
        $pdo->commit();
        return true;
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function quote_set_status(int $id, string $status): bool
{
    $allowed = ['draft', 'sent', 'accepted', 'rejected'];
    if (!in_array($status, $allowed, true)) return false;

    $biz = current_business();
    $stmt = db()->prepare(
        'UPDATE quotes SET status = ? WHERE id = ? AND business_id = ?'
    );
    $stmt->execute([$status, $id, $biz['id']]);
    return $stmt->rowCount() > 0;
}

function quote_delete(int $id): bool
{
    $biz = current_business();
    $stmt = db()->prepare(
        "DELETE FROM quotes WHERE id = ? AND business_id = ? AND status = 'draft'"
    );
    $stmt->execute([$id, $biz['id']]);
    return $stmt->rowCount() > 0;
}

function quote_duplicate(int $id): ?int
{
    $biz = current_business();
    $src = quote_get($id);
    if (!$src) return null;

    $items = quote_items($id);
    $meta = [
        'client_id'      => $src['client_id'],
        'status'         => 'draft',
        'issue_date'     => date('Y-m-d'),
        'valid_until'    => $src['valid_until'],
        'currency'       => $src['currency'],
        'discount_type'  => $src['discount_type'],
        'discount_value' => $src['discount_value'],
        'tax_rate'       => $src['tax_rate'],
        'notes'          => $src['notes'],
        'terms'          => $src['terms'],
    ];
    return quote_create($meta, $items);
}

function quote_has_invoice(int $quoteId): bool
{
    $biz = current_business();
    $stmt = db()->prepare(
        'SELECT 1 FROM invoices WHERE source_quote_id = ? AND business_id = ? LIMIT 1'
    );
    $stmt->execute([$quoteId, $biz['id']]);
    return (bool) $stmt->fetchColumn();
}