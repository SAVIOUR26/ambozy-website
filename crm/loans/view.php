<?php
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /crm/loans/'); exit; }

$loan       = null;
$repayments = [];

if ($pdo) { try {
    $stmt = $pdo->prepare("SELECT * FROM loans WHERE id = ?");
    $stmt->execute([$id]);
    $loan = $stmt->fetch();
    if (!$loan) { header('Location: /crm/loans/'); exit; }

    $rep_stmt = $pdo->prepare("SELECT * FROM loan_repayments WHERE loan_id = ? ORDER BY payment_date DESC");
    $rep_stmt->execute([$id]);
    $repayments = $rep_stmt->fetchAll();
} catch (PDOException $e) { error_log('Loan view: ' . $e->getMessage()); } }

// Handle repayment POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['record_repayment']) && $pdo && $loan) {
    $amount    = (float)str_replace(',', '', $_POST['rep_amount'] ?? '0');
    $principal = (float)str_replace(',', '', $_POST['rep_principal'] ?? '0');
    $interest  = (float)str_replace(',', '', $_POST['rep_interest'] ?? '0');
    $method    = clean($_POST['rep_method'] ?? 'bank_transfer');
    $ref       = clean($_POST['rep_ref'] ?? '');
    $pdate     = clean($_POST['rep_date'] ?? date('Y-m-d'));

    if ($amount > 0) {
        try {
            $pdo->beginTransaction();
            $ins = $pdo->prepare("INSERT INTO loan_repayments (loan_id, amount, principal, interest, method, reference, payment_date, recorded_by) VALUES (?,?,?,?,?,?,?,?)");
            $ins->execute([$id, $amount, $principal, $interest, $method, $ref ?: null, $pdate, $_SESSION['admin_id'] ?? null]);

            $new_repaid = $loan['amount_repaid'] + $amount;
            $new_status = ($new_repaid >= $loan['principal']) ? 'fully_paid' : $loan['status'];
            $pdo->prepare("UPDATE loans SET amount_repaid=?, status=? WHERE id=?")->execute([$new_repaid, $new_status, $id]);
            log_activity($pdo, 'loan_repayment', "Repayment of " . fmt_money($amount) . " on loan {$loan['ref']}.", 'loan', $id);
            $pdo->commit();
            flash('success', 'Repayment recorded.');
        } catch (Throwable $e) {
            $pdo->rollBack();
            flash('error', 'Failed to record repayment.');
        }
        redirect("/crm/loans/view?id=$id");
    }
}

$page_title = $loan['ref'] ?? 'Loan';
$active_nav = 'loans';
require_once __DIR__ . '/../partials/header.php';

$outstanding = $loan['principal'] - $loan['amount_repaid'];
$progress    = $loan['principal'] > 0 ? min(100, round(($loan['amount_repaid'] / $loan['principal']) * 100)) : 0;
?>

<div class="mb-5">
  <a href="/crm/loans/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Back to Loans
  </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-5">
  <!-- Loan detail card -->
  <div class="xl:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-6">
    <div class="flex items-start justify-between flex-wrap gap-3 mb-5">
      <div>
        <h2 class="text-xl font-bold text-gray-900"><?= htmlspecialchars($loan['lender']) ?></h2>
        <p class="text-sm text-gray-400 font-mono mt-0.5"><?= htmlspecialchars($loan['ref']) ?> · <?= ucfirst(str_replace('_',' ',$loan['loan_type'])) ?></p>
      </div>
      <span class="inline-block text-sm px-3 py-1 rounded-full font-medium <?= loan_badge($loan['status']) ?>">
        <?= ucfirst(str_replace('_',' ',$loan['status'])) ?>
      </span>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm mb-6">
      <div><p class="text-gray-400 text-xs">Principal</p><p class="font-bold text-gray-900"><?= fmt_money($loan['principal']) ?></p></div>
      <div><p class="text-gray-400 text-xs">Interest Rate</p><p class="font-medium text-gray-900"><?= $loan['interest_rate'] ?>% p.a.</p></div>
      <div><p class="text-gray-400 text-xs">Monthly Payment</p><p class="font-medium text-gray-900"><?= $loan['installment'] > 0 ? fmt_money($loan['installment']) : '—' ?></p></div>
      <div><p class="text-gray-400 text-xs">Due Date</p><p class="font-medium <?= $loan['due_date'] && strtotime($loan['due_date']) < time() && $outstanding > 0 ? 'text-red-600' : 'text-gray-900' ?>"><?= $loan['due_date'] ? date('d M Y', strtotime($loan['due_date'])) : '—' ?></p></div>
    </div>

    <!-- Repayment progress bar -->
    <div class="mb-2 flex items-center justify-between text-xs text-gray-500">
      <span>Repayment Progress</span>
      <span><?= $progress ?>%</span>
    </div>
    <div class="h-3 bg-gray-100 rounded-full overflow-hidden mb-4">
      <div class="h-3 bg-gradient-to-r from-amber-400 to-green-500 rounded-full transition-all"
           style="width: <?= $progress ?>%"></div>
    </div>
    <div class="flex items-center justify-between text-sm">
      <span class="text-green-600 font-semibold">Repaid: <?= fmt_money($loan['amount_repaid']) ?></span>
      <span class="text-red-600 font-semibold">Outstanding: <?= fmt_money(max(0, $outstanding)) ?></span>
    </div>

    <?php if ($loan['notes']): ?>
      <p class="mt-4 text-sm text-gray-500 bg-gray-50 rounded-lg p-3"><?= nl2br(htmlspecialchars($loan['notes'])) ?></p>
    <?php endif; ?>
  </div>

  <!-- Right panel -->
  <div class="space-y-4">
    <!-- Quick stats -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-3">
      <div class="flex justify-between text-sm"><span class="text-gray-500">Disbursed</span><span class="font-medium"><?= date('d M Y', strtotime($loan['disbursement_date'])) ?></span></div>
      <div class="flex justify-between text-sm"><span class="text-gray-500">Total Repayments</span><span class="font-medium"><?= count($repayments) ?></span></div>
    </div>

    <!-- Record repayment -->
    <?php if ($outstanding > 0 && in_array($loan['status'], ['active'])): ?>
      <div class="bg-white rounded-xl border border-amber-200 shadow-sm overflow-hidden" x-data="{ open: false }">
        <button type="button" @click="open = !open" class="w-full px-5 py-4 text-left flex items-center justify-between">
          <span class="font-semibold text-amber-700 text-sm">Record Repayment</span>
          <svg class="w-4 h-4 text-amber-500 transition-transform" :class="open?'rotate-180':''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
          </svg>
        </button>
        <div x-show="open" x-cloak class="px-5 pb-5 border-t border-amber-100">
          <form method="POST" class="space-y-3 pt-4">
            <input type="hidden" name="record_repayment" value="1">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Total Amount (UGX)</label>
              <input type="number" name="rep_amount" value="<?= $loan['installment'] > 0 ? $loan['installment'] : '' ?>" min="1" step="100" required
                     class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Principal</label>
                <input type="number" name="rep_principal" value="0" min="0" step="100"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
              </div>
              <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Interest</label>
                <input type="number" name="rep_interest" value="0" min="0" step="100"
                       class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
              </div>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Method</label>
              <select name="rep_method" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cash">Cash</option>
                <option value="mobile_money">Mobile Money</option>
                <option value="cheque">Cheque</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Date</label>
              <input type="date" name="rep_date" value="<?= date('Y-m-d') ?>" required
                     class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Reference</label>
              <input type="text" name="rep_ref" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
            </div>
            <button type="submit" class="w-full bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold py-2.5 rounded-lg transition-colors">Record Repayment</button>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Repayment history -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="px-5 py-4 border-b border-gray-100">
    <h3 class="font-semibold text-gray-800">Repayment History</h3>
  </div>
  <?php if (empty($repayments)): ?>
    <p class="text-center text-gray-400 py-8 text-sm">No repayments recorded yet.</p>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Total</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Principal</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Interest</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Method</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Reference</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($repayments as $r): ?>
            <tr class="hover:bg-gray-50">
              <td class="px-5 py-3 text-gray-700"><?= date('d M Y', strtotime($r['payment_date'])) ?></td>
              <td class="px-4 py-3 text-right font-semibold text-gray-900"><?= fmt_money($r['amount']) ?></td>
              <td class="px-4 py-3 text-right text-green-600 hidden md:table-cell"><?= fmt_money($r['principal']) ?></td>
              <td class="px-4 py-3 text-right text-orange-600 hidden md:table-cell"><?= fmt_money($r['interest']) ?></td>
              <td class="px-4 py-3 text-gray-500 hidden lg:table-cell capitalize"><?= str_replace('_',' ',$r['method']) ?></td>
              <td class="px-4 py-3 text-gray-400 font-mono text-xs hidden lg:table-cell"><?= htmlspecialchars($r['reference'] ?? '—') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
