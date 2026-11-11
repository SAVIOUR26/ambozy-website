<?php
require_once __DIR__ . '/../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) { flash('error', 'Invalid request.'); redirect('/crm/payroll/'); }
$id = (int)($_POST['id'] ?? 0);

if ($pdo && $id) {
    try {
        $s = $pdo->prepare("SELECT ref, status FROM payroll WHERE id = ?");
        $s->execute([$id]);
        if ($pr = $s->fetch()) {
            if ($pr['status'] === 'paid') {
                flash('error', "Payroll {$pr['ref']} is already marked Paid and is locked.");
                redirect("/crm/payroll/view?id=$id");
            }
            $pdo->beginTransaction();
            // Remove the auto-created PAYE/NSSF obligations that have not been paid yet
            $pdo->prepare("DELETE FROM statutory_obligations WHERE payroll_id = ? AND amount_paid = 0")->execute([$id]);
            // payroll_items are removed automatically (ON DELETE CASCADE)
            $pdo->prepare("DELETE FROM payroll WHERE id = ?")->execute([$id]);
            log_activity($pdo, 'payroll_deleted', "Payroll {$pr['ref']} deleted.", 'payroll', null);
            $pdo->commit();
            flash('success', "Payroll {$pr['ref']} deleted. You can generate it again for that month.");
        } else {
            flash('error', 'Payroll not found.');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        error_log('Payroll delete: ' . $e->getMessage());
        flash('error', 'Could not delete the payroll.');
    }
}
redirect('/crm/payroll/');
