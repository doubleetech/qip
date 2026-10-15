<?php
/**
 * QIP — Client helpers.
 * All operations are scoped to the current business.
 */

require_once __DIR__ . '/auth.php';

/**
 * List clients for the current business.
 * Optional $q searches name, company, email.
 */
function clients_list(?string $q = null): array
{
    $biz = current_business();
    if (!$biz) return [];

    $sql = 'SELECT * FROM clients WHERE business_id = ?';
    $args = [$biz['id']];

    if ($q !== null && $q !== '') {
        $sql .= ' AND (name LIKE ? OR company LIKE ? OR email LIKE ?)';
        $like = '%' . $q . '%';
        array_push($args, $like, $like, $like);
    }
    $sql .= ' ORDER BY name ASC';

    $stmt = db()->prepare($sql);
    $stmt->execute($args);
    return $stmt->fetchAll();
}

/**
 * Fetch a single client owned by the current business.
 * Returns null if not found or not owned.
 */
function client_get(int $id): ?array
{
    $biz = current_business();
    if (!$biz) return null;

    $stmt = db()->prepare(
        'SELECT * FROM clients WHERE id = ? AND business_id = ? LIMIT 1'
    );
    $stmt->execute([$id, $biz['id']]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/**
 * Count quotes + invoices attached to a client.
 */
function client_dependency_counts(int $id): array
{
    $biz = current_business();
    if (!$biz) return ['quotes' => 0, 'invoices' => 0];

    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM quotes WHERE client_id = ? AND business_id = ?'
    );
    $stmt->execute([$id, $biz['id']]);
    $q = (int) $stmt->fetchColumn();

    $stmt = db()->prepare(
        'SELECT COUNT(*) FROM invoices WHERE client_id = ? AND business_id = ?'
    );
    $stmt->execute([$id, $biz['id']]);
    $i = (int) $stmt->fetchColumn();

    return ['quotes' => $q, 'invoices' => $i];
}

/**
 * Validate client input. Returns [clean_values, errors].
 */
function client_validate(array $in): array
{
    $errors = [];
    $out = [
        'name'    => clean($in['name']    ?? null),
        'company' => clean($in['company'] ?? null),
        'email'   => clean($in['email']   ?? null),
        'phone'   => clean($in['phone']   ?? null),
        'address' => clean($in['address'] ?? null),
        'notes'   => clean($in['notes']   ?? null),
    ];

    if (!$out['name']) {
        $errors['name'] = 'Client name is required.';
    } elseif (mb_strlen($out['name']) > 255) {
        $errors['name'] = 'Name is too long.';
    }

    if ($out['email'] && !is_valid_email($out['email'])) {
        $errors['email'] = 'Enter a valid email address.';
    }

    return [$out, $errors];
}

/**
 * Create a client for the current business. Returns new id.
 */
function client_create(array $data): int
{
    $biz = current_business();
    $stmt = db()->prepare(
        'INSERT INTO clients (business_id, name, company, email, phone, address, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([
        $biz['id'],
        $data['name'],
        $data['company'],
        $data['email'],
        $data['phone'],
        $data['address'],
        $data['notes'],
    ]);
    return (int) db()->lastInsertId();
}

/**
 * Update a client. Returns true if a row was changed.
 */
function client_update(int $id, array $data): bool
{
    $biz = current_business();
    $stmt = db()->prepare(
        'UPDATE clients
         SET name = ?, company = ?, email = ?, phone = ?, address = ?, notes = ?
         WHERE id = ? AND business_id = ?'
    );
    $stmt->execute([
        $data['name'],
        $data['company'],
        $data['email'],
        $data['phone'],
        $data['address'],
        $data['notes'],
        $id,
        $biz['id'],
    ]);
    return $stmt->rowCount() > 0;
}

/**
 * Delete a client. Caller must check dependencies first.
 */
function client_delete(int $id): bool
{
    $biz = current_business();
    $stmt = db()->prepare(
        'DELETE FROM clients WHERE id = ? AND business_id = ?'
    );
    $stmt->execute([$id, $biz['id']]);
    return $stmt->rowCount() > 0;
}