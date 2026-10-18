<?php
/**
 * QIP — Dashboard analytics helpers. Scoped to current business.
 * All money figures derive from persisted rows; nothing trusts stored labels.
 */

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/invoices.php';

/**
 * Resolve a period key to [from, to] inclusive date strings, or [null,null] for all time.
 * Keys: this_month, last_month, this_year, all.
 */
function analytics_period_range(string $period): array
{
    $today = new DateTimeImmutable('today');

    switch ($period) {
        case 'this_month':
            return [
                $today->modify('first day of this month')->format('Y-m-d'),
                $today->modify('last day of this month')->format('Y-m-d'),
            ];
        case 'last_month':
            $ref = $today->modify('first day of last month');
            return [
                $ref->format('Y-m-d'),
                $ref->modify('last day of this month')->format('Y-m-d'),
            ];
        case 'this_year':
            return [
                $today->setDate((int) $today->format('Y'), 1, 1)->format('Y-m-d'),
                $today->setDate((int) $today->format('Y'), 12, 31)->format('Y-m-d'),
            ];
        case 'all':
        default:
            return [null, null];
    }
}

/**
 * Core dashboard metrics for the current business.
 * Returns all figures in the business's default currency scope (mixed
 * currencies are summed as-is — by design, no FX conversion in Phase 3).
 */
function analytics_metrics(?string $from = null, ?string $to = null): array
{
    $biz = current_business();
    if (!$biz) {
        return [
            'invoiced' => 0.0, 'paid' => 0.0, 'outstanding' => 0.0, 'overdue' => 0.0,
            'clients'  => 0,   'expenses' => 0.0, 'payments_count' => 0,
            'invoices_count' => 0,
        ];
    }

    $pdo = db();
    $bizId = (int) $biz['id'];

    // Issued invoices in range (excludes draft + cancelled).
    $sql = "SELECT id, total, due_date
            FROM invoices
            WHERE business_id = ?
              AND status NOT IN ('draft','cancelled')";
    $args = [$bizId];
    if ($from) { $sql .= ' AND issue_date >= ?'; $args[] = $from; }
    if ($to)   { $sql .= ' AND issue_date <= ?'; $args[] = $to; }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($args);
    $invoices = $stmt->fetchAll();

    $invoiced   = 0.0;
    $outstanding = 0.0;
    $overdue     = 0.0;
    $ids = [];

    foreach ($invoices as $inv) {
        $ids[] = (int) $inv['id'];
        $invoiced += (float) $inv['total'];
    }

    // Paid totals for those invoices.
    $paidMap = $ids ? invoices_paid_map($ids) : [];

    $today = date('Y-m-d');
    foreach ($invoices as $inv) {
        $id   = (int) $inv['id'];
        $paid = $paidMap[$id] ?? 0.0;
        $bal  = max(0.0, round((float) $inv['total'] - $paid, 2));
        $outstanding += $bal;

        if ($bal > 0.0001 && $inv['due_date'] && $inv['due_date'] < $today) {
            $overdue += $bal;
        }
    }

    // Payments total in range (independent of invoice date filter).
    $psql = 'SELECT COALESCE(SUM(amount),0), COUNT(*) FROM payments WHERE business_id = ?';
    $pargs = [$bizId];
    if ($from) { $psql .= ' AND payment_date >= ?'; $pargs[] = $from; }
    if ($to)   { $psql .= ' AND payment_date <= ?'; $pargs[] = $to; }
    $pstmt = $pdo->prepare($psql);
    $pstmt->execute($pargs);
    [$paid, $payCount] = $pstmt->fetch(PDO::FETCH_NUM);

    // Expenses in range.
    $esql = 'SELECT COALESCE(SUM(amount),0) FROM expenses WHERE business_id = ?';
    $eargs = [$bizId];
    if ($from) { $esql .= ' AND expense_date >= ?'; $eargs[] = $from; }
    if ($to)   { $esql .= ' AND expense_date <= ?'; $eargs[] = $to; }
    $estmt = $pdo->prepare($esql);
    $estmt->execute($eargs);
    $expenses = (float) $estmt->fetchColumn();

    // Client count (lifetime, not date-scoped).
    $cstmt = $pdo->prepare('SELECT COUNT(*) FROM clients WHERE business_id = ?');
    $cstmt->execute([$bizId]);
    $clients = (int) $cstmt->fetchColumn();

    return [
        'invoiced'       => round($invoiced, 2),
        'paid'           => round((float) $paid, 2),
        'outstanding'    => round($outstanding, 2),
        'overdue'        => round($overdue, 2),
        'clients'        => $clients,
        'expenses'       => round($expenses, 2),
        'payments_count' => (int) $payCount,
        'invoices_count' => count($invoices),
    ];
}

/**
 * Overdue invoices requiring attention. Limited.
 */
function analytics_overdue_invoices(int $limit = 5): array
{
    $biz = current_business();
    if (!$biz) return [];

    $stmt = db()->prepare(
        "SELECT i.id, i.number, i.total, i.currency, i.due_date,
                c.name AS client_name
         FROM invoices i
         JOIN clients c ON c.id = i.client_id
         WHERE i.business_id = ?
           AND i.status NOT IN ('draft','cancelled')
           AND i.due_date IS NOT NULL
           AND i.due_date < CURDATE()
         ORDER BY i.due_date ASC
         LIMIT " . (int) $limit
    );
    $stmt->execute([$biz['id']]);
    $rows = $stmt->fetchAll();

    if (!$rows) return [];

    $ids = array_map(fn($r) => (int) $r['id'], $rows);
    $paidMap = invoices_paid_map($ids);

    $out = [];
    foreach ($rows as $r) {
        $paid = $paidMap[(int) $r['id']] ?? 0.0;
        $bal  = max(0.0, round((float) $r['total'] - $paid, 2));
        if ($bal <= 0.0001) continue;
        $r['balance'] = $bal;
        $out[] = $r;
    }
    return $out;
}

/**
 * Recent payments for the current business.
 */
function analytics_recent_payments(int $limit = 5): array
{
    $biz = current_business();
    if (!$biz) return [];

    $stmt = db()->prepare(
        'SELECT p.id, p.amount, p.payment_date, p.method, p.receipt_number,
                i.number AS invoice_number, i.currency AS invoice_currency,
                c.name AS client_name
         FROM payments p
         JOIN invoices i ON i.id = p.invoice_id
         JOIN clients  c ON c.id = i.client_id
         WHERE p.business_id = ?
         ORDER BY p.payment_date DESC, p.id DESC
         LIMIT ' . (int) $limit
    );
    $stmt->execute([$biz['id']]);
    return $stmt->fetchAll();
}