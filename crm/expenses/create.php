<?php
$page_title = 'Record Expense';
$active_nav = 'expenses';
require_once __DIR__ . '/../partials/header.php';

$errors = [];
$f = [
    'category_id'  => '',
    'description'  => '',
    'amount'       => '',
    'method'       => 'cash',
    'reference'    => '',
    'vendor'       => '',
    'expense_date' => date('Y-m-d'),
    'notes'        => '',
];

$categories = [];
if ($pdo) { try {
    $categories = $pdo->query("SELECT * FROM expense_categories WHERE is_active=1 ORDER BY name")->fetchAll();
} catch (PDOException $e) {} }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    foreach ($f as $k => $_) { $f[$k] = clean($_POST[$k] ?? ''); }
    $amount = (float) str_replace(',', '', $f['amount']);

    if (!$f['description']) $errors['description'] = 'Description is required.';
    if ($amount <= 0)        $errors['amount']      = 'Enter a valid amount.';
    if (!$f['expense_date']) $errors['expense_date']= 'Date is required.';

    if (empty($errors)) {
        $ref = next_doc_number($pdo, 'EXP');
        $stmt = $pdo->prepare(
            "INSERT INTO expenses (ref, category_id, description, amount, method, reference, vendor, expense_date, notes, recorded_by)
             VALUES (?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $ref,
            ($f['category_id'] !== '') ? (int)$f['category_id'] : null,
            $f['description'],
            $amount,
            $f['method'],
            $f['reference'] ?: null,
            $f['vendor']    ?: null,
            $f['expense_date'],
            $f['notes']     ?: null,
            $_SESSION['admin_id'] ?? null,
        ]);
        log_activity($pdo, 'expense_recorded', "Expense {$ref} — {$f['description']} (" . fmt_money($amount) . ") recorded.", 'expense', (int)$pdo->lastInsertId());
        flash('success', "Expense {$ref} recorded successfully.");
        redirect('/crm/expenses/');
    }
}
?>

<div class="max-w-xl">
  <div class="mb-5">
    <a href="/crm/expenses/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Back to Expenses
    </a>
  </div>

  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800">Expense Details</h2>
      <p class="text-sm text-gray-400 mt-0.5">A reference number will be auto-generated (e.g. EXP-2026-0001)</p>
    </div>

    <form method="POST" class="px-6 py-5 space-y-5">

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Category</label>
          <select name="category_id" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <option value="">— Uncategorised —</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c['id'] ?>" <?= $f['category_id'] == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Date <span class="text-red-500">*</span></label>
          <input type="date" name="expense_date" value="<?= htmlspecialchars($f['expense_date']) ?>"
                 class="w-full border <?= isset($errors['expense_date']) ? 'border-red-400' : 'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
          <?php if (isset($errors['expense_date'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['expense_date'] ?></p><?php endif; ?>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Description <span class="text-red-500">*</span></label>
        <input type="text" name="description" value="<?= htmlspecialchars($f['description']) ?>" placeholder="What was this expense for?"
               class="w-full border <?= isset($errors['description']) ? 'border-red-400' : 'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        <?php if (isset($errors['description'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['description'] ?></p><?php endif; ?>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount (UGX) <span class="text-red-500">*</span></label>
          <input type="number" name="amount" value="<?= htmlspecialchars($f['amount']) ?>" min="0" step="100"
                 class="w-full border <?= isset($errors['amount']) ? 'border-red-400' : 'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
          <?php if (isset($errors['amount'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['amount'] ?></p><?php endif; ?>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Payment Method</label>
          <select name="method" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <?php foreach (['cash'=>'Cash','bank_transfer'=>'Bank Transfer','mobile_money'=>'Mobile Money','card'=>'Card','cheque'=>'Cheque'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= $f['method']===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Vendor / Paid To</label>
          <input type="text" name="vendor" value="<?= htmlspecialchars($f['vendor']) ?>" placeholder="Shop / company name"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Receipt / Reference</label>
          <input type="text" name="reference" value="<?= htmlspecialchars($f['reference']) ?>" placeholder="Receipt no., transaction ID…"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
        <textarea name="notes" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 resize-none"><?= htmlspecialchars($f['notes']) ?></textarea>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors">Save Expense</button>
        <a href="/crm/expenses/" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
