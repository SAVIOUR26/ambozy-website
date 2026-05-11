<?php
$page_title = 'Payroll';
$active_nav = 'payroll';
$header_actions = '<a href="/crm/payroll/generate"
   class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
  Run Payroll
</a>';
require_once __DIR__ . '/../partials/header.php';

$payrolls = [];
$total    = 0;

if ($pdo) { try {
    $total = (int)$pdo->query("SELECT COUNT(*) FROM payroll")->fetchColumn();
    $payrolls = $pdo->query(
        "SELECT p.*,
                (SELECT COUNT(*) FROM payroll_items pi WHERE pi.payroll_id = p.id) AS emp_count
         FROM payroll p
         ORDER BY p.pay_year DESC, p.pay_month DESC
         LIMIT 24"
    )->fetchAll();
} catch (PDOException $e) { error_log('Payroll index: ' . $e->getMessage()); } }

$month_names = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
?>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="px-5 py-4 border-b border-gray-100">
    <p class="text-sm text-gray-500"><?= number_format($total) ?> payroll run<?= $total !== 1 ? 's' : '' ?></p>
  </div>

  <?php if (empty($payrolls)): ?>
    <div class="text-center py-16">
      <p class="text-4xl mb-3">💵</p>
      <p class="text-gray-500 font-medium">No payroll runs yet</p>
      <a href="/crm/payroll/generate" class="mt-3 inline-block text-amber-600 hover:text-amber-700 text-sm font-medium">→ Run this month's payroll</a>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Period</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Staff</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Gross</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Total PAYE</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">NSSF (Total)</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Net Pay</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($payrolls as $pr): ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3.5">
                <a href="/crm/payroll/view?id=<?= $pr['id'] ?>" class="font-semibold text-gray-900 hover:text-amber-600 transition-colors">
                  <?= $month_names[$pr['pay_month']] ?> <?= $pr['pay_year'] ?>
                </a>
                <p class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($pr['ref']) ?></p>
              </td>
              <td class="px-4 py-3.5 text-right text-gray-600 hidden md:table-cell"><?= $pr['emp_count'] ?></td>
              <td class="px-4 py-3.5 text-right font-medium text-gray-900"><?= fmt_money($pr['total_gross']) ?></td>
              <td class="px-4 py-3.5 text-right text-red-600 hidden lg:table-cell"><?= fmt_money($pr['total_paye']) ?></td>
              <td class="px-4 py-3.5 text-right text-orange-600 hidden lg:table-cell">
                <?= fmt_money($pr['total_nssf_employee'] + $pr['total_nssf_employer']) ?>
              </td>
              <td class="px-4 py-3.5 text-right font-semibold text-green-600"><?= fmt_money($pr['total_net']) ?></td>
              <td class="px-4 py-3.5">
                <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium <?= payroll_badge($pr['status']) ?>">
                  <?= ucfirst($pr['status']) ?>
                </span>
              </td>
              <td class="px-4 py-3.5 text-right">
                <a href="/crm/payroll/view?id=<?= $pr['id'] ?>" class="text-gray-400 hover:text-amber-600 transition-colors" title="View">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                  </svg>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
