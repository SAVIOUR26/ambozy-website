<?php
require_once __DIR__ . '/../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) { flash('error', 'Invalid request.'); redirect('/crm/purchases/'); }
$id = (int)($_POST['id'] ?? 0);

if ($pdo && $id) {
    try {
        $s = $pdo->prepare("SELECT purchase_number FROM purchases WHERE id = ?");
        $s->execute([$id]);
        if ($p = $s->fetch()) {
            // purchase_items and supplier_payments are removed automatically (ON DELETE CASCADE)
            $pdo->prepare("DELETE FROM purchases WHERE id = ?")->execute([$id]);
            log_activity($pdo, 'purchase_deleted', "Purchase {$p['purchase_number']} deleted.", 'purchase', null);
            flash('success', "Purchase {$p['purchase_number']} deleted.");
        } else {
            flash('error', 'Purchase not found.');
        }
    } catch (Throwable $e) {
        error_log('Purchase delete: ' . $e->getMessage());
        flash('error', 'Could not delete the purchase.');
    }
}
redirect('/crm/purchases/');
