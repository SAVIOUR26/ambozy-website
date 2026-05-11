<?php
$page_title = 'Add Employee';
$active_nav = 'employees';
require_once __DIR__ . '/../partials/header.php';

$errors = [];
$f = [
    'name'            => '',
    'position'        => '',
    'department'      => '',
    'email'           => '',
    'phone'           => '',
    'tin'             => '',
    'nssf_number'     => '',
    'gross_salary'    => '',
    'employment_type' => 'permanent',
    'hire_date'       => date('Y-m-d'),
    'notes'           => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    foreach ($f as $k => $_) { $f[$k] = clean($_POST[$k] ?? ''); }
    $salary = (float) str_replace(',', '', $f['gross_salary']);

    if (!$f['name'])     $errors['name']         = 'Name is required.';
    if ($salary < 0)     $errors['gross_salary']  = 'Enter a valid salary.';
    if ($f['email'] && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email.';

    if (empty($errors)) {
        $emp_number = next_doc_number($pdo, 'EMP');
        $stmt = $pdo->prepare(
            "INSERT INTO employees (emp_number, name, position, department, email, phone, tin, nssf_number, gross_salary, employment_type, hire_date, notes, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $emp_number, $f['name'], $f['position'] ?: null, $f['department'] ?: null,
            $f['email'] ?: null, $f['phone'] ?: null, $f['tin'] ?: null, $f['nssf_number'] ?: null,
            $salary, $f['employment_type'],
            $f['hire_date'] ?: null,
            $f['notes'] ?: null,
            $_SESSION['admin_id'] ?? null,
        ]);
        $id = (int)$pdo->lastInsertId();
        log_activity($pdo, 'employee_added', "Employee {$emp_number} — {$f['name']} added.", 'employee', $id);
        flash('success', "Employee {$emp_number} added successfully.");
        redirect("/crm/employees/view?id=$id");
    }
}
?>

<div class="max-w-2xl">
  <div class="mb-5">
    <a href="/crm/employees/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Back to Employees
    </a>
  </div>

  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800">Employee Information</h2>
    </div>

    <form method="POST" class="px-6 py-5 space-y-5">

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name <span class="text-red-500">*</span></label>
          <input type="text" name="name" value="<?= htmlspecialchars($f['name']) ?>" required
                 class="w-full border <?= isset($errors['name']) ? 'border-red-400' : 'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
          <?php if (isset($errors['name'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['name'] ?></p><?php endif; ?>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Position / Job Title</label>
          <input type="text" name="position" value="<?= htmlspecialchars($f['position']) ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Department</label>
          <input type="text" name="department" value="<?= htmlspecialchars($f['department']) ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Employment Type</label>
          <select name="employment_type" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <option value="permanent" <?= $f['employment_type']==='permanent'?'selected':'' ?>>Permanent</option>
            <option value="contract"  <?= $f['employment_type']==='contract' ?'selected':'' ?>>Contract</option>
            <option value="casual"    <?= $f['employment_type']==='casual'   ?'selected':'' ?>>Casual</option>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($f['email']) ?>"
                 class="w-full border <?= isset($errors['email']) ? 'border-red-400' : 'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
          <?php if (isset($errors['email'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['email'] ?></p><?php endif; ?>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
          <input type="tel" name="phone" value="<?= htmlspecialchars($f['phone']) ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">URA TIN</label>
          <input type="text" name="tin" value="<?= htmlspecialchars($f['tin']) ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400" placeholder="Tax Identification Number">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">NSSF Number</label>
          <input type="text" name="nssf_number" value="<?= htmlspecialchars($f['nssf_number']) ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Monthly Gross Salary (UGX)</label>
          <input type="number" name="gross_salary" value="<?= htmlspecialchars($f['gross_salary']) ?>" min="0" step="1000"
                 class="w-full border <?= isset($errors['gross_salary']) ? 'border-red-400' : 'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
          <?php if (isset($errors['gross_salary'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['gross_salary'] ?></p><?php endif; ?>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Hire Date</label>
          <input type="date" name="hire_date" value="<?= htmlspecialchars($f['hire_date']) ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
        <textarea name="notes" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 resize-none"><?= htmlspecialchars($f['notes']) ?></textarea>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors">Save Employee</button>
        <a href="/crm/employees/" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
