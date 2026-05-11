<?php
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /crm/payroll/'); exit; }

$payroll = null;
$items   = [];

if ($pdo) { try {
    $stmt = $pdo->prepare("SELECT * FROM payroll WHERE id = ?");
    $stmt->execute([$id]);
    $payroll = $stmt->fetch();
    if (!$payroll) { header('Location: /crm/payroll/'); exit; }

    $items = $pdo->prepare(
        "SELECT pi.*, e.name AS emp_name, e.position, e.nssf_number, e.tin
         FROM payroll_items pi
         JOIN employees e ON pi.employee_id = e.id
         WHERE pi.payroll_id = ?
         ORDER BY e.name"
    );
    $items->execute([$id]);
    $items = $items->fetchAll();
} catch (PDOException $e) { error_log('Payroll view: ' . $e->getMessage()); } }

// Status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status']) && $pdo && $payroll) {
    $new_status = in_array($_POST['new_status'] ?? '', ['draft','approved','paid']) ? $_POST['new_status'] : null;
    if ($new_status) {
        $pdo->prepare("UPDATE payroll SET status=? WHERE id=?")->execute([$new_status, $id]);
        if ($new_status === 'paid') {
            // Mark all PAYE/NSSF obligations for this payroll as paid
            $pdo->prepare("UPDATE statutory_obligations SET status='paid', paid_date=CURDATE() WHERE payroll_id=?")->execute([$id]);
        }
        flash('success', 'Payroll status updated.');
        redirect("/crm/payroll/view?id=$id");
    }
}

$month_names = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
$page_title  = ($month_names[$payroll['pay_month']] ?? '') . ' ' . $payroll['pay_year'] . ' Payroll';
$active_nav  = 'payroll';
require_once __DIR__ . '/../partials/header.php';
?>

<div class="mb-5">
  <a href="/crm/payroll/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Back to Payroll
  </a>
</div>

<!-- Header card -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6 mb-5">
  <div class="flex items-start justify-between flex-wrap gap-4 mb-5">
    <div>
      <h2 class="text-xl font-bold text-gray-900">
        <?= $month_names[$payroll['pay_month']] ?> <?= $payroll['pay_year'] ?> Payroll
      </h2>
      <p class="text-sm text-gray-400 font-mono mt-0.5"><?= htmlspecialchars($payroll['ref']) ?></p>
    </div>
    <div class="flex items-center gap-3">
      <span class="inline-block text-sm px-3 py-1 rounded-full font-medium <?= payroll_badge($payroll['status']) ?>">
        <?= ucfirst($payroll['status']) ?>
      </span>
      <?php if ($payroll['status'] !== 'paid'): ?>
        <form method="POST" class="inline">
          <input type="hidden" name="change_status" value="1">
          <?php if ($payroll['status'] === 'draft'): ?>
            <button type="submit" name="new_status" value="approved"
                    class="text-xs bg-blue-500 hover:bg-blue-400 text-white px-3 py-1.5 rounded-lg font-medium transition-colors">
              Approve
            </button>
          <?php endif; ?>
          <?php if ($payroll['status'] === 'approved'): ?>
            <button type="submit" name="new_status" value="paid"
                    class="text-xs bg-green-500 hover:bg-green-400 text-white px-3 py-1.5 rounded-lg font-medium transition-colors">
              Mark Paid
            </button>
          <?php endif; ?>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-5 gap-4 text-sm">
    <div class="bg-gray-50 rounded-xl p-4">
      <p class="text-xs text-gray-400 mb-1">Total Gross</p>
      <p class="font-bold text-gray-900"><?= fmt_money($payroll['total_gross']) ?></p>
    </div>
    <div class="bg-red-50 rounded-xl p-4">
      <p class="text-xs text-red-400 mb-1">Total PAYE</p>
      <p class="font-bold text-red-700"><?= fmt_money($payroll['total_paye']) ?></p>
    </div>
    <div class="bg-orange-50 rounded-xl p-4">
      <p class="text-xs text-orange-400 mb-1">NSSF (Employee)</p>
      <p class="font-bold text-orange-700"><?= fmt_money($payroll['total_nssf_employee']) ?></p>
    </div>
    <div class="bg-orange-50 rounded-xl p-4">
      <p class="text-xs text-orange-400 mb-1">NSSF (Employer)</p>
      <p class="font-bold text-orange-700"><?= fmt_money($payroll['total_nssf_employer']) ?></p>
    </div>
    <div class="bg-green-50 rounded-xl p-4">
      <p class="text-xs text-green-400 mb-1">Total Net Pay</p>
      <p class="font-bold text-green-700"><?= fmt_money($payroll['total_net']) ?></p>
    </div>
  </div>
</div>

<!-- Payslip table -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="px-5 py-4 border-b border-gray-100">
    <h3 class="font-semibold text-gray-800">Individual Payslips (<?= count($items) ?> employees)</h3>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 border-b border-gray-100">
        <tr>
          <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Employee</th>
          <th class="text-right px-3 py-3 text-xs font-semibold text-gray-500 uppercase">Gross</th>
          <th class="text-right px-3 py-3 text-xs font-semibold text-gray-500 uppercase">PAYE</th>
          <th class="text-right px-3 py-3 text-xs font-semibold text-gray-500 uppercase">NSSF (Emp)</th>
          <th class="text-right px-3 py-3 text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Employer NSSF</th>
          <th class="text-right px-3 py-3 text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Other Ded.</th>
          <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Net Pay</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        <?php foreach ($items as $item): ?>
          <tr class="hover:bg-gray-50">
            <td class="px-5 py-3.5">
              <a href="/crm/employees/view?id=<?= $item['employee_id'] ?>" class="font-medium text-gray-900 hover:text-amber-600 transition-colors">
                <?= htmlspecialchars($item['emp_name']) ?>
              </a>
              <p class="text-xs text-gray-400"><?= htmlspecialchars($item['position'] ?? '') ?></p>
            </td>
            <td class="px-3 py-3.5 text-right text-gray-900"><?= fmt_money($item['gross_salary']) ?></td>
            <td class="px-3 py-3.5 text-right text-red-500"><?= fmt_money($item['paye']) ?></td>
            <td class="px-3 py-3.5 text-right text-orange-500"><?= fmt_money($item['nssf_employee']) ?></td>
            <td class="px-3 py-3.5 text-right text-gray-400 hidden lg:table-cell"><?= fmt_money($item['nssf_employer']) ?></td>
            <td class="px-3 py-3.5 text-right text-gray-500 hidden md:table-cell"><?= $item['other_deductions'] > 0 ? fmt_money($item['other_deductions']) : '—' ?></td>
            <td class="px-5 py-3.5 text-right font-bold text-green-600"><?= fmt_money($item['net_pay']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot class="bg-gray-50 border-t border-gray-200">
        <tr>
          <td class="px-5 py-3 font-semibold text-gray-700">Totals</td>
          <td class="px-3 py-3 text-right font-bold text-gray-900"><?= fmt_money($payroll['total_gross']) ?></td>
          <td class="px-3 py-3 text-right font-bold text-red-600"><?= fmt_money($payroll['total_paye']) ?></td>
          <td class="px-3 py-3 text-right font-bold text-orange-600"><?= fmt_money($payroll['total_nssf_employee']) ?></td>
          <td class="px-3 py-3 text-right text-gray-400 hidden lg:table-cell"><?= fmt_money($payroll['total_nssf_employer']) ?></td>
          <td class="px-3 py-3 hidden md:table-cell"></td>
          <td class="px-5 py-3 text-right font-bold text-green-600"><?= fmt_money($payroll['total_net']) ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
