<?php
require_once __DIR__ . '/../partials/bootstrap.php';
require_once __DIR__ . '/../../includes/crm_delivery.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { redirect('/crm/delivery/'); }
if (!csrf_valid()) { flash('error', 'Session expired — please try again.'); redirect('/crm/delivery/'); }

$id = (int)($_POST['id'] ?? 0);
if ($pdo && $id) {
    try {
        ensure_delivery_tables($pdo);
        $s = $pdo->prepare("SELECT dn_number FROM delivery_notes WHERE id=?");
        $s->execute([$id]); $no = $s->fetchColumn();
        if ($no) {
            $pdo->prepare("DELETE FROM delivery_notes WHERE id=?")->execute([$id]);
            log_activity($pdo, 'delivery_deleted', "Delivery note $no deleted.", 'delivery_note', $id);
            flash('success', "Delivery note $no deleted.");
        } else {
            flash('error', 'Delivery note not found.');
        }
    } catch (Throwable $e) {
        error_log('Delivery delete: ' . $e->getMessage());
        flash('error', 'Could not delete the delivery note.');
    }
}
redirect('/crm/delivery/');
