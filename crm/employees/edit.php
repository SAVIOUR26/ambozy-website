<?php
require_once __DIR__ . '/../partials/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { redirect('/crm/employees/'); }

$emp = null;
if ($pdo) {
    $q = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
    $q->execute([$id]);
    $emp = $q->fetch();
}
if (!$emp) { flash('error', 'Employee not found.'); redirect('/crm/employees/'); }

$errors = [];
$f = [
    'name'            => unclean($emp['name']),
    'position'        => unclean($emp['position']),
    'department'      => unclean($emp['department']),
    'email'           => (string)$emp['email'],
    'phone'           => unclean($emp['phone']),
    'tin'             => unclean($emp['tin']),
    'nssf_number'     => unclean($emp['nssf_number']),
    'gross_salary'    => $emp['gross_salary'] + 0,
    'employment_type' => $emp['employment_type'],
    'status'          => $emp['status'],
    'hire_date'       => $emp['hire_date'] ?? '',
    'notes'           => unclean($emp['notes']),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    foreach ($f as $k => $_) { $f[$k] = trim($_POST[$k] ?? ''); }
    $salary = (float) str_replace(',', '', $f['gross_salary']);
    if (!in_array($f['employment_type'], ['permanent','contract','casual'], true)) $f['employment_type'] = $emp['employment_type'];
    if (!in_array($f['status'], ['active','terminated','on_leave'], true))          $f['status'] = $emp['status'];

    if (!$f['name'])     $errors['name']         = 'Name is required.';
    if ($salary < 0)     $errors['gross_salary']  = 'Enter a valid salary.';
    if ($f['email'] && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email.';

    if (empty($errors)) {
        $pdo->prepare(
            "UPDATE employees SET name=?, position=?, department=?, email=?, phone=?, tin=?, nssf_number=?, gross_salary=?, employment_type=?, status=?, hire_date=?, notes=? WHERE id=?"
        )->execute([
            clean($f['name']), clean($f['position']) ?: null, clean($f['department']) ?: null,
            $f['email'] ?: null, clean($f['phone']) ?: null, clean($f['tin']) ?: null, clean($f['nssf_number']) ?: null,
            $salary, $f['employment_type'], $f['status'],
            $f['hire_date'] ?: null,
            clean($f['notes']) ?: null,
            $id,
        ]);
        log_activity($pdo, 'employee_updated', "Employee {$emp['emp_number']} — {$f['name']} updated.", 'employee', $id);
        flash('success', "Employee {$emp['emp_number']} updated.");
        redirect("/crm/employees/view?id=$id");
    }
}

$page_title = 'Edit Employee';
$active_nav = 'employees';
require_once __DIR__ . '/../partials/header.php';
?>

<div class="max-w-2xl">
  <div class="mb-5">
    <a href="/crm/employees/view?id=<?= $id ?>" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Back to Employee
    </a>
  </div>

  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800">Edit Employee <span class="font-mono text-sm text-gray-400"><?= htmlspecialchars($emp['emp_number']) ?></span></h2>
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

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
        <select name="status" class="w-full sm:w-1/2 border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
          <option value="active"     <?= $f['status']==='active'    ?'selected':'' ?>>Active</option>
          <option value="on_leave"   <?= $f['status']==='on_leave'  ?'selected':'' ?>>On Leave</option>
          <option value="terminated" <?= $f['status']==='terminated'?'selected':'' ?>>Terminated</option>
        </select>
        <p class="text-xs text-gray-400 mt-1">Terminated staff are left out of new payroll runs but keep their history.</p>
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
          <input type="number" name="gross_salary" value="<?= htmlspecialchars($f['gross_salary']) ?>" min="0" step="any"
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
        <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors">Save Changes</button>
        <a href="/crm/employees/view?id=<?= $id ?>" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
