<?php
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /crm/orders/'); exit; }
$page_title = 'Edit Order';
$active_nav = 'orders';
require_once __DIR__ . '/../partials/header.php';

$order = null; $clients = [];
if ($pdo) {
    $s = $pdo->prepare("SELECT * FROM orders WHERE id=?"); $s->execute([$id]); $order = $s->fetch();
    $clients = $pdo->query("SELECT id,name,company FROM clients WHERE status='active' ORDER BY name")->fetchAll();
}
if (!$order) { flash('error','Order not found.'); redirect('/crm/orders/'); }

$f = $order; $errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $f['client_id']        = (int)($_POST['client_id'] ?? 0);
    $f['title']            = clean($_POST['title'] ?? '');
    $f['due_date']         = clean($_POST['due_date'] ?? '');
    $f['priority']         = $_POST['priority'] === 'urgent' ? 'urgent' : 'normal';
    $f['status']           = clean($_POST['status'] ?? 'pending');
    $f['delivery_address'] = clean($_POST['delivery_address'] ?? '');
    $f['notes']            = clean($_POST['notes'] ?? '');

    if (!$f['client_id']) $errors['client_id'] = 'Select a client.';
    if (!$f['title'])     $errors['title']     = 'Title is required.';

    if (empty($errors)) {
        $pdo->prepare(
            "UPDATE orders SET client_id=?,title=?,due_date=?,priority=?,status=?,delivery_address=?,notes=? WHERE id=?"
        )->execute([$f['client_id'],$f['title'],$f['due_date']?:null,$f['priority'],$f['status'],$f['delivery_address']?:null,$f['notes']?:null,$id]);
        log_activity($pdo,'order_updated',"Order {$order['order_number']} updated.",'order',$id);
        flash('success','Order updated.');
        redirect("/crm/orders/view.php?id=$id");
    }
}
?>
<div class="max-w-xl">
  <div class="mb-5"><a href="/crm/orders/view.php?id=<?= $id ?>" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back to Order</a></div>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800">Edit Order</h2>
      <p class="text-sm font-mono text-gray-400 mt-0.5"><?= htmlspecialchars($order['order_number']) ?></p>
    </div>
    <form method="POST" class="px-6 py-5 space-y-5">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Client <span class="text-red-500">*</span></label>
        <select name="client_id" class="w-full border <?= isset($errors['client_id'])?'border-red-400':'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
          <option value="">— Select —</option>
          <?php foreach ($clients as $cl): ?><option value="<?= $cl['id'] ?>" <?= $f['client_id']==$cl['id']?'selected':'' ?>><?= htmlspecialchars($cl['name']) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Job Title <span class="text-red-500">*</span></label>
        <input type="text" name="title" value="<?= htmlspecialchars($f['title']) ?>" required
               class="w-full border <?= isset($errors['title'])?'border-red-400':'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
      </div>
      <div class="grid grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Due Date</label>
          <input type="date" name="due_date" value="<?= $f['due_date']??'' ?>" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Priority</label>
          <select name="priority" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <option value="normal" <?= $f['priority']==='normal'?'selected':''?>>Normal</option>
            <option value="urgent" <?= $f['priority']==='urgent'?'selected':''?>>🚨 Urgent</option>
          </select>
        </div>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
        <select name="status" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
          <?php foreach(['pending','in_production','ready','delivered','completed','cancelled'] as $s): ?>
            <option value="<?= $s ?>" <?= $f['status']===$s?'selected':''?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Delivery Address</label>
        <input type="text" name="delivery_address" value="<?= htmlspecialchars($f['delivery_address']??'') ?>" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
        <textarea name="notes" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 resize-none"><?= htmlspecialchars($f['notes']??'') ?></textarea>
      </div>
      <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg">Save Changes</button>
        <a href="/crm/orders/view.php?id=<?= $id ?>" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
