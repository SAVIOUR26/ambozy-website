<?php
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /crm/employees/'); exit; }

$employee      = null;
$payroll_items = [];

if ($pdo) { try {
    $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    $employee = $stmt->fetch();
    if (!$employee) { header('Location: /crm/employees/'); exit; }

    $pi_stmt = $pdo->prepare(
        "SELECT pi.*, pr.ref AS payroll_ref, pr.pay_month, pr.pay_year, pr.status AS payroll_status
         FROM payroll_items pi
         JOIN payroll pr ON pi.payroll_id = pr.id
         WHERE pi.employee_id = ?
         ORDER BY pr.pay_year DESC, pr.pay_month DESC
         LIMIT 24"
    );
    $pi_stmt->execute([$id]);
    $payroll_items = $pi_stmt->fetchAll();
} catch (PDOException $e) { error_log('Employee view: ' . $e->getMessage()); } }

$page_title = $employee['name'] ?? 'Employee';
$active_nav = 'employees';
$header_actions = '<a href="/crm/payroll/"
   class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
  Run Payroll
</a>';
require_once __DIR__ . '/../partials/header.php';

$paye  = calculate_paye($employee['gross_salary']);
$nssf_emp   = round($employee['gross_salary'] * 0.05);
$nssf_emplr = round($employee['gross_salary'] * 0.10);
$net   = $employee['gross_salary'] - $paye - $nssf_emp;
?>

<div class="mb-5">
  <a href="/crm/employees/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Back to Employees
  </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-5">
  <!-- Profile -->
  <div class="xl:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-6">
    <div class="flex items-start gap-4">
      <div class="w-14 h-14 rounded-2xl bg-teal-100 text-teal-700 flex items-center justify-center text-xl font-bold shrink-0">
        <?= strtoupper(substr($employee['name'], 0, 1)) ?>
      </div>
      <div class="flex-1">
        <div class="flex items-center gap-3 flex-wrap">
          <h2 class="text-xl font-semibold text-gray-900"><?= htmlspecialchars($employee['name']) ?></h2>
          <span class="text-xs px-2 py-0.5 rounded-full font-medium <?= employee_badge($employee['status']) ?>"><?= ucfirst(str_replace('_',' ',$employee['status'])) ?></span>
          <span class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($employee['emp_number']) ?></span>
        </div>
        <?php if ($employee['position']): ?>
          <p class="text-sm text-gray-600 mt-1"><?= htmlspecialchars($employee['position']) ?><?= $employee['department'] ? ' — ' . htmlspecialchars($employee['department']) : '' ?></p>
        <?php endif; ?>
        <div class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm text-gray-500">
          <?php if ($employee['email']): ?><span>✉ <?= htmlspecialchars($employee['email']) ?></span><?php endif; ?>
          <?php if ($employee['phone']): ?><span>📞 <?= htmlspecialchars($employee['phone']) ?></span><?php endif; ?>
          <?php if ($employee['hire_date']): ?><span>📅 Hired: <?= date('d M Y', strtotime($employee['hire_date'])) ?></span><?php endif; ?>
        </div>
        <div class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-xs text-gray-400">
          <?php if ($employee['tin']): ?><span>TIN: <?= htmlspecialchars($employee['tin']) ?></span><?php endif; ?>
          <?php if ($employee['nssf_number']): ?><span>NSSF: <?= htmlspecialchars($employee['nssf_number']) ?></span><?php endif; ?>
        </div>
        <?php if ($employee['notes']): ?>
          <p class="text-sm text-gray-500 mt-3 bg-gray-50 rounded-lg p-3"><?= nl2br(htmlspecialchars($employee['notes'])) ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Salary breakdown -->
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
    <h3 class="font-semibold text-gray-800 mb-4">Monthly Salary Breakdown</h3>
    <div class="space-y-3 text-sm">
      <div class="flex justify-between">
        <span class="text-gray-500">Gross Salary</span>
        <span class="font-semibold text-gray-900"><?= fmt_money($employee['gross_salary']) ?></span>
      </div>
      <div class="flex justify-between text-red-500">
        <span>PAYE (deducted)</span>
        <span>− <?= fmt_money($paye) ?></span>
      </div>
      <div class="flex justify-between text-orange-500">
        <span>NSSF Employee (5%)</span>
        <span>− <?= fmt_money($nssf_emp) ?></span>
      </div>
      <div class="border-t border-gray-100 pt-3 flex justify-between">
        <span class="font-semibold text-gray-700">Net Pay</span>
        <span class="font-bold text-green-600"><?= fmt_money($net) ?></span>
      </div>
      <div class="border-t border-gray-100 pt-3 flex justify-between text-xs text-gray-400">
        <span>Employer NSSF (10%)</span>
        <span><?= fmt_money($nssf_emplr) ?></span>
      </div>
    </div>
  </div>
</div>

<!-- Payroll history -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
    <h3 class="font-semibold text-gray-800">Payroll History</h3>
    <a href="/crm/payroll/" class="text-xs text-amber-600 hover:text-amber-700 font-medium">All Payrolls →</a>
  </div>
  <?php if (empty($payroll_items)): ?>
    <p class="text-center text-gray-400 py-8 text-sm">No payroll runs yet.</p>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Period</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Gross</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">PAYE</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">NSSF</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Net Pay</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php
          $month_names = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
          foreach ($payroll_items as $pi):
          ?>
            <tr class="hover:bg-gray-50">
              <td class="px-5 py-3">
                <a href="/crm/payroll/view?id=<?= $pi['payroll_id'] ?>" class="font-medium text-gray-900 hover:text-amber-600">
                  <?= $month_names[$pi['pay_month']] ?> <?= $pi['pay_year'] ?>
                </a>
              </td>
              <td class="px-4 py-3 text-right text-gray-700"><?= fmt_money($pi['gross_salary']) ?></td>
              <td class="px-4 py-3 text-right text-red-500 hidden md:table-cell"><?= fmt_money($pi['paye']) ?></td>
              <td class="px-4 py-3 text-right text-orange-500 hidden md:table-cell"><?= fmt_money($pi['nssf_employee']) ?></td>
              <td class="px-4 py-3 text-right font-semibold text-green-600"><?= fmt_money($pi['net_pay']) ?></td>
              <td class="px-4 py-3">
                <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium <?= payroll_badge($pi['payroll_status']) ?>">
                  <?= ucfirst($pi['payroll_status']) ?>
                </span>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
