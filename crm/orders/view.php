<?php
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /crm/orders/'); exit; }
$page_title = 'Order';
$active_nav = 'orders';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../../includes/crm_email.php';

$order = null; $items = [];
if ($pdo) {
    $s = $pdo->prepare("SELECT o.*, c.name client_name, c.email client_email, c.phone client_phone FROM orders o JOIN clients c ON o.client_id=c.id WHERE o.id=?");
    $s->execute([$id]); $order = $s->fetch();
    if ($order) { $si=$pdo->prepare("SELECT * FROM order_items WHERE order_id=? ORDER BY sort_order"); $si->execute([$id]); $items=$si->fetchAll(); }
}
if (!$order) { flash('error','Order not found.'); redirect('/crm/orders/'); }
$page_title = $order['order_number'];

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $act = $_POST['action'] ?? '';
    if ($act === 'update_status') {
        $ns    = $_POST['new_status'] ?? '';
        $valid = ['pending','in_production','ready','delivered','completed','cancelled'];
        if (in_array($ns, $valid)) {
            $pdo->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$ns, $id]);
            log_activity($pdo,'order_status',"Order {$order['order_number']} → $ns.",'order',$id);

            // Auto-email on key statuses
            $notify = ['in_production','ready','delivered','completed'];
            if (in_array($ns,$notify) && $order['client_email']) {
                $order['status'] = $ns; // update for email
                $client = ['name'=>$order['client_name']];
                $html = email_order_status($order, $client);
                crm_send_email($pdo,'order_status',$order['client_email'],$order['client_name'],
                    "Order {$order['order_number']} — ".ucfirst(str_replace('_',' ',$ns)), $html, 'order',$id);
            }
            flash('success','Order status updated.');
            redirect("/crm/orders/view?id=$id");
        }
    }
    if ($act === 'create_invoice') {
        redirect("/crm/invoices/create?order_id=$id");
    }
}

$pipeline = ['pending','in_production','ready','delivered','completed'];
$cur_idx  = array_search($order['status'], $pipeline);
?>

<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
  <a href="/crm/orders/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Orders
  </a>
  <div class="flex items-center gap-2">
    <?php if (!in_array($order['status'],['completed','cancelled'])): ?>
    <form method="POST" class="inline">
      <input type="hidden" name="action" value="create_invoice">
      <button type="submit" class="text-sm bg-green-500 hover:bg-green-400 text-white px-3 py-2 rounded-lg font-medium">
        + Invoice This Order
      </button>
    </form>
    <?php endif; ?>
    <a href="/crm/orders/edit?id=<?= $id ?>" class="text-sm bg-amber-500 hover:bg-amber-400 text-white px-3 py-2 rounded-lg font-medium">
      Edit
    </a>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

  <!-- Order card -->
  <div class="xl:col-span-2 space-y-5">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <!-- Header -->
      <div class="p-6 border-b border-gray-100 flex items-start justify-between gap-4 flex-wrap">
        <div>
          <p class="text-xs text-gray-400 uppercase tracking-wider font-medium mb-1">Production Order</p>
          <h2 class="text-2xl font-bold text-gray-900 font-mono"><?= htmlspecialchars($order['order_number']) ?></h2>
          <p class="text-gray-600 mt-1"><?= htmlspecialchars($order['title']) ?></p>
        </div>
        <div class="flex items-center gap-2">
          <?php if ($order['priority']==='urgent'): ?>
            <span class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded-full font-bold">🚨 URGENT</span>
          <?php endif; ?>
          <span class="text-sm px-3 py-1 rounded-full font-semibold <?= order_badge($order['status']) ?>">
            <?= ucfirst(str_replace('_',' ',$order['status'])) ?>
          </span>
        </div>
      </div>

      <!-- Meta -->
      <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-y md:divide-y-0 divide-gray-100 border-b border-gray-100">
        <?php
        $meta = [
            'Client'   => htmlspecialchars($order['client_name']),
            'Booked'   => date('d M Y', strtotime($order['created_at'])),
            'Due Date' => $order['due_date'] ? date('d M Y', strtotime($order['due_date'])) : '—',
            'Delivery' => $order['delivery_address'] ? htmlspecialchars($order['delivery_address']) : 'Collection',
        ];
        foreach ($meta as $lbl=>$val):
        ?>
        <div class="px-5 py-3.5">
          <p class="text-xs text-gray-400 font-medium"><?= $lbl ?></p>
          <p class="text-sm font-medium text-gray-800 mt-0.5"><?= $val ?></p>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Production pipeline -->
      <?php if ($order['status'] !== 'cancelled'): ?>
      <div class="px-6 py-5 border-b border-gray-100">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-4">Production Progress</p>
        <div class="flex items-center gap-0 overflow-x-auto pb-1">
          <?php foreach ($pipeline as $idx => $step): ?>
            <div class="flex items-center shrink-0">
              <div class="flex flex-col items-center">
                <div class="w-9 h-9 rounded-full flex items-center justify-center text-xs font-bold border-2
                            <?= $idx <= ($cur_idx !== false ? $cur_idx : -1)
                                ? 'bg-amber-500 border-amber-500 text-white'
                                : 'border-gray-200 text-gray-300' ?>">
                  <?php if ($idx < ($cur_idx !== false ? $cur_idx : -1)): ?>
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                  <?php else: ?>
                    <?= $idx + 1 ?>
                  <?php endif; ?>
                </div>
                <span class="text-xs mt-1.5 whitespace-nowrap capitalize <?= $idx===($cur_idx!==false?$cur_idx:-1)?'text-amber-600 font-semibold':'text-gray-400' ?>">
                  <?= str_replace('_',' ',$step) ?>
                </span>
              </div>
              <?php if ($idx < count($pipeline)-1): ?>
                <div class="w-12 h-0.5 mb-5 mx-1 <?= $idx < ($cur_idx!==false?$cur_idx:-1)?'bg-amber-400':'bg-gray-200' ?>"></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Job items -->
      <?php if (!empty($items)): ?>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Item Description</th>
              <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase w-24">Quantity</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase w-24">Unit</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($items as $it): ?>
            <tr>
              <td class="px-5 py-3 text-gray-800"><?= htmlspecialchars($it['description']) ?></td>
              <td class="px-4 py-3 text-center text-gray-600"><?= htmlspecialchars($it['quantity']??'') ?></td>
              <td class="px-4 py-3 text-gray-500"><?= htmlspecialchars($it['unit']??'') ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <?php endif; ?>

      <?php if ($order['notes']): ?>
        <div class="px-5 py-4 border-t border-gray-100">
          <p class="text-xs font-semibold text-gray-400 uppercase mb-1">Notes</p>
          <p class="text-sm text-gray-600"><?= nl2br(htmlspecialchars($order['notes'])) ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right: status update -->
  <div class="space-y-5">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
      <h3 class="font-semibold text-gray-800 mb-4">Update Status</h3>
      <p class="text-xs text-gray-400 mb-3">Client will be emailed automatically when status changes to "In Production", "Ready", "Delivered" or "Completed".</p>
      <form method="POST" class="space-y-3">
        <input type="hidden" name="action" value="update_status">
        <div class="space-y-1.5">
          <?php foreach (['pending','in_production','ready','delivered','completed','cancelled'] as $s): ?>
            <label class="flex items-center gap-3 p-2.5 rounded-lg border cursor-pointer transition-colors
                          <?= $order['status']===$s?'border-amber-400 bg-amber-50':'border-gray-100 hover:border-amber-200' ?>">
              <input type="radio" name="new_status" value="<?= $s ?>" class="text-amber-500 focus:ring-amber-400" <?= $order['status']===$s?'checked':'' ?>>
              <span class="text-sm font-medium text-gray-700 capitalize flex-1"><?= str_replace('_',' ',$s) ?></span>
              <span class="text-xs px-1.5 py-0.5 rounded-full <?= order_badge($s) ?>">&nbsp;</span>
            </label>
          <?php endforeach; ?>
        </div>
        <button type="submit" class="w-full bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors">
          Update Status
        </button>
      </form>
    </div>

    <!-- Client -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
      <h3 class="font-semibold text-gray-800 mb-3">Client</h3>
      <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($order['client_name']) ?></p>
      <?php if ($order['client_email']): ?><p class="text-sm text-amber-600 mt-1"><?= htmlspecialchars($order['client_email']) ?></p><?php endif; ?>
      <?php if ($order['client_phone']): ?><p class="text-sm text-gray-500"><?= htmlspecialchars($order['client_phone']) ?></p><?php endif; ?>
      <a href="/crm/clients/view?id=<?= $order['client_id'] ?>" class="mt-3 inline-block text-xs text-amber-600 hover:text-amber-700 font-medium">View client →</a>
    </div>
  </div>

</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
