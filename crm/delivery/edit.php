<?php
require_once __DIR__ . '/../partials/bootstrap.php';
require_once __DIR__ . '/../../includes/crm_delivery.php';

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
if (!$id || !$pdo) { redirect('/crm/delivery/'); }
ensure_delivery_tables($pdo);

$s = $pdo->prepare("SELECT * FROM delivery_notes WHERE id=?");
$s->execute([$id]); $dn = $s->fetch();
if (!$dn) { flash('error', 'Delivery note not found.'); redirect('/crm/delivery/'); }

$errors  = [];
$clients = $pdo->query("SELECT id,name,company FROM clients ORDER BY name")->fetchAll();
$f = [
    'client_id' => $dn['client_id'], 'title' => (string)$dn['title'], 'delivery_date' => $dn['delivery_date'],
    'delivered_to' => (string)$dn['delivered_to'], 'delivery_address' => (string)$dn['delivery_address'],
    'driver_name' => (string)$dn['driver_name'], 'vehicle_no' => (string)$dn['vehicle_no'],
    'notes' => (string)$dn['notes'], 'status' => $dn['status'], 'received_by' => (string)$dn['received_by'],
    'quotation_id' => (int)$dn['quotation_id'], 'invoice_id' => (int)$dn['invoice_id'],
];
$si = $pdo->prepare("SELECT description,quantity,unit FROM delivery_note_items WHERE delivery_note_id=? ORDER BY sort_order");
$si->execute([$id]); $rows = $si->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) { $errors['general'] = 'Session expired — please try again.'; }
    foreach (['title','delivery_date','delivered_to','delivery_address','driver_name','vehicle_no','notes','received_by'] as $k) $f[$k] = trim($_POST[$k] ?? '');
    $client_choice = client_choice($errors);
    $f['client_id'] = max(0, $client_choice);
    $f['status']    = in_array($_POST['status'] ?? '', ['draft','dispatched','delivered','cancelled']) ? $_POST['status'] : $dn['status'];
    $items = delivery_post_items();
    $rows  = array_map(fn($i) => ['description' => $i['description'], 'quantity' => $i['quantity'], 'unit' => $i['unit']], $items);

    if ($f['title'] === '') $errors['title']     = 'Enter a description.';
    if (empty($items))      $errors['items']     = 'Add at least one item.';
    if (!$f['delivery_date']) $f['delivery_date'] = $dn['delivery_date'];

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            if ($client_choice === -1) { $f['client_id'] = create_one_time_client($pdo); }
            $pdo->prepare(
                "UPDATE delivery_notes SET client_id=?,title=?,delivery_date=?,delivered_to=?,delivery_address=?,driver_name=?,vehicle_no=?,status=?,notes=?,received_by=? WHERE id=?"
            )->execute([
                $f['client_id'], $f['title'], $f['delivery_date'], $f['delivered_to'] ?: null, $f['delivery_address'] ?: null,
                $f['driver_name'] ?: null, $f['vehicle_no'] ?: null, $f['status'], $f['notes'] ?: null, $f['received_by'] ?: null, $id,
            ]);
            $pdo->prepare("DELETE FROM delivery_note_items WHERE delivery_note_id=?")->execute([$id]);
            $ins = $pdo->prepare("INSERT INTO delivery_note_items (delivery_note_id,description,quantity,unit,sort_order) VALUES (?,?,?,?,?)");
            foreach ($items as $i => $it) $ins->execute([$id, $it['description'], $it['quantity'], $it['unit'], $i]);
            log_activity($pdo, 'delivery_updated', "Delivery note {$dn['dn_number']} updated.", 'delivery_note', $id);
            $pdo->commit();
            flash('success', "Delivery note {$dn['dn_number']} updated.");
            redirect("/crm/delivery/view?id=$id");
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Delivery edit: ' . $e->getMessage());
            $errors['general'] = 'Database error. Please try again.';
        }
    }
}

$page_title = 'Edit ' . $dn['dn_number'];
$active_nav = 'delivery';
$is_edit = true; $submit_label = 'Save Changes'; $cancel_url = "/crm/delivery/view?id=$id"; $source_html = '';
require_once __DIR__ . '/../partials/header.php';
require __DIR__ . '/_form.php';
require_once __DIR__ . '/../partials/footer.php';
