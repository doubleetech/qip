<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/expenses.php';
require_business();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify(post('_csrf'))) {
    flash_set('error', 'Invalid request.');
    redirect('pages/expenses.php');
}

$id = (int) post('id', 0);
$e  = expense_get($id);
if (!$e) {
    flash_set('error', 'Expense not found.');
    redirect('pages/expenses.php');
}

expense_delete($id);
flash_set('success', 'Expense deleted.');
redirect('pages/expenses.php');