<?php
$page_title = 'New Order';
$active_nav = 'orders';
require_once __DIR__ . '/../partials/header.php';

$clients = []; $quote = null;
$quote_id   = (int)($_GET['quote_id'] ?? 0);
$client_id_pre = (int)($_GET['client_id'] ?? 0);

if ($pdo) {
    $clients = $pdo->query("SELECT id, name, company FROM clients WHERE status='active' ORDER BY name")->fetchAll();
    if ($quote_id) {
        $sq = $pdo->prepare("SELECT q.*, GROUP_CONCAT(qi.description SEPARATOR '\n') AS items_list FROM quotations q LEFT JOIN quotation_items qi ON q.id=qi.quotation_id WHERE q.id=? GROUP BY q.id");
        $sq->execute([$quote_id]); $quote = $sq->fetch();
    }
}

$errors = [];
$f = [
    'client_id'       => $quote['client_id'] ?? $client_id_pre,
    'title'           => $quote['title'] ?? '',
    'due_date'        => date('Y-m-d', strtotime('+7 days')),
    'priority'        => 'normal',
    'delivery_address'=> '',
    'notes'           => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $f['client_id']        = (int)($_POST['client_id'] ?? 0);
    $f['title']            = clean($_POST['title'] ?? '');
    $f['due_date']         = clean($_POST['due_date'] ?? '');
    $f['priority']         = $_POST['priority'] === 'urgent' ? 'urgent' : 'normal';
    $f['delivery_address'] = clean($_POST['delivery_address'] ?? '');
    $f['notes']            = clean($_POST['notes'] ?? '');

    $descs = array_filter(array_map('trim', $_POST['item_desc'] ?? []));
    $qtys  = $_POST['item_qty']  ?? [];
    $units = $_POST['item_unit'] ?? [];

    if (!$f['client_id']) $errors['client_id'] = 'Select a client.';
    if (!$f['title'])     $errors['title']     = 'Job title is required.';

    if (empty($errors)) {
        $order_no = next_doc_number($pdo, 'ORD');
        $pdo->prepare(
            "INSERT INTO orders (order_number,client_id,quotation_id,title,status,priority,due_date,delivery_address,notes,created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?)"
        )->execute([
            $order_no, $f['client_id'], $quote_id ?: null, $f['title'],
            'pending', $f['priority'], $f['due_date'] ?: null,
            $f['delivery_address'] ?: null, $f['notes'] ?: null,
            $_SESSION['admin_id'] ?? null,
        ]);
        $oid = (int)$pdo->lastInsertId();

        $ins = $pdo->prepare("INSERT INTO order_items (order_id,description,quantity,unit,sort_order) VALUES (?,?,?,?,?)");
        foreach ($descs as $i => $desc) {
            $ins->execute([$oid, $desc, trim($qtys[$i]??''), trim($units[$i]??''), $i]);
        }

        if ($quote_id) {
            $pdo->prepare("UPDATE quotations SET status='accepted' WHERE id=? AND status='sent'")->execute([$quote_id]);
        }
        log_activity($pdo,'order_created',"Order $order_no booked.",'order',$oid);
        flash('success',"Order $order_no created.");
        redirect("/crm/orders/view?id=$oid");
    }
}
?>
<div class="max-w-2xl">
  <div class="mb-5">
    <a href="/crm/orders/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Orders
    </a>
  </div>
  <?php if ($quote): ?>
    <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 mb-5">
      <p class="text-sm font-medium text-indigo-800">From quote: <strong><?= htmlspecialchars($quote['quote_number']) ?></strong> — <?= htmlspecialchars($quote['title']) ?></p>
    </div>
  <?php endif; ?>

  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800">Book Production Order</h2>
    </div>
    <form method="POST" class="px-6 py-5 space-y-5" x-data="{ items: [{ desc:'', qty:'', unit:'' }] }">

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Client <span class="text-red-500">*</span></label>
        <select name="client_id" class="w-full border <?= isset($errors['client_id'])?'border-red-400':'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
          <option value="">— Select client —</option>
          <?php foreach ($clients as $cl): ?>
            <option value="<?= $cl['id'] ?>" <?= $f['client_id']==$cl['id']?'selected':'' ?>><?= htmlspecialchars($cl['name']) ?><?= $cl['company']?' — '.htmlspecialchars($cl['company']):'' ?></option>
          <?php endforeach; ?>
        </select>
        <?php if(isset($errors['client_id'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['client_id'] ?></p><?php endif; ?>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Job Title / Description <span class="text-red-500">*</span></label>
        <input type="text" name="title" value="<?= htmlspecialchars($f['title']) ?>" required
               placeholder="e.g. 200 Branded T-Shirts for Uganda Tourism Board"
               class="w-full border <?= isset($errors['title'])?'border-red-400':'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        <?php if(isset($errors['title'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['title'] ?></p><?php endif; ?>
      </div>

      <div class="grid grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Due Date</label>
          <input type="date" name="due_date" value="<?= $f['due_date'] ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Priority</label>
          <select name="priority" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <option value="normal"  <?= $f['priority']==='normal' ?'selected':''?>>Normal</option>
            <option value="urgent"  <?= $f['priority']==='urgent' ?'selected':''?>>🚨 Urgent</option>
          </select>
        </div>
      </div>

      <!-- Line items -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Job Items</label>
        <div class="space-y-2">
          <template x-for="(item, idx) in items" :key="idx">
            <div class="flex gap-2 items-center">
              <input type="text" :name="'item_desc['+idx+']'" x-model="item.desc" placeholder="Item description…"
                     class="flex-1 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
              <input type="text" :name="'item_qty['+idx+']'" x-model="item.qty" placeholder="Qty"
                     class="w-20 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
              <input type="text" :name="'item_unit['+idx+']'" x-model="item.unit" placeholder="Unit"
                     class="w-20 border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
              <button type="button" @click="items.splice(idx,1)" class="text-gray-300 hover:text-red-500 text-xl leading-none px-1">×</button>
            </div>
          </template>
        </div>
        <button type="button" @click="items.push({desc:'',qty:'',unit:''})"
                class="mt-2 text-sm text-amber-600 hover:text-amber-700 font-medium">+ Add item</button>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Delivery Address</label>
        <input type="text" name="delivery_address" value="<?= htmlspecialchars($f['delivery_address']) ?>"
               placeholder="Leave blank for collection"
               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Internal Notes</label>
        <textarea name="notes" rows="2"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 resize-none"><?= htmlspecialchars($f['notes']) ?></textarea>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors">
          Book Order
        </button>
        <a href="/crm/orders/" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
