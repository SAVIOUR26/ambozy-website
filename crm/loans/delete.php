<?php
require_once __DIR__ . '/../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) { flash('error', 'Invalid request.'); redirect('/crm/loans/'); }
$id = (int)($_POST['id'] ?? 0);

if ($pdo && $id) {
    try {
        $s = $pdo->prepare("SELECT ref, lender FROM loans WHERE id = ?");
        $s->execute([$id]);
        if ($loan = $s->fetch()) {
            // loan_repayments rows are removed automatically (ON DELETE CASCADE)
            $pdo->prepare("DELETE FROM loans WHERE id = ?")->execute([$id]);
            log_activity($pdo, 'loan_deleted', "Loan {$loan['ref']} ({$loan['lender']}) deleted.", 'loan', null);
            flash('success', "Loan {$loan['ref']} deleted.");
        } else {
            flash('error', 'Loan not found.');
        }
    } catch (Throwable $e) {
        error_log('Loan delete: ' . $e->getMessage());
        flash('error', 'Could not delete the loan.');
    }
}
redirect('/crm/loans/');
