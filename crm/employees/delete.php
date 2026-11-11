<?php
require_once __DIR__ . '/../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) { flash('error', 'Invalid request.'); redirect('/crm/employees/'); }
$id = (int)($_POST['id'] ?? 0);

if ($pdo && $id) {
    try {
        $s = $pdo->prepare("SELECT emp_number, name FROM employees WHERE id = ?");
        $s->execute([$id]);
        if ($emp = $s->fetch()) {
            $c = $pdo->prepare("SELECT COUNT(*) FROM payroll_items WHERE employee_id = ?");
            $c->execute([$id]);
            if ((int)$c->fetchColumn() > 0) {
                flash('error', "{$emp['name']} already appears in payroll runs and cannot be deleted. Edit the employee and set the status to Terminated instead.");
                redirect("/crm/employees/view?id=$id");
            }
            $pdo->prepare("DELETE FROM employees WHERE id = ?")->execute([$id]);
            log_activity($pdo, 'employee_deleted', "Employee {$emp['emp_number']} — {$emp['name']} deleted.", 'employee', null);
            flash('success', "Employee {$emp['emp_number']} deleted.");
        } else {
            flash('error', 'Employee not found.');
        }
    } catch (Throwable $e) {
        error_log('Employee delete: ' . $e->getMessage());
        flash('error', 'Could not delete the employee.');
    }
}
redirect('/crm/employees/');
