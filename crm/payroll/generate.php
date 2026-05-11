<?php
$page_title = 'Generate Payroll';
$active_nav = 'payroll';
require_once __DIR__ . '/../partials/header.php';

$errors    = [];
$employees = [];
$preview   = false;
$sel_month = (int)($_POST['pay_month'] ?? date('n'));
$sel_year  = (int)($_POST['pay_year']  ?? date('Y'));

if ($pdo) { try {
    $employees = $pdo->query("SELECT * FROM employees WHERE status='active' ORDER BY name")->fetchAll();
} catch (PDOException $e) { error_log('Payroll generate: ' . $e->getMessage()); } }

// Build preview (POST with action=preview or action=save)
$action = $_POST['action'] ?? '';
$preview_rows = [];

if ($action && $pdo) {
    foreach ($employees as $emp) {
        $gross    = (float)str_replace(',', '', $_POST['gross_' . $emp['id']] ?? $emp['gross_salary']);
        $extra_ded= (float)str_replace(',', '', $_POST['extra_ded_' . $emp['id']] ?? '0');
        $paye     = calculate_paye($gross);
        $nssf_emp = round($gross * 0.05);
        $nssf_er  = round($gross * 0.10);
        $net      = max(0, $gross - $paye - $nssf_emp - $extra_ded);
        $preview_rows[] = [
            'id'           => $emp['id'],
            'name'         => $emp['name'],
            'gross'        => $gross,
            'paye'         => $paye,
            'nssf_emp'     => $nssf_emp,
            'nssf_er'      => $nssf_er,
            'extra_ded'    => $extra_ded,
            'net'          => $net,
        ];
    }

    if ($action === 'save') {
        // Check if payroll for this month/year already exists
        $exists = $pdo->prepare("SELECT id FROM payroll WHERE pay_month=? AND pay_year=?");
        $exists->execute([$sel_month, $sel_year]);
        if ($exists->fetchColumn()) {
            $errors['duplicate'] = "Payroll for this period already exists.";
        } else {
            try {
                $pdo->beginTransaction();
                $total_gross  = array_sum(array_column($preview_rows, 'gross'));
                $total_paye   = array_sum(array_column($preview_rows, 'paye'));
                $total_nssf_e = array_sum(array_column($preview_rows, 'nssf_emp'));
                $total_nssf_r = array_sum(array_column($preview_rows, 'nssf_er'));
                $total_net    = array_sum(array_column($preview_rows, 'net'));
                $ref          = next_doc_number($pdo, 'PAY');

                $pr = $pdo->prepare(
                    "INSERT INTO payroll (ref, pay_month, pay_year, status, total_gross, total_paye, total_nssf_employee, total_nssf_employer, total_net, created_by)
                     VALUES (?,?,?,?,?,?,?,?,?,?)"
                );
                $pr->execute([$ref, $sel_month, $sel_year, 'draft', $total_gross, $total_paye, $total_nssf_e, $total_nssf_r, $total_net, $_SESSION['admin_id'] ?? null]);
                $payroll_id = (int)$pdo->lastInsertId();

                $ins = $pdo->prepare(
                    "INSERT INTO payroll_items (payroll_id, employee_id, gross_salary, paye, nssf_employee, nssf_employer, other_deductions, net_pay)
                     VALUES (?,?,?,?,?,?,?,?)"
                );
                foreach ($preview_rows as $row) {
                    $ins->execute([$payroll_id, $row['id'], $row['gross'], $row['paye'], $row['nssf_emp'], $row['nssf_er'], $row['extra_ded'], $row['net']]);
                }

                // Auto-create PAYE and NSSF statutory obligations
                $month_names = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
                $due_paye  = date('Y-m-15', mktime(0,0,0, $sel_month+1, 1, $sel_year)); // 15th of next month
                $due_nssf  = date('Y-m-15', mktime(0,0,0, $sel_month+1, 1, $sel_year));

                $stat = $pdo->prepare(
                    "INSERT IGNORE INTO statutory_obligations (type, period_month, period_year, amount_due, due_date, status, payroll_id, created_by)
                     VALUES (?,?,?,?,?,?,?,?)"
                );
                $stat->execute(['paye',  $sel_month, $sel_year, $total_paye, $due_paye, 'pending', $payroll_id, $_SESSION['admin_id'] ?? null]);
                $stat->execute(['nssf',  $sel_month, $sel_year, $total_nssf_e + $total_nssf_r, $due_nssf, 'pending', $payroll_id, $_SESSION['admin_id'] ?? null]);

                log_activity($pdo, 'payroll_generated', "Payroll {$ref} for {$month_names[$sel_month]} {$sel_year} generated.", 'payroll', $payroll_id);
                $pdo->commit();
                flash('success', "Payroll {$ref} created. PAYE and NSSF obligations auto-logged.");
                redirect("/crm/payroll/view?id=$payroll_id");
            } catch (Throwable $e) {
                $pdo->rollBack();
                error_log('Payroll generate save: ' . $e->getMessage());
                $errors['general'] = 'Error saving payroll. Please try again.';
            }
        }
    }
    $preview = true;
}

$month_names = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
?>

<div class="max-w-4xl">
  <div class="mb-5">
    <a href="/crm/payroll/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Back to Payroll
    </a>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="mb-4 px-4 py-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
      <?= htmlspecialchars(implode(' ', $errors)) ?>
    </div>
  <?php endif; ?>

  <?php if (empty($employees)): ?>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-10 text-center">
      <p class="text-gray-500 mb-3">No active employees found.</p>
      <a href="/crm/employees/create" class="text-amber-600 hover:text-amber-700 font-medium text-sm">+ Add employees first</a>
    </div>
  <?php else: ?>

  <form method="POST">
    <!-- Period selector -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-5">
      <h2 class="font-semibold text-gray-800 mb-4">Pay Period</h2>
      <div class="flex flex-wrap gap-4 items-end">
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1.5">Month</label>
          <select name="pay_month" class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <?php for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $m ?>" <?= $sel_month === $m ? 'selected' : '' ?>><?= $month_names[$m] ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <div>
          <label class="block text-xs font-medium text-gray-600 mb-1.5">Year</label>
          <select name="pay_year" class="border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <?php for ($y = date('Y'); $y >= date('Y')-3; $y--): ?>
              <option value="<?= $y ?>" <?= $sel_year === $y ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <button type="submit" name="action" value="preview"
                class="bg-slate-700 hover:bg-slate-600 text-white text-sm font-medium px-5 py-2.5 rounded-lg transition-colors">
          Preview Payroll
        </button>
      </div>
    </div>

    <!-- Employee salary table (editable) -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden mb-5">
      <div class="px-5 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">
          Salary Details — <?= $month_names[$sel_month] ?> <?= $sel_year ?>
        </h2>
        <p class="text-xs text-gray-400 mt-0.5">PAYE and NSSF are auto-calculated per Uganda Revenue Authority rates. You may override gross salaries.</p>
      </div>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Employee</th>
              <th class="text-right px-3 py-3 text-xs font-semibold text-gray-500 uppercase">Gross</th>
              <th class="text-right px-3 py-3 text-xs font-semibold text-gray-500 uppercase">PAYE</th>
              <th class="text-right px-3 py-3 text-xs font-semibold text-gray-500 uppercase">NSSF Emp</th>
              <th class="text-right px-3 py-3 text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Employer NSSF</th>
              <th class="text-right px-3 py-3 text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Other Ded.</th>
              <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Net Pay</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($employees as $emp):
              $gross = 0; $paye = 0; $nssf_emp = 0; $nssf_er = 0; $net = 0; $extra = 0;
              if ($preview) {
                foreach ($preview_rows as $pr) {
                    if ($pr['id'] == $emp['id']) {
                        $gross = $pr['gross']; $paye = $pr['paye']; $nssf_emp = $pr['nssf_emp'];
                        $nssf_er = $pr['nssf_er']; $net = $pr['net']; $extra = $pr['extra_ded'];
                        break;
                    }
                }
              } else {
                $gross = $emp['gross_salary'];
                $paye  = calculate_paye($gross);
                $nssf_emp = round($gross * 0.05);
                $nssf_er  = round($gross * 0.10);
                $net   = max(0, $gross - $paye - $nssf_emp);
              }
            ?>
              <tr class="hover:bg-gray-50">
                <td class="px-5 py-3">
                  <p class="font-medium text-gray-900"><?= htmlspecialchars($emp['name']) ?></p>
                  <p class="text-xs text-gray-400"><?= htmlspecialchars($emp['position'] ?? '') ?></p>
                </td>
                <td class="px-3 py-2">
                  <input type="number" name="gross_<?= $emp['id'] ?>" value="<?= $gross ?>" min="0" step="1000"
                         class="w-28 border border-gray-200 rounded px-2 py-1.5 text-sm text-right focus:outline-none focus:border-amber-400">
                </td>
                <td class="px-3 py-3 text-right text-red-500"><?= number_format($paye, 0) ?></td>
                <td class="px-3 py-3 text-right text-orange-500"><?= number_format($nssf_emp, 0) ?></td>
                <td class="px-3 py-3 text-right text-gray-400 hidden lg:table-cell"><?= number_format($nssf_er, 0) ?></td>
                <td class="px-3 py-2 hidden md:table-cell">
                  <input type="number" name="extra_ded_<?= $emp['id'] ?>" value="<?= $extra ?>" min="0" step="1000"
                         class="w-28 border border-gray-200 rounded px-2 py-1.5 text-sm text-right focus:outline-none focus:border-amber-400">
                </td>
                <td class="px-5 py-3 text-right font-bold text-green-600"><?= number_format($net, 0) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <?php if ($preview && !empty($preview_rows)): ?>
            <tfoot class="bg-gray-50 border-t border-gray-200">
              <tr>
                <td class="px-5 py-3 font-semibold text-gray-700">Totals</td>
                <td class="px-3 py-3 text-right font-semibold text-gray-900"><?= number_format(array_sum(array_column($preview_rows,'gross')),0) ?></td>
                <td class="px-3 py-3 text-right font-semibold text-red-600"><?= number_format(array_sum(array_column($preview_rows,'paye')),0) ?></td>
                <td class="px-3 py-3 text-right font-semibold text-orange-600"><?= number_format(array_sum(array_column($preview_rows,'nssf_emp')),0) ?></td>
                <td class="px-3 py-3 text-right text-gray-400 hidden lg:table-cell"><?= number_format(array_sum(array_column($preview_rows,'nssf_er')),0) ?></td>
                <td class="px-3 py-3 hidden md:table-cell"></td>
                <td class="px-5 py-3 text-right font-bold text-green-600"><?= number_format(array_sum(array_column($preview_rows,'net')),0) ?></td>
              </tr>
            </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>

    <div class="flex items-center gap-3">
      <?php if ($preview): ?>
        <button type="submit" name="action" value="save"
                class="bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors">
          Confirm &amp; Save Payroll
        </button>
      <?php endif; ?>
      <button type="submit" name="action" value="preview"
              class="bg-slate-700 hover:bg-slate-600 text-white font-medium text-sm px-5 py-2.5 rounded-lg transition-colors">
        <?= $preview ? 'Recalculate' : 'Preview' ?>
      </button>
      <a href="/crm/payroll/" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
    </div>
  </form>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
