<?php
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /crm/purchases/'); exit; }

$purchase = null;
$items    = [];
$payments = [];

if ($pdo) { try {
    $stmt = $pdo->prepare(
        "SELECT p.*, s.name AS supplier_name, s.code AS supplier_code, s.id AS supplier_id
         FROM purchases p JOIN suppliers s ON p.supplier_id = s.id WHERE p.id = ?"
    );
    $stmt->execute([$id]);
    $purchase = $stmt->fetch();
    if (!$purchase) { header('Location: /crm/purchases/'); exit; }

    $items    = $pdo->prepare("SELECT * FROM purchase_items WHERE purchase_id = ? ORDER BY sort_order, id")->execute([$id]) ? $pdo->prepare("SELECT * FROM purchase_items WHERE purchase_id = ? ORDER BY sort_order, id") : null;
    $items_stmt = $pdo->prepare("SELECT * FROM purchase_items WHERE purchase_id = ? ORDER BY sort_order, id");
    $items_stmt->execute([$id]);
    $items = $items_stmt->fetchAll();

    $pay_stmt = $pdo->prepare("SELECT * FROM supplier_payments WHERE purchase_id = ? ORDER BY payment_date DESC");
    $pay_stmt->execute([$id]);
    $payments = $pay_stmt->fetchAll();
} catch (PDOException $e) { error_log('Purchase view: ' . $e->getMessage()); } }

// Handle record payment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_payment']) && $pdo && $purchase) {
    $amount = (float)str_replace(',', '', $_POST['pay_amount'] ?? '0');
    $method = clean($_POST['pay_method'] ?? 'cash');
    $ref    = clean($_POST['pay_ref'] ?? '');
    $pdate  = clean($_POST['pay_date'] ?? date('Y-m-d'));
    $notes  = clean($_POST['pay_notes'] ?? '');

    if ($amount > 0) {
        try {
            $pdo->beginTransaction();
            $ins = $pdo->prepare("INSERT INTO supplier_payments (purchase_id, supplier_id, amount, method, reference, notes, payment_date, recorded_by) VALUES (?,?,?,?,?,?,?,?)");
            $ins->execute([$id, $purchase['supplier_id'], $amount, $method, $ref ?: null, $notes ?: null, $pdate, $_SESSION['admin_id'] ?? null]);

            $new_paid  = $purchase['amount_paid'] + $amount;
            $new_status = ($new_paid >= $purchase['total']) ? 'paid' : 'partial';
            $pdo->prepare("UPDATE purchases SET amount_paid=?, status=? WHERE id=?")->execute([$new_paid, $new_status, $id]);
            log_activity($pdo, 'supplier_payment', "Payment of " . fmt_money($amount) . " recorded for purchase {$purchase['purchase_number']}.", 'purchase', $id);
            $pdo->commit();
            flash('success', 'Payment recorded successfully.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('error', 'Failed to record payment.');
        }
        redirect("/crm/purchases/view?id=$id");
    }
}

$page_title = $purchase['purchase_number'] ?? 'Purchase';
$active_nav = 'purchases';
require_once __DIR__ . '/../partials/header.php';

$balance = ($purchase['total'] ?? 0) - ($purchase['amount_paid'] ?? 0);
?>

<div class="mb-5">
  <a href="/crm/purchases/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Back to Purchases
  </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-5">
  <!-- Purchase info -->
  <div class="xl:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-6">
    <div class="flex items-start justify-between flex-wrap gap-3 mb-4">
      <div>
        <h2 class="text-xl font-bold text-gray-900 font-mono"><?= htmlspecialchars($purchase['purchase_number']) ?></h2>
        <p class="text-sm text-gray-500 mt-1">
          Supplier: <a href="/crm/suppliers/view?id=<?= $purchase['supplier_id'] ?>" class="text-amber-600 hover:text-amber-700 font-medium"><?= htmlspecialchars($purchase['supplier_name']) ?></a>
        </p>
      </div>
      <span class="inline-block text-sm px-3 py-1 rounded-full font-medium <?= purchase_badge($purchase['status']) ?>">
        <?= ucfirst($purchase['status']) ?>
      </span>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm mb-5">
      <div><p class="text-gray-400 text-xs">Date</p><p class="font-medium text-gray-900"><?= date('d M Y', strtotime($purchase['purchase_date'])) ?></p></div>
      <div><p class="text-gray-400 text-xs">Type</p>
        <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium <?= $purchase['payment_type']==='credit' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700' ?>"><?= ucfirst($purchase['payment_type']) ?></span>
      </div>
      <?php if ($purchase['due_date']): ?>
        <div><p class="text-gray-400 text-xs">Due</p><p class="font-medium <?= strtotime($purchase['due_date']) < time() && $balance > 0 ? 'text-red-600' : 'text-gray-900' ?>"><?= date('d M Y', strtotime($purchase['due_date'])) ?></p></div>
      <?php endif; ?>
      <div><p class="text-gray-400 text-xs">Total</p><p class="font-bold text-gray-900"><?= fmt_money($purchase['total']) ?></p></div>
    </div>

    <!-- Items table -->
    <div class="border border-gray-100 rounded-xl overflow-hidden">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">Description</th>
            <th class="text-right px-3 py-2.5 text-xs font-semibold text-gray-500 uppercase">Qty</th>
            <th class="text-left px-3 py-2.5 text-xs font-semibold text-gray-500 uppercase">Unit</th>
            <th class="text-right px-3 py-2.5 text-xs font-semibold text-gray-500 uppercase">Price</th>
            <th class="text-right px-4 py-2.5 text-xs font-semibold text-gray-500 uppercase">Total</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($items as $item): ?>
            <tr>
              <td class="px-4 py-2.5 text-gray-700"><?= htmlspecialchars($item['description']) ?></td>
              <td class="px-3 py-2.5 text-right text-gray-600"><?= rtrim(rtrim(number_format($item['quantity'],3),'0'),'.') ?></td>
              <td class="px-3 py-2.5 text-gray-500"><?= htmlspecialchars($item['unit']) ?></td>
              <td class="px-3 py-2.5 text-right text-gray-600"><?= number_format($item['unit_price'],0) ?></td>
              <td class="px-4 py-2.5 text-right font-medium text-gray-900"><?= number_format($item['total'],0) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot class="bg-gray-50 border-t border-gray-200">
          <tr>
            <td colspan="4" class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Total</td>
            <td class="px-4 py-3 text-right text-sm font-bold text-gray-900"><?= fmt_money($purchase['total']) ?></td>
          </tr>
          <tr>
            <td colspan="4" class="px-4 py-2 text-right text-sm text-gray-500">Paid</td>
            <td class="px-4 py-2 text-right text-sm font-semibold text-green-600"><?= fmt_money($purchase['amount_paid']) ?></td>
          </tr>
          <?php if ($balance > 0): ?>
            <tr>
              <td colspan="4" class="px-4 py-2 text-right text-sm font-semibold text-red-600">Balance Due</td>
              <td class="px-4 py-2 text-right text-sm font-bold text-red-600"><?= fmt_money($balance) ?></td>
            </tr>
          <?php endif; ?>
        </tfoot>
      </table>
    </div>

    <?php if ($purchase['notes']): ?>
      <p class="mt-4 text-sm text-gray-500 bg-gray-50 rounded-lg p-3"><?= nl2br(htmlspecialchars($purchase['notes'])) ?></p>
    <?php endif; ?>
  </div>

  <!-- Payments panel -->
  <div class="space-y-4">
    <!-- Payment history -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">Payments</h3>
      </div>
      <?php if (empty($payments)): ?>
        <p class="text-center text-gray-400 py-6 text-sm">No payments recorded.</p>
      <?php else: ?>
        <div class="divide-y divide-gray-50">
          <?php foreach ($payments as $pay): ?>
            <div class="px-5 py-3 flex items-center justify-between">
              <div>
                <p class="text-sm font-medium text-gray-900"><?= fmt_money($pay['amount']) ?></p>
                <p class="text-xs text-gray-400"><?= date('d M Y', strtotime($pay['payment_date'])) ?> · <?= ucfirst(str_replace('_', ' ', $pay['method'])) ?></p>
              </div>
              <?php if ($pay['reference']): ?>
                <span class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($pay['reference']) ?></span>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Record payment form (only if balance remaining) -->
    <?php if ($balance > 0): ?>
      <div class="bg-white rounded-xl border border-amber-200 shadow-sm overflow-hidden" x-data="{ open: false }">
        <button type="button" @click="open = !open"
                class="w-full px-5 py-4 text-left flex items-center justify-between">
          <span class="font-semibold text-amber-700 text-sm">Record Payment</span>
          <svg class="w-4 h-4 text-amber-500 transition-transform" :class="open?'rotate-180':''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div x-show="open" x-cloak class="px-5 pb-5 border-t border-amber-100">
          <form method="POST" class="space-y-3 pt-4">
            <input type="hidden" name="record_payment" value="1">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Amount (UGX)</label>
              <input type="number" name="pay_amount" value="<?= $balance ?>" min="1" step="100" required
                     class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Method</label>
              <select name="pay_method" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
                <option value="cash">Cash</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="mobile_money">Mobile Money</option>
                <option value="cheque">Cheque</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
              <input type="date" name="pay_date" value="<?= date('Y-m-d') ?>" required
                     class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Reference (optional)</label>
              <input type="text" name="pay_ref" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
            </div>
            <button type="submit" class="w-full bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors">
              Record Payment
            </button>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
