<?php
require_once __DIR__ . '/../partials/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valid()) { flash('error', 'Invalid request.'); redirect('/crm/statutory/'); }
$id = (int)($_POST['id'] ?? 0);

if ($pdo && $id) {
    try {
        $s = $pdo->prepare("SELECT type, period_month, period_year FROM statutory_obligations WHERE id = ?");
        $s->execute([$id]);
        if ($ob = $s->fetch()) {
            $pdo->prepare("DELETE FROM statutory_obligations WHERE id = ?")->execute([$id]);
            log_activity($pdo, 'statutory_deleted', "Statutory obligation ({$ob['type']}) for {$ob['period_month']}/{$ob['period_year']} deleted.", 'statutory', null);
            flash('success', 'Obligation deleted.');
        } else {
            flash('error', 'Obligation not found.');
        }
    } catch (Throwable $e) {
        error_log('Statutory delete: ' . $e->getMessage());
        flash('error', 'Could not delete the obligation.');
    }
}
redirect('/crm/statutory/');
