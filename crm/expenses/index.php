<?php
$page_title = 'Expenses';
$active_nav = 'expenses';
$header_actions = '<a href="/crm/expenses/create"
   class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
  Record Expense
</a>';
require_once __DIR__ . '/../partials/header.php';

$search   = trim($_GET['q'] ?? '');
$cat_id   = (int)($_GET['cat'] ?? 0);
$month    = $_GET['month'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = 25;
$offset   = ($page - 1) * $limit;

$expenses   = [];
$total      = 0;
$total_amt  = 0;
$categories = [];

if ($pdo) { try {
    $categories = $pdo->query("SELECT * FROM expense_categories WHERE is_active=1 ORDER BY name")->fetchAll();

    $where  = ['1=1'];
    $params = [];
    if ($search !== '') {
        $where[]  = '(e.description LIKE ? OR e.vendor LIKE ? OR e.ref LIKE ?)';
        $like     = "%$search%";
        $params[] = $like; $params[] = $like; $params[] = $like;
    }
    if ($cat_id) { $where[] = 'e.category_id = ?'; $params[] = $cat_id; }
    if ($month)  { $where[] = 'DATE_FORMAT(e.expense_date, \'%Y-%m\') = ?'; $params[] = $month; }
    $sql_where = implode(' AND ', $where);

    $cnt = $pdo->prepare("SELECT COUNT(*), IFNULL(SUM(e.amount),0) FROM expenses e WHERE $sql_where");
    $cnt->execute($params);
    [$total, $total_amt] = $cnt->fetch(PDO::FETCH_NUM);
    $total = (int)$total;
    $total_amt = (float)$total_amt;

    $stmt = $pdo->prepare(
        "SELECT e.*, ec.name AS category_name
         FROM expenses e
         LEFT JOIN expense_categories ec ON e.category_id = ec.id
         WHERE $sql_where
         ORDER BY e.expense_date DESC, e.id DESC
         LIMIT $limit OFFSET $offset"
    );
    $stmt->execute($params);
    $expenses = $stmt->fetchAll();
} catch (PDOException $e) { error_log('Expenses: ' . $e->getMessage()); } }

$pages = max(1, (int)ceil($total / $limit));
?>

<!-- Summary strip -->
<div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-5">
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
    <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Total Records</p>
    <p class="text-2xl font-bold text-gray-900"><?= number_format($total) ?></p>
  </div>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
    <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">
      <?= $month ? 'Period Total' : 'Filtered Total' ?>
    </p>
    <p class="text-2xl font-bold text-red-600"><?= fmt_money($total_amt) ?></p>
  </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-5">
  <form method="GET" class="flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-48">
      <label class="block text-xs text-gray-500 font-medium mb-1">Search</label>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Description, vendor, ref…"
             class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
    </div>
    <div>
      <label class="block text-xs text-gray-500 font-medium mb-1">Category</label>
      <select name="cat" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
        <option value="">All Categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $cat_id === (int)$c['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-xs text-gray-500 font-medium mb-1">Month</label>
      <input type="month" name="month" value="<?= htmlspecialchars($month) ?>"
             class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
    </div>
    <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">Filter</button>
    <?php if ($search || $cat_id || $month): ?>
      <a href="/crm/expenses/" class="text-sm text-gray-400 hover:text-gray-600 py-2">Clear</a>
    <?php endif; ?>
  </form>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="px-5 py-4 border-b border-gray-100">
    <p class="text-sm text-gray-500"><?= number_format($total) ?> expense<?= $total !== 1 ? 's' : '' ?></p>
  </div>

  <?php if (empty($expenses)): ?>
    <div class="text-center py-16">
      <p class="text-4xl mb-3">💸</p>
      <p class="text-gray-500 font-medium">No expenses recorded</p>
      <a href="/crm/expenses/create" class="mt-3 inline-block text-amber-600 hover:text-amber-700 text-sm font-medium">+ Record first expense</a>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ref / Description</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Category</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Vendor</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Date</th>
            <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($expenses as $exp): ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3.5">
                <p class="font-medium text-gray-900"><?= htmlspecialchars($exp['description']) ?></p>
                <p class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($exp['ref']) ?></p>
              </td>
              <td class="px-4 py-3.5 hidden md:table-cell text-gray-500"><?= htmlspecialchars($exp['category_name'] ?? '—') ?></td>
              <td class="px-4 py-3.5 hidden lg:table-cell text-gray-500"><?= htmlspecialchars($exp['vendor'] ?? '—') ?></td>
              <td class="px-4 py-3.5 hidden md:table-cell text-gray-500"><?= date('d M Y', strtotime($exp['expense_date'])) ?></td>
              <td class="px-5 py-3.5 text-right font-semibold text-gray-900"><?= fmt_money($exp['amount']) ?></td>
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
