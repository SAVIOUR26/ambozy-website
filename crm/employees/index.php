<?php
$page_title = 'Employees';
$active_nav = 'employees';
$header_actions = '<a href="/crm/employees/create"
   class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
  Add Employee
</a>';
require_once __DIR__ . '/../partials/header.php';

$search   = trim($_GET['q'] ?? '');
$status   = $_GET['status'] ?? 'active';
$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = 20;
$offset   = ($page - 1) * $limit;

$employees     = [];
$total         = 0;
$total_salary  = 0;

if ($pdo) { try {
    $where  = ['1=1'];
    $params = [];
    if ($search !== '') {
        $where[]  = '(e.name LIKE ? OR e.position LIKE ? OR e.emp_number LIKE ?)';
        $like     = "%$search%";
        $params[] = $like; $params[] = $like; $params[] = $like;
    }
    if ($status) { $where[] = 'e.status = ?'; $params[] = $status; }
    $sql_where = implode(' AND ', $where);

    $cnt = $pdo->prepare("SELECT COUNT(*), IFNULL(SUM(e.gross_salary),0) FROM employees e WHERE $sql_where");
    $cnt->execute($params);
    [$total, $total_salary] = $cnt->fetch(PDO::FETCH_NUM);
    $total        = (int)$total;
    $total_salary = (float)$total_salary;

    $stmt = $pdo->prepare(
        "SELECT * FROM employees e WHERE $sql_where ORDER BY e.name ASC LIMIT $limit OFFSET $offset"
    );
    $stmt->execute($params);
    $employees = $stmt->fetchAll();
} catch (PDOException $e) { error_log('Employees: ' . $e->getMessage()); } }

$pages = max(1, (int)ceil($total / $limit));
?>

<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-5">
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
    <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Active Staff</p>
    <p class="text-2xl font-bold text-gray-900"><?= number_format($total) ?></p>
  </div>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
    <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Monthly Payroll</p>
    <p class="text-2xl font-bold text-gray-900"><?= fmt_money($total_salary) ?></p>
  </div>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-5">
  <form method="GET" class="flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-48">
      <label class="block text-xs text-gray-500 font-medium mb-1">Search</label>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Name, position…"
             class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
    </div>
    <div>
      <label class="block text-xs text-gray-500 font-medium mb-1">Status</label>
      <select name="status" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
        <option value="">All</option>
        <option value="active"     <?= $status==='active'    ?'selected':'' ?>>Active</option>
        <option value="on_leave"   <?= $status==='on_leave'  ?'selected':'' ?>>On Leave</option>
        <option value="terminated" <?= $status==='terminated'?'selected':'' ?>>Terminated</option>
      </select>
    </div>
    <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">Filter</button>
    <?php if ($search || ($status !== 'active')): ?>
      <a href="/crm/employees/" class="text-sm text-gray-400 hover:text-gray-600 py-2">Clear</a>
    <?php endif; ?>
  </form>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="px-5 py-4 border-b border-gray-100">
    <p class="text-sm text-gray-500"><?= number_format($total) ?> employee<?= $total !== 1 ? 's' : '' ?></p>
  </div>

  <?php if (empty($employees)): ?>
    <div class="text-center py-16">
      <p class="text-4xl mb-3">👤</p>
      <p class="text-gray-500 font-medium">No employees found</p>
      <a href="/crm/employees/create" class="mt-3 inline-block text-amber-600 hover:text-amber-700 text-sm font-medium">+ Add first employee</a>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Employee</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Position</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Contact</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Gross Salary</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($employees as $emp): ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3.5">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center text-xs font-bold shrink-0">
                    <?= strtoupper(substr($emp['name'], 0, 1)) ?>
                  </div>
                  <div>
                    <a href="/crm/employees/view?id=<?= $emp['id'] ?>" class="font-medium text-gray-900 hover:text-amber-600 transition-colors">
                      <?= htmlspecialchars($emp['name']) ?>
                    </a>
                    <p class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($emp['emp_number']) ?></p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3.5 hidden md:table-cell text-gray-600"><?= htmlspecialchars($emp['position'] ?? '—') ?></td>
              <td class="px-4 py-3.5 hidden lg:table-cell text-gray-500 text-xs"><?= htmlspecialchars($emp['phone'] ?? $emp['email'] ?? '—') ?></td>
              <td class="px-4 py-3.5 text-right font-semibold text-gray-900"><?= fmt_money($emp['gross_salary']) ?></td>
              <td class="px-4 py-3.5">
                <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium <?= employee_badge($emp['status']) ?>">
                  <?= ucfirst(str_replace('_',' ',$emp['status'])) ?>
                </span>
              </td>
              <td class="px-4 py-3.5 text-right">
                <a href="/crm/employees/view?id=<?= $emp['id'] ?>" class="text-gray-400 hover:text-amber-600 transition-colors" title="View">
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
    <?php if ($pages > 1): ?>
      <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
        <p class="text-xs text-gray-400">Page <?= $page ?> of <?= $pages ?></p>
        <div class="flex gap-1">
          <?php for ($i = max(1,$page-2); $i <= min($pages,$page+2); $i++): ?>
            <a href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>"
               class="w-8 h-8 flex items-center justify-center rounded text-xs font-medium
                      <?= $i===$page ? 'bg-amber-500 text-white' : 'text-gray-500 hover:bg-gray-100' ?>">
              <?= $i ?>
            </a>
          <?php endfor; ?>
        </div>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
