<?php
require_once __DIR__ . '/../partials/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { redirect('/crm/purchases/'); }

$purchase = null; $old_items = []; $suppliers = [];
if ($pdo) {
    $q = $pdo->prepare("SELECT * FROM purchases WHERE id = ?");
    $q->execute([$id]);
    $purchase = $q->fetch();
    if ($purchase) {
        $qi = $pdo->prepare("SELECT * FROM purchase_items WHERE purchase_id = ? ORDER BY sort_order, id");
        $qi->execute([$id]);
        $old_items = $qi->fetchAll();
        $suppliers = $pdo->query("SELECT id, code, name FROM suppliers WHERE status='active' OR id = " . (int)$purchase['supplier_id'] . " ORDER BY name")->fetchAll();
    }
}
if (!$purchase) { flash('error', 'Purchase not found.'); redirect('/crm/purchases/'); }

$errors = [];
$f = [
    'supplier_id'   => (int)$purchase['supplier_id'],
    'payment_type'  => $purchase['payment_type'],
    'purchase_date' => $purchase['purchase_date'],
    'due_date'      => $purchase['due_date'] ?? '',
    'notes'         => unclean($purchase['notes']),
];
$js_items = [];
foreach ($old_items as $it) {
    $js_items[] = [
        'description' => unclean($it['description']),
        'qty'   => (float)$it['quantity'],
        'unit'  => unclean($it['unit']),
        'price' => (float)$it['unit_price'],
        'total' => (float)$it['total'],
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $f['supplier_id']  = (int)($_POST['supplier_id'] ?? 0);
    $f['payment_type'] = in_array($_POST['payment_type'] ?? '', ['cash','credit'], true) ? $_POST['payment_type'] : 'cash';
    $f['purchase_date']= trim($_POST['purchase_date'] ?? '');
    $f['due_date']     = trim($_POST['due_date'] ?? '');
    $f['notes']        = trim($_POST['notes'] ?? '');

    $items = []; $js_items = [];
    $descs = $_POST['item_desc']  ?? [];
    $qtys  = $_POST['item_qty']   ?? [];
    $units = $_POST['item_unit']  ?? [];
    $prices= $_POST['item_price'] ?? [];
    $n = 0;
    foreach ($descs as $i => $desc) {
        $desc = trim($desc);
        if ($desc === '') continue;
        $qty   = max(0, (float)($qtys[$i] ?? 1));
        $price = max(0, (float)str_replace(',', '', $prices[$i] ?? '0'));
        $unit  = trim($units[$i] ?? 'piece') ?: 'piece';
        $items[] = ['description' => clean($desc), 'quantity' => $qty, 'unit' => clean($unit), 'unit_price' => $price, 'total' => round($qty * $price, 2), 'sort_order' => $n++];
        $js_items[] = ['description' => $desc, 'qty' => $qty, 'unit' => $unit, 'price' => $price, 'total' => round($qty * $price, 2)];
    }

    if (!$f['supplier_id'])   $errors['supplier_id'] = 'Please select a supplier.';
    if (empty($items))        $errors['items']       = 'Add at least one line item.';
    if (!$f['purchase_date']) $errors['purchase_date'] = 'Purchase date required.';

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            $total = array_sum(array_column($items, 'total'));
            $due   = ($f['payment_type'] === 'credit' && $f['due_date']) ? $f['due_date'] : null;

            $pdo->prepare("DELETE FROM purchase_items WHERE purchase_id = ?")->execute([$id]);
            $ins = $pdo->prepare("INSERT INTO purchase_items (purchase_id, description, quantity, unit, unit_price, total, sort_order) VALUES (?,?,?,?,?,?,?)");
            foreach ($items as $it) {
                $ins->execute([$id, $it['description'], $it['quantity'], $it['unit'], $it['unit_price'], $it['total'], $it['sort_order']]);
            }

            // Keep recorded payments tied to the (possibly changed) supplier
            $pdo->prepare("UPDATE supplier_payments SET supplier_id = ? WHERE purchase_id = ?")->execute([$f['supplier_id'], $id]);

            // Cash purchases are settled in full: keep their single auto-payment equal to the total
            $pc = $pdo->prepare("SELECT COUNT(*) FROM supplier_payments WHERE purchase_id = ?");
            $pc->execute([$id]);
            $pay_count = (int)$pc->fetchColumn();
            if ($f['payment_type'] === 'cash') {
                if ($pay_count === 0) {
                    $pdo->prepare("INSERT INTO supplier_payments (purchase_id, supplier_id, amount, method, payment_date, recorded_by) VALUES (?,?,?,?,?,?)")
                        ->execute([$id, $f['supplier_id'], $total, 'cash', $f['purchase_date'], $_SESSION['admin_id'] ?? null]);
                } elseif ($pay_count === 1) {
                    $pdo->prepare("UPDATE supplier_payments SET amount = ? WHERE purchase_id = ?")->execute([$total, $id]);
                }
            }

            $ps = $pdo->prepare("SELECT IFNULL(SUM(amount),0) FROM supplier_payments WHERE purchase_id = ?");
            $ps->execute([$id]);
            $paid = (float)$ps->fetchColumn();

            if ($purchase['status'] === 'cancelled')   $status = 'cancelled';
            elseif ($paid >= $total && $total > 0)     $status = 'paid';
            elseif ($paid > 0)                         $status = 'partial';
            else                                       $status = ($f['payment_type'] === 'cash') ? 'received' : 'pending';

            $pdo->prepare(
                "UPDATE purchases SET supplier_id=?, payment_type=?, status=?, subtotal=?, total=?, amount_paid=?, purchase_date=?, due_date=?, notes=? WHERE id=?"
            )->execute([$f['supplier_id'], $f['payment_type'], $status, $total, $total, $paid, $f['purchase_date'], $due, clean($f['notes']) ?: null, $id]);

            log_activity($pdo, 'purchase_updated', "Purchase {$purchase['purchase_number']} updated.", 'purchase', $id);
            $pdo->commit();
            flash('success', "Purchase {$purchase['purchase_number']} updated.");
            redirect("/crm/purchases/view?id=$id");
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            error_log('Purchase edit: ' . $e->getMessage());
            $errors['general'] = 'Database error. Please try again.';
        }
    }
}
if (empty($js_items)) { $js_items[] = ['description' => '', 'qty' => 1, 'unit' => 'piece', 'price' => 0, 'total' => 0]; }

$page_title = 'Edit Purchase';
$active_nav = 'purchases';
require_once __DIR__ . '/../partials/header.php';
?>

<div class="max-w-3xl">
  <div class="mb-5">
    <a href="/crm/purchases/view?id=<?= $id ?>" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Back to Purchase
    </a>
  </div>

  <?php if (isset($errors['general'])): ?>
    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm"><?= $errors['general'] ?></div>
  <?php endif; ?>

  <form method="POST" x-data="purchaseForm()" class="space-y-5">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 space-y-5">
      <h2 class="font-semibold text-gray-800">Edit Purchase <span class="font-mono text-sm text-gray-400"><?= htmlspecialchars($purchase['purchase_number']) ?></span></h2>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Supplier <span class="text-red-500">*</span></label>
          <select name="supplier_id" class="w-full border <?= isset($errors['supplier_id']) ? 'border-red-400' : 'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <?php foreach ($suppliers as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $f['supplier_id'] == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['code']) ?>)</option>
            <?php endforeach; ?>
          </select>
          <?php if (isset($errors['supplier_id'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['supplier_id'] ?></p><?php endif; ?>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Payment Type</label>
          <select name="payment_type" x-model="payType" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <option value="cash">Cash (paid in full)</option>
            <option value="credit">Credit (pay later)</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Purchase Date <span class="text-red-500">*</span></label>
          <input type="date" name="purchase_date" value="<?= htmlspecialchars($f['purchase_date']) ?>"
                 class="w-full border <?= isset($errors['purchase_date']) ? 'border-red-400' : 'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
        </div>
        <div x-show="payType === 'credit'">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Due Date</label>
          <input type="date" name="due_date" value="<?= htmlspecialchars($f['due_date']) ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
        <textarea name="notes" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 resize-none"><?= htmlspecialchars($f['notes']) ?></textarea>
      </div>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="font-semibold text-gray-800">Items / Goods Received</h2>
        <?php if (isset($errors['items'])): ?><p class="text-red-500 text-xs"><?= $errors['items'] ?></p><?php endif; ?>
      </div>

      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">Description</th>
              <th class="text-right px-3 py-2.5 text-xs font-semibold text-gray-500 uppercase w-24">Qty</th>
              <th class="text-left px-3 py-2.5 text-xs font-semibold text-gray-500 uppercase w-24">Unit</th>
              <th class="text-right px-3 py-2.5 text-xs font-semibold text-gray-500 uppercase w-32">Unit Price</th>
              <th class="text-right px-3 py-2.5 text-xs font-semibold text-gray-500 uppercase w-32">Total</th>
              <th class="w-8 px-2"></th>
            </tr>
          </thead>
          <tbody>
            <template x-for="(item, idx) in items" :key="idx">
              <tr class="border-b border-gray-50">
                <td class="px-4 py-2"><input type="text" :name="'item_desc['+idx+']'" x-model="item.description" class="w-full border border-gray-200 rounded px-2 py-1.5 text-sm focus:outline-none focus:border-amber-400" placeholder="Description"></td>
                <td class="px-3 py-2"><input type="number" :name="'item_qty['+idx+']'" x-model.number="item.qty" @input="calcRow(idx)" min="0" step="0.001" class="w-full border border-gray-200 rounded px-2 py-1.5 text-sm text-right focus:outline-none focus:border-amber-400"></td>
                <td class="px-3 py-2"><input type="text" :name="'item_unit['+idx+']'" x-model="item.unit" class="w-full border border-gray-200 rounded px-2 py-1.5 text-sm focus:outline-none focus:border-amber-400" placeholder="piece"></td>
                <td class="px-3 py-2"><input type="number" :name="'item_price['+idx+']'" x-model.number="item.price" @input="calcRow(idx)" min="0" step="any" class="w-full border border-gray-200 rounded px-2 py-1.5 text-sm text-right focus:outline-none focus:border-amber-400"></td>
                <td class="px-3 py-2 text-right"><span class="text-sm font-medium text-gray-700" x-text="item.total.toLocaleString()"></span></td>
                <td class="px-2 py-2">
                  <button type="button" @click="removeItem(idx)" class="text-gray-300 hover:text-red-400 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                  </button>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <div class="px-6 py-3 border-t border-gray-100 flex items-center justify-between">
        <button type="button" @click="addItem()" class="text-sm text-amber-600 hover:text-amber-700 font-medium flex items-center gap-1">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Add Line
        </button>
        <div class="text-right">
          <p class="text-xs text-gray-400">Total</p>
          <p class="text-xl font-bold text-gray-900">UGX <span x-text="grandTotal().toLocaleString()"></span></p>
        </div>
      </div>
    </div>

    <div class="flex items-center gap-3">
      <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors">Save Changes</button>
      <a href="/crm/purchases/view?id=<?= $id ?>" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
    </div>
  </form>
</div>

<script>
function purchaseForm() {
    return {
        payType: <?= json_encode($f['payment_type']) ?>,
        items: <?= json_encode($js_items, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
        addItem() { this.items.push({ description: '', qty: 1, unit: 'piece', price: 0, total: 0 }); },
        removeItem(idx) { if (this.items.length > 1) this.items.splice(idx, 1); },
        calcRow(idx) { this.items[idx].total = this.items[idx].qty * this.items[idx].price; },
        grandTotal() { return this.items.reduce((s, i) => s + i.total, 0); },
    };
}
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
