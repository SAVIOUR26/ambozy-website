<?php
require_once __DIR__ . '/../partials/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { redirect('/crm/loans/'); }

$loan = null;
if ($pdo) {
    $s = $pdo->prepare("SELECT * FROM loans WHERE id = ?");
    $s->execute([$id]);
    $loan = $s->fetch();
}
if (!$loan) { flash('error', 'Loan not found.'); redirect('/crm/loans/'); }

$errors = [];
$f = [
    'lender'            => unclean($loan['lender']),
    'loan_type'         => $loan['loan_type'],
    'principal'         => $loan['principal'] + 0,
    'interest_rate'     => $loan['interest_rate'] + 0,
    'disbursement_date' => $loan['disbursement_date'],
    'due_date'          => $loan['due_date'] ?? '',
    'installment'       => $loan['installment'] + 0,
    'status'            => $loan['status'],
    'notes'             => unclean($loan['notes']),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    foreach ($f as $k => $_) { $f[$k] = trim($_POST[$k] ?? ''); }
    $principal = (float) str_replace(',', '', $f['principal']);
    $rate      = (float) $f['interest_rate'];
    $install   = (float) str_replace(',', '', $f['installment']);

    if (!$f['lender'])            $errors['lender']    = 'Lender name is required.';
    if ($principal <= 0)          $errors['principal'] = 'Enter a valid loan amount.';
    if (!$f['disbursement_date']) $errors['disbursement_date'] = 'Disbursement date is required.';
    if (!in_array($f['loan_type'], ['bank_loan','overdraft','personal','equipment','other'], true)) $f['loan_type'] = 'other';
    if (!in_array($f['status'], ['active','fully_paid','defaulted','written_off'], true)) $f['status'] = $loan['status'];

    if (empty($errors)) {
        $pdo->prepare(
            "UPDATE loans SET lender=?, loan_type=?, principal=?, interest_rate=?, disbursement_date=?, due_date=?, installment=?, status=?, notes=? WHERE id=?"
        )->execute([
            clean($f['lender']), $f['loan_type'], $principal, $rate,
            $f['disbursement_date'], $f['due_date'] ?: null, $install,
            $f['status'], clean($f['notes']) ?: null, $id,
        ]);
        log_activity($pdo, 'loan_updated', "Loan {$loan['ref']} details updated.", 'loan', $id);
        flash('success', "Loan {$loan['ref']} updated.");
        redirect("/crm/loans/view?id=$id");
    }
}

$page_title = 'Edit Loan';
$active_nav = 'loans';
require_once __DIR__ . '/../partials/header.php';
?>

<div class="max-w-xl">
  <div class="mb-5">
    <a href="/crm/loans/view?id=<?= $id ?>" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Back to Loan
    </a>
  </div>

  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800">Edit Loan <span class="font-mono text-sm text-gray-400"><?= htmlspecialchars($loan['ref']) ?></span></h2>
    </div>

    <form method="POST" class="px-6 py-5 space-y-5">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Lender <span class="text-red-500">*</span></label>
          <input type="text" name="lender" value="<?= htmlspecialchars($f['lender']) ?>"
                 class="w-full border <?= isset($errors['lender']) ? 'border-red-400' : 'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
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
          <input type="number" name="principal" value="<?= htmlspecialchars((string)$f['principal']) ?>" min="0" step="any"
                 class="w-full border <?= isset($errors['principal']) ? 'border-red-400' : 'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
          <?php if (isset($errors['principal'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['principal'] ?></p><?php endif; ?>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Interest Rate (% p.a.)</label>
          <input type="number" name="interest_rate" value="<?= htmlspecialchars((string)$f['interest_rate']) ?>" min="0" max="100" step="0.1"
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

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Monthly Installment (UGX)</label>
          <input type="number" name="installment" value="<?= htmlspecialchars((string)$f['installment']) ?>" min="0" step="any"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
          <select name="status" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <?php foreach (['active'=>'Active','fully_paid'=>'Fully Paid','defaulted'=>'Defaulted','written_off'=>'Written Off'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= $f['status']===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
        <textarea name="notes" rows="3" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 resize-none"><?= htmlspecialchars($f['notes']) ?></textarea>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors">Save Changes</button>
        <a href="/crm/loans/view?id=<?= $id ?>" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
