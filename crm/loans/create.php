<?php
$page_title = 'Add Loan';
$active_nav = 'loans';
require_once __DIR__ . '/../partials/header.php';

$errors = [];
$f = [
    'lender'            => '',
    'loan_type'         => 'bank_loan',
    'principal'         => '',
    'interest_rate'     => '0',
    'disbursement_date' => date('Y-m-d'),
    'due_date'          => '',
    'installment'       => '0',
    'notes'             => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    foreach ($f as $k => $_) { $f[$k] = clean($_POST[$k] ?? ''); }
    $principal = (float) str_replace(',', '', $f['principal']);
    $rate      = (float) $f['interest_rate'];
    $install   = (float) str_replace(',', '', $f['installment']);

    if (!$f['lender'])          $errors['lender']    = 'Lender name is required.';
    if ($principal <= 0)        $errors['principal'] = 'Enter a valid loan amount.';
    if (!$f['disbursement_date']) $errors['disbursement_date'] = 'Disbursement date is required.';

    if (empty($errors)) {
        $ref = next_doc_number($pdo, 'LN');
        $stmt = $pdo->prepare(
            "INSERT INTO loans (ref, lender, loan_type, principal, interest_rate, disbursement_date, due_date, installment, notes, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $ref, $f['lender'], $f['loan_type'], $principal, $rate,
            $f['disbursement_date'],
            $f['due_date'] ?: null,
            $install,
            $f['notes'] ?: null,
            $_SESSION['admin_id'] ?? null,
        ]);
        $id = (int)$pdo->lastInsertId();
        log_activity($pdo, 'loan_created', "Loan {$ref} from {$f['lender']} — " . fmt_money($principal) . " added.", 'loan', $id);
        flash('success', "Loan {$ref} recorded successfully.");
        redirect("/crm/loans/view?id=$id");
    }
}
?>

<div class="max-w-xl">
  <div class="mb-5">
    <a href="/crm/loans/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Back to Loans
    </a>
  </div>

  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800">Loan / Credit Facility</h2>
    </div>

    <form method="POST" class="px-6 py-5 space-y-5">

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Lender <span class="text-red-500">*</span></label>
          <input type="text" name="lender" value="<?= htmlspecialchars($f['lender']) ?>" placeholder="Bank / person name"
                 class="w-full border <?= isset($errors['lender']) ? 'border-red-400' : 'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
          <?php if (isset($errors['lender'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['lender'] ?></p><?php endif; ?>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Loan Type</label>
          <select name="loan_type" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <?php foreach (['bank_loan'=>'Bank Loan','overdraft'=>'Bank Overdraft','personal'=>'Personal Loan','equipment'=>'Equipment Finance','other'=>'Other'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= $f['loan_type']===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Principal Amount (UGX) <span class="text-red-500">*</span></label>
          <input type="number" name="principal" value="<?= htmlspecialchars($f['principal']) ?>" min="0" step="1000"
                 class="w-full border <?= isset($errors['principal']) ? 'border-red-400' : 'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
          <?php if (isset($errors['principal'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['principal'] ?></p><?php endif; ?>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Interest Rate (% p.a.)</label>
          <input type="number" name="interest_rate" value="<?= htmlspecialchars($f['interest_rate']) ?>" min="0" max="100" step="0.1"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Disbursement Date <span class="text-red-500">*</span></label>
          <input type="date" name="disbursement_date" value="<?= htmlspecialchars($f['disbursement_date']) ?>"
                 class="w-full border <?= isset($errors['disbursement_date']) ? 'border-red-400' : 'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Final Due Date</label>
          <input type="date" name="due_date" value="<?= htmlspecialchars($f['due_date']) ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Monthly Installment (UGX)</label>
        <input type="number" name="installment" value="<?= htmlspecialchars($f['installment']) ?>" min="0" step="1000"
               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
        <p class="text-xs text-gray-400 mt-1">For reference — used on the dashboard reminders</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
        <textarea name="notes" rows="3" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 resize-none"><?= htmlspecialchars($f['notes']) ?></textarea>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors">Save Loan</button>
        <a href="/crm/loans/" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
