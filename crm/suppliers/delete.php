<?php
require_once __DIR__ . '/../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) { flash('error', 'Invalid request.'); redirect('/crm/suppliers/'); }
$id = (int)($_POST['id'] ?? 0);

if ($pdo && $id) {
    try {
        $s = $pdo->prepare("SELECT code, name FROM suppliers WHERE id = ?");
        $s->execute([$id]);
        if ($sup = $s->fetch()) {
            $c = $pdo->prepare("SELECT COUNT(*) FROM purchases WHERE supplier_id = ?");
            $c->execute([$id]);
            if ((int)$c->fetchColumn() > 0) {
                flash('error', "{$sup['name']} has purchases recorded and cannot be deleted. Edit the supplier and set it to Inactive instead, or delete its purchases first.");
                redirect("/crm/suppliers/view?id=$id");
            }
            $pdo->prepare("DELETE FROM suppliers WHERE id = ?")->execute([$id]);
            log_activity($pdo, 'supplier_deleted', "Supplier {$sup['code']} — {$sup['name']} deleted.", 'supplier', null);
            flash('success', "Supplier {$sup['code']} deleted.");
        } else {
            flash('error', 'Supplier not found.');
        }
    } catch (Throwable $e) {
        error_log('Supplier delete: ' . $e->getMessage());
        flash('error', 'Could not delete the supplier.');
    }
}
redirect('/crm/suppliers/');
