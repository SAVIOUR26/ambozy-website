<?php
require_once __DIR__ . '/../partials/bootstrap.php';
require_once __DIR__ . '/../../includes/crm_delivery.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id || !$pdo) { redirect('/crm/delivery/'); }
ensure_delivery_tables($pdo);

$load = function () use ($pdo, $id) {
    $s = $pdo->prepare(
        "SELECT d.*, c.name client_name, c.company client_company, c.email client_email, c.phone client_phone,
                q.quote_number, i.invoice_number
         FROM delivery_notes d JOIN clients c ON d.client_id=c.id
         LEFT JOIN quotations q ON d.quotation_id=q.id
         LEFT JOIN invoices i ON d.invoice_id=i.id
         WHERE d.id=?"
    );
    $s->execute([$id]); return $s->fetch();
};
$dn = $load();
if (!$dn) { flash('error', 'Delivery note not found.'); redirect('/crm/delivery/'); }

// Status change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) { flash('error', 'Session expired — please try again.'); redirect("/crm/delivery/view?id=$id"); }
    $ns = $_POST['new_status'] ?? '';
    if (in_array($ns, ['draft','dispatched','delivered','cancelled'])) {
        $received = trim($_POST['received_by'] ?? '');
        $pdo->prepare("UPDATE delivery_notes SET status=?, received_by=COALESCE(NULLIF(?,''), received_by) WHERE id=?")->execute([$ns, $received, $id]);
        log_activity($pdo, 'delivery_status', "Delivery note {$dn['dn_number']} → $ns.", 'delivery_note', $id);
        flash('success', "Status updated to $ns.");
    }
    redirect("/crm/delivery/view?id=$id");
}

$si = $pdo->prepare("SELECT * FROM delivery_note_items WHERE delivery_note_id=? ORDER BY sort_order"); $si->execute([$id]); $items = $si->fetchAll();

$page_title = $dn['dn_number'];
$active_nav = 'delivery';
require_once __DIR__ . '/../partials/header.php';
?>
<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
  <a href="/crm/delivery/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Delivery Notes</a>
  <div class="flex items-center gap-2 flex-wrap">
    <a href="/crm/delivery/pdf?id=<?= $id ?>" target="_blank" class="text-sm bg-white border border-gray-200 hover:border-indigo-400 text-gray-700 px-3 py-2 rounded-lg">🖨 Print / PDF</a>
    <a href="/crm/delivery/edit?id=<?= $id ?>" class="text-sm bg-white border border-gray-200 hover:border-amber-400 text-gray-700 px-3 py-2 rounded-lg">✎ Edit</a>
    <?= row_actions('/crm/delivery', $id, 'Delete delivery note ' . $dn['dn_number'] . '? This cannot be undone.') ?>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">
  <div class="xl:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="p-6 border-b border-gray-100 flex items-start justify-between gap-4 flex-wrap">
      <div>
        <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Delivery Note</p>
        <h2 class="text-2xl font-bold text-gray-900 font-mono"><?= htmlspecialchars($dn['dn_number']) ?></h2>
        <p class="text-gray-600 mt-1"><?= htmlspecialchars($dn['title']) ?></p>
      </div>
      <span class="text-sm px-3 py-1 rounded-full font-semibold <?= delivery_badge($dn['status']) ?>"><?= ucfirst($dn['status']) ?></span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-y md:divide-y-0 divide-gray-100 border-b border-gray-100">
      <?php foreach ([
        'Client' => $dn['client_name'], 'Delivery Date' => date('d M Y', strtotime($dn['delivery_date'])),
        'Delivered To' => $dn['delivered_to'] ?: '—', 'Received By' => $dn['received_by'] ?: '—',
        'Address' => $dn['delivery_address'] ?: '—', 'Driver' => $dn['driver_name'] ?: '—', 'Vehicle' => $dn['vehicle_no'] ?: '—',
      ] as $l => $v): ?>
        <div class="px-5 py-3.5"><p class="text-xs text-gray-400 font-medium"><?= $l ?></p><p class="text-sm font-medium text-gray-800 mt-0.5"><?= htmlspecialchars($v) ?></p></div>
      <?php endforeach; ?>
    </div>
    <div class="overflow-x-auto"><table class="w-full text-sm">
      <thead class="bg-gray-50 border-b border-gray-100"><tr>
        <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">#</th>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Description</th>
        <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Qty</th>
        <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Unit</th>
      </tr></thead>
      <tbody class="divide-y divide-gray-50">
        <?php foreach ($items as $i => $it): ?>
          <tr><td class="px-5 py-3.5 text-gray-400"><?= $i + 1 ?></td><td class="px-4 py-3.5 text-gray-800 font-medium"><?= htmlspecialchars($it['description']) ?></td><td class="px-4 py-3.5 text-center text-gray-600"><?= $it['quantity'] + 0 ?></td><td class="px-4 py-3.5 text-gray-500"><?= htmlspecialchars($it['unit']) ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table></div>
    <?php if ($dn['notes']): ?><div class="px-5 py-4 border-t border-gray-100"><p class="text-xs font-semibold text-gray-400 uppercase mb-1">Notes</p><p class="text-sm text-gray-600"><?= nl2br(htmlspecialchars($dn['notes'])) ?></p></div><?php endif; ?>
  </div>

  <div class="space-y-5">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
      <h3 class="font-semibold text-gray-800 mb-4">Update Status</h3>
      <form method="POST" class="space-y-3">
        <?= csrf_field() ?>
        <div class="grid grid-cols-2 gap-2">
          <?php foreach (['draft','dispatched','delivered','cancelled'] as $s): ?>
            <label class="cursor-pointer"><input type="radio" name="new_status" value="<?= $s ?>" class="sr-only peer" <?= $dn['status'] === $s ? 'checked' : '' ?>>
              <div class="text-center py-2 px-1 rounded-lg border-2 text-xs font-medium border-gray-100 text-gray-500 hover:border-amber-300 peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-700"><?= ucfirst($s) ?></div></label>
          <?php endforeach; ?>
        </div>
        <input type="text" name="received_by" value="<?= htmlspecialchars($dn['received_by'] ?? '') ?>" placeholder="Received by (when delivered)" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
        <button type="submit" class="w-full bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold py-2 rounded-lg">Update</button>
      </form>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 text-sm space-y-2">
      <h3 class="font-semibold text-gray-800 mb-1">Linked Documents</h3>
      <?php if ($dn['quotation_id']): ?><p><a class="text-amber-600 hover:text-amber-700 font-medium" href="/crm/quotes/view?id=<?= $dn['quotation_id'] ?>">Quotation <?= htmlspecialchars($dn['quote_number'] ?? '') ?> →</a></p><?php endif; ?>
      <?php if ($dn['invoice_id']): ?><p><a class="text-amber-600 hover:text-amber-700 font-medium" href="/crm/invoices/view?id=<?= $dn['invoice_id'] ?>">Invoice <?= htmlspecialchars($dn['invoice_number'] ?? '') ?> →</a></p><?php endif; ?>
      <?php if (!$dn['quotation_id'] && !$dn['invoice_id']): ?><p class="text-gray-400">Created manually.</p><?php endif; ?>
      <a href="/crm/clients/view?id=<?= $dn['client_id'] ?>" class="inline-block text-xs text-amber-600 font-medium">View client →</a>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
