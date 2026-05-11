<?php
$page_title = 'Add Statutory Obligation';
$active_nav = 'statutory';
require_once __DIR__ . '/../partials/header.php';

$errors = [];
$f = [
    'type'         => 'paye',
    'period_month' => date('n'),
    'period_year'  => date('Y'),
    'amount_due'   => '',
    'amount_paid'  => '0',
    'due_date'     => '',
    'paid_date'    => '',
    'reference'    => '',
    'notes'        => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    foreach ($f as $k => $_) { $f[$k] = clean($_POST[$k] ?? ''); }
    $amount_due  = (float) str_replace(',', '', $f['amount_due']);
    $amount_paid = (float) str_replace(',', '', $f['amount_paid']);

    if ($amount_due <= 0) $errors['amount_due'] = 'Enter a valid amount due.';

    if (empty($errors)) {
        $status = 'pending';
        if ($amount_paid >= $amount_due) $status = 'paid';
        elseif ($amount_paid > 0)        $status = 'partial';
        elseif ($f['due_date'] && strtotime($f['due_date']) < time()) $status = 'overdue';

        $stmt = $pdo->prepare(
            "INSERT INTO statutory_obligations (type, period_month, period_year, amount_due, amount_paid, due_date, paid_date, reference, status, notes, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $f['type'], (int)$f['period_month'], (int)$f['period_year'],
            $amount_due, $amount_paid,
            $f['due_date']  ?: null,
            $f['paid_date'] ?: null,
            $f['reference'] ?: null,
            $status,
            $f['notes']     ?: null,
            $_SESSION['admin_id'] ?? null,
        ]);
        log_activity($pdo, 'statutory_added', "Statutory obligation ({$f['type']}) for {$f['period_month']}/{$f['period_year']} added.", 'statutory', (int)$pdo->lastInsertId());
        flash('success', 'Statutory obligation added.');
        redirect('/crm/statutory/');
    }
}
$month_names = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
$type_labels = ['paye'=>'PAYE (Income Tax)','vat'=>'VAT','withholding_tax'=>'Withholding Tax (WHT)','nssf'=>'NSSF','local_service_tax'=>'Local Service Tax (LST)','other'=>'Other'];
?>

<div class="max-w-xl">
  <div class="mb-5">
    <a href="/crm/statutory/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Back to Statutory Obligations
    </a>
  </div>

  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800">Add Statutory Obligation</h2>
      <p class="text-xs text-gray-400 mt-0.5">PAYE and NSSF obligations are auto-created when payroll is run. Use this to add VAT, WHT, or corrections.</p>
    </div>

    <form method="POST" class="px-6 py-5 space-y-5">
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label>
          <select name="type" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <?php foreach ($type_labels as $v=>$l): ?>
              <option value="<?= $v ?>" <?= $f['type']===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Month</label>
          <select name="period_month" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $m ?>" <?= (int)$f['period_month']===$m?'selected':'' ?>><?= $month_names[$m] ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Year</label>
          <select name="period_year" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <?php for ($y = date('Y'); $y >= date('Y')-3; $y--): ?>
              <option value="<?= $y ?>" <?= (int)$f['period_year']===$y?'selected':'' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount Due (UGX) <span class="text-red-500">*</span></label>
          <input type="number" name="amount_due" value="<?= htmlspecialchars($f['amount_due']) ?>" min="0" step="100"
                 class="w-full border <?= isset($errors['amount_due']) ? 'border-red-400' : 'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
          <?php if (isset($errors['amount_due'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['amount_due'] ?></p><?php endif; ?>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount Paid (UGX)</label>
          <input type="number" name="amount_paid" value="<?= htmlspecialchars($f['amount_paid']) ?>" min="0" step="100"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Due Date</label>
          <input type="date" name="due_date" value="<?= htmlspecialchars($f['due_date']) ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Date Paid (if applicable)</label>
          <input type="date" name="paid_date" value="<?= htmlspecialchars($f['paid_date']) ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">PRN / Payment Reference</label>
        <input type="text" name="reference" value="<?= htmlspecialchars($f['reference']) ?>" placeholder="URA PRN or transaction ID"
               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
        <textarea name="notes" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 resize-none"><?= htmlspecialchars($f['notes']) ?></textarea>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors">Save Obligation</button>
        <a href="/crm/statutory/" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
