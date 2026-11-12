<?php
require_once __DIR__ . '/../partials/bootstrap.php';
require_once __DIR__ . '/../../includes/crm_delivery.php';

$quote_id   = (int)($_GET['quote_id']   ?? $_POST['source_quote']   ?? 0);
$invoice_id = (int)($_GET['invoice_id'] ?? $_POST['source_invoice'] ?? 0);

$errors = [];
$f = [
    'client_id' => '', 'title' => '', 'delivery_date' => date('Y-m-d'), 'delivered_to' => '',
    'delivery_address' => '', 'driver_name' => '', 'vehicle_no' => '', 'notes' => '',
    'quotation_id' => 0, 'invoice_id' => 0, 'status' => 'draft', 'received_by' => '',
];
$rows = [];
$clients = [];
$source_html = '';

if ($pdo) {
    try { ensure_delivery_tables($pdo); } catch (PDOException $e) { error_log('Delivery tables: ' . $e->getMessage()); $errors['general'] = 'Could not prepare delivery note tables.'; }
    $clients = $pdo->query("SELECT id,name,company,address,city FROM clients WHERE status='active' ORDER BY name")->fetchAll();

    $quote_options = $pdo->query(
        "SELECT q.id, q.quote_number, q.title, q.status, c.name client_name
         FROM quotations q JOIN clients c ON q.client_id = c.id
         ORDER BY FIELD(q.status,'accepted','sent','draft','expired','rejected'), q.id DESC LIMIT 150"
    )->fetchAll();
    $invoice_options = $pdo->query(
        "SELECT i.id, i.invoice_number, i.status, c.name client_name
         FROM invoices i JOIN clients c ON i.client_id = c.id
         WHERE i.status <> 'cancelled' ORDER BY i.id DESC LIMIT 150"
    )->fetchAll();

    $addr_of = function (int $cid) use ($clients): string {
        foreach ($clients as $c) if ((int)$c['id'] === $cid) return trim(unclean($c['address']) . ($c['city'] ? ', ' . unclean($c['city']) : ''), ', ');
        return '';
    };

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        if ($quote_id) {
            $s = $pdo->prepare("SELECT q.*, c.name client_name FROM quotations q JOIN clients c ON q.client_id=c.id WHERE q.id=?");
            $s->execute([$quote_id]); $q = $s->fetch();
            if ($q) {
                $f['client_id'] = $q['client_id']; $f['title'] = unclean($q['title']); $f['quotation_id'] = $quote_id;
                $f['delivery_address'] = $addr_of((int)$q['client_id']);
                $si = $pdo->prepare("SELECT description,quantity,unit FROM quotation_items WHERE quotation_id=? ORDER BY sort_order");
                $si->execute([$quote_id]); $rows = $si->fetchAll();
                $source_html = 'Based on quotation <span class="font-mono font-semibold">' . htmlspecialchars($q['quote_number']) . '</span> — quantities only, no prices. <a href="/crm/delivery/create" class="underline">Clear</a>';
            } else { $quote_id = 0; }
        } elseif ($invoice_id) {
            $s = $pdo->prepare("SELECT i.*, c.name client_name FROM invoices i JOIN clients c ON i.client_id=c.id WHERE i.id=?");
            $s->execute([$invoice_id]); $inv = $s->fetch();
            if ($inv) {
                $f['client_id'] = $inv['client_id']; $f['invoice_id'] = $invoice_id; $f['quotation_id'] = (int)$inv['quotation_id'];
                $f['title'] = 'Goods for invoice ' . $inv['invoice_number'];
                $f['delivery_address'] = $addr_of((int)$inv['client_id']);
                $si = $pdo->prepare("SELECT description,quantity,unit FROM invoice_items WHERE invoice_id=? ORDER BY sort_order");
                $si->execute([$invoice_id]); $rows = $si->fetchAll();
                $source_html = 'Based on invoice <span class="font-mono font-semibold">' . htmlspecialchars($inv['invoice_number']) . '</span> — quantities only, no prices. <a href="/crm/delivery/create" class="underline">Clear</a>';
            } else { $invoice_id = 0; }
        }
        if ($f['client_id'] === '' && !empty($_GET['client_id'])) $f['client_id'] = (int)$_GET['client_id'];
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($errors['general'])) {
        if (!csrf_valid()) { $errors['general'] = 'Session expired — please try again.'; }
        $client_choice = client_choice($errors);
        $f['client_id']        = max(0, $client_choice);
        $f['title']            = trim($_POST['title'] ?? '');
        $f['delivery_date']    = trim($_POST['delivery_date'] ?? '');
        $f['delivered_to']     = trim($_POST['delivered_to'] ?? '');
        $f['delivery_address'] = trim($_POST['delivery_address'] ?? '');
        $f['driver_name']      = trim($_POST['driver_name'] ?? '');
        $f['vehicle_no']       = trim($_POST['vehicle_no'] ?? '');
        $f['notes']            = trim($_POST['notes'] ?? '');
        $f['quotation_id']     = $quote_id;
        $f['invoice_id']       = $invoice_id;
        $items = delivery_post_items();
        $rows  = array_map(fn($i) => ['description' => $i['description'], 'quantity' => $i['quantity'], 'unit' => $i['unit']], $items);

        if ($f['title'] === '')    $errors['title']     = 'Enter a description.';
        if (empty($items))         $errors['items']     = 'Add at least one item.';
        if (!$f['delivery_date'])  $f['delivery_date']  = date('Y-m-d');

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();
                if ($client_choice === -1) { $f['client_id'] = create_one_time_client($pdo); }
                $dn_no = next_doc_number($pdo, 'DN');
                $pdo->prepare(
                    "INSERT INTO delivery_notes (dn_number,client_id,quotation_id,invoice_id,title,delivery_date,delivered_to,delivery_address,driver_name,vehicle_no,status,notes,created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?,'draft',?,?)"
                )->execute([
                    $dn_no, $f['client_id'], $quote_id ?: null, $invoice_id ?: null, $f['title'], $f['delivery_date'],
                    $f['delivered_to'] ?: null, $f['delivery_address'] ?: null,
                    $f['driver_name'] ?: null, $f['vehicle_no'] ?: null,
                    $f['notes'] ?: null, $_SESSION['admin_id'] ?? null,
                ]);
                $did = (int)$pdo->lastInsertId();
                $ins = $pdo->prepare("INSERT INTO delivery_note_items (delivery_note_id,description,quantity,unit,sort_order) VALUES (?,?,?,?,?)");
                foreach ($items as $i => $it) $ins->execute([$did, $it['description'], $it['quantity'], $it['unit'], $i]);
                log_activity($pdo, 'delivery_created', "Delivery note $dn_no created.", 'delivery_note', $did);
                $pdo->commit();
                flash('success', "Delivery note $dn_no created.");
                redirect("/crm/delivery/view?id=$did");
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                error_log('Delivery create: ' . $e->getMessage());
                $errors['general'] = 'Database error. Please try again.';
            }
        }
    }
}

$page_title = 'New Delivery Note';
$active_nav = 'delivery';
$is_edit = false; $submit_label = 'Save Delivery Note'; $cancel_url = '/crm/delivery/';
require_once __DIR__ . '/../partials/header.php';
require __DIR__ . '/_form.php';
require_once __DIR__ . '/../partials/footer.php';
