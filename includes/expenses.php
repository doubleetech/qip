<?php
/**
 * QIP — Expense helpers. Scoped to current business.
 */

require_once __DIR__ . '/auth.php';

const EXPENSE_CATEGORIES = [
    'advertising' => 'Advertising',
    'software'    => 'Software & subscriptions',
    'equipment'   => 'Equipment',
    'transport'   => 'Transport',
    'office'      => 'Office supplies',
    'rent'        => 'Rent & utilities',
    'services'    => 'Professional services',
    'other'       => 'Other',
];

/**
 * List expenses with optional search, category, and date range.
 */
function expenses_list(?string $q = null, ?string $category = null, ?string $from = null, ?string $to = null): array
{
    $biz = current_business();
    if (!$biz) return [];

    $sql = 'SELECT * FROM expenses WHERE business_id = ?';
    $args = [$biz['id']];

    if ($q !== null && $q !== '') {
        $sql .= ' AND (description LIKE ? OR reference LIKE ?)';
        $like = '%' . $q . '%';
        $args[] = $like; $args[] = $like;
    }

    if ($category !== null && $category !== '' && $category !== 'all') {
        $sql .= ' AND category = ?';
        $args[] = $category;
    }

    if ($from) { $sql .= ' AND expense_date >= ?'; $args[] = $from; }
    if ($to)   { $sql .= ' AND expense_date <= ?'; $args[] = $to; }

    $sql .= ' ORDER BY expense_date DESC, id DESC';

    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    return $stmt->fetchAll();
}

/**
 * Fetch one expense owned by the current business.
 */
function expense_get(int $id): ?array
{
    $biz = current_business();
    if (!$biz) return null;

    $stmt = db()->prepare(
        'SELECT * FROM expenses WHERE id = ? AND business_id = ? LIMIT 1'
    );
    $stmt->execute([$id, $biz['id']]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Validate expense input. Returns [clean, errors].
 */
function expense_validate(array $in): array
{
    $errors = [];

    $description = clean($in['description'] ?? null);
    $category    = (string) ($in['category'] ?? 'other');
    $amount      = (float)  ($in['amount'] ?? 0);
    $date        = trim((string) ($in['expense_date'] ?? ''));
    $method      = clean($in['method'] ?? null);
    $reference   = clean($in['reference'] ?? null);
    $notes       = clean($in['notes'] ?? null);

    if (!$description) {
        $errors['description'] = 'Description is required.';
    } elseif (mb_strlen($description) > 500) {
        $errors['description'] = 'Description is too long.';
    }

    if (!isset(EXPENSE_CATEGORIES[$category])) {
        $category = 'other';
    }

    if ($amount <= 0) {
        $errors['amount'] = 'Enter an amount greater than zero.';
    }

    if ($date === '') {
        $errors['expense_date'] = 'Expense date is required.';
    } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
        $errors['expense_date'] = 'Invalid date.';
    }

    $clean = [
        'description'  => $description,
        'category'     => $category,
        'amount'       => round($amount, 2),
        'expense_date' => $date,
        'method'       => $method,
        'reference'    => $reference,
        'notes'        => $notes,
    ];

    return [$clean, $errors];
}

function expense_create(array $data): int
{
    $biz = current_business();
    $stmt = db()->prepare(
        'INSERT INTO expenses
         (business_id, description, category, amount, expense_date, method, reference, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $biz['id'],
        $data['description'],
        $data['category'],
        $data['amount'],
        $data['expense_date'],
        $data['method'],
        $data['reference'],
        $data['notes'],
    ]);
    return (int) db()->lastInsertId();
}

function expense_update(int $id, array $data): bool
{
    $biz = current_business();
    $stmt = db()->prepare(
        'UPDATE expenses
         SET description = ?, category = ?, amount = ?, expense_date = ?,
             method = ?, reference = ?, notes = ?
         WHERE id = ? AND business_id = ?'
    );
    $stmt->execute([
        $data['description'],
        $data['category'],
        $data['amount'],
        $data['expense_date'],
        $data['method'],
        $data['reference'],
        $data['notes'],
        $id,
        $biz['id'],
    ]);
    return $stmt->rowCount() > 0;
}

function expense_delete(int $id): bool
{
    $biz = current_business();
    $stmt = db()->prepare(
        'DELETE FROM expenses WHERE id = ? AND business_id = ?'
    );
    $stmt->execute([$id, $biz['id']]);
    return $stmt->rowCount() > 0;
}

/**
 * Total expenses for a date range (inclusive).
 */
function expenses_total(?string $from = null, ?string $to = null): float
{
    $biz = current_business();
    if (!$biz) return 0.0;

    $sql = 'SELECT COALESCE(SUM(amount), 0) FROM expenses WHERE business_id = ?';
    $args = [$biz['id']];
    if ($from) { $sql .= ' AND expense_date >= ?'; $args[] = $from; }
    if ($to)   { $sql .= ' AND expense_date <= ?'; $args[] = $to; }

    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    return round((float) $stmt->fetchColumn(), 2);
}