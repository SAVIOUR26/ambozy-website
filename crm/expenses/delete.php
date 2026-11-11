<?php
require_once __DIR__ . '/../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) { flash('error', 'Invalid request.'); redirect('/crm/expenses/'); }
$id = (int)($_POST['id'] ?? 0);

if ($pdo && $id) {
    try {
        $s = $pdo->prepare("SELECT ref, description FROM expenses WHERE id = ?");
        $s->execute([$id]);
        if ($exp = $s->fetch()) {
            $pdo->prepare("DELETE FROM expenses WHERE id = ?")->execute([$id]);
            log_activity($pdo, 'expense_deleted', "Expense {$exp['ref']} deleted.", 'expense', null);
            flash('success', "Expense {$exp['ref']} deleted.");
        } else {
            flash('error', 'Expense not found.');
        }
    } catch (Throwable $e) {
        error_log('Expense delete: ' . $e->getMessage());
        flash('error', 'Could not delete the expense.');
    }
}
redirect('/crm/expenses/');
