<?php
$page_title = 'Suppliers';
$active_nav = 'suppliers';
$header_actions = '<a href="/crm/suppliers/create"
   class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
  New Supplier
</a>';
require_once __DIR__ . '/../partials/header.php';

$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$suppliers = [];
$total     = 0;

if ($pdo) { try {
    $where  = ['1=1'];
    $params = [];
    if ($search !== '') {
        $where[]  = '(s.name LIKE ? OR s.contact_name LIKE ? OR s.email LIKE ? OR s.code LIKE ?)';
        $like     = "%$search%";
        $params   = [$like, $like, $like, $like];
    }
    if ($status) { $where[] = 's.status = ?'; $params[] = $status; }
    $sql_where = implode(' AND ', $where);

    $cnt = $pdo->prepare("SELECT COUNT(*) FROM suppliers s WHERE $sql_where");
    $cnt->execute($params);
    $total = (int)$cnt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT s.*,
                (SELECT IFNULL(SUM(p.total - p.amount_paid),0)
                 FROM purchases p WHERE p.supplier_id = s.id AND p.status NOT IN ('paid','cancelled')) AS balance_owed,
                (SELECT COUNT(*) FROM purchases p WHERE p.supplier_id = s.id) AS purchase_count
         FROM suppliers s
         WHERE $sql_where
         ORDER BY s.created_at DESC
         LIMIT $limit OFFSET $offset"
    );
    $stmt->execute($params);
    $suppliers = $stmt->fetchAll();
} catch (PDOException $e) { error_log('Suppliers: ' . $e->getMessage()); } }

$pages = max(1, (int)ceil($total / $limit));
?>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-5">
  <form method="GET" class="flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-48">
      <label class="block text-xs text-gray-500 font-medium mb-1">Search</label>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Name, email, code…"
             class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
    </div>
    <div>
      <label class="block text-xs text-gray-500 font-medium mb-1">Status</label>
      <select name="status" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
        <option value="">All</option>
        <option value="active"   <?= $status==='active'   ? 'selected':'' ?>>Active</option>
        <option value="inactive" <?= $status==='inactive' ? 'selected':'' ?>>Inactive</option>
      </select>
    </div>
    <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">Filter</button>
    <?php if ($search || $status): ?>
      <a href="/crm/suppliers/" class="text-sm text-gray-400 hover:text-gray-600 py-2">Clear</a>
    <?php endif; ?>
  </form>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="px-5 py-4 border-b border-gray-100">
    <p class="text-sm text-gray-500"><?= number_format($total) ?> supplier<?= $total !== 1 ? 's' : '' ?></p>
  </div>

  <?php if (empty($suppliers)): ?>
    <div class="text-center py-16">
      <p class="text-4xl mb-3">🏭</p>
      <p class="text-gray-500 font-medium">No suppliers yet</p>
      <a href="/crm/suppliers/create" class="mt-3 inline-block text-amber-600 hover:text-amber-700 text-sm font-medium">+ Add first supplier</a>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Supplier</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Contact</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Credit Limit</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Balance Owed</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden xl:table-cell">Purchases</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($suppliers as $s): ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3.5">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-violet-100 text-violet-700 flex items-center justify-center text-xs font-bold shrink-0">
                    <?= strtoupper(substr($s['name'], 0, 1)) ?>
                  </div>
                  <div>
                    <a href="/crm/suppliers/view?id=<?= $s['id'] ?>"
                       class="font-medium text-gray-900 hover:text-amber-600 transition-colors">
                      <?= htmlspecialchars($s['name']) ?>
                    </a>
                    <p class="text-xs text-gray-400"><?= htmlspecialchars($s['code']) ?></p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3.5 hidden md:table-cell">
                <p class="text-gray-700"><?= htmlspecialchars($s['contact_name'] ?? '—') ?></p>
                <p class="text-xs text-gray-400"><?= htmlspecialchars($s['phone'] ?? '') ?></p>
              </td>
              <td class="px-4 py-3.5 text-right hidden lg:table-cell">
                <?= $s['credit_limit'] > 0 ? fmt_money($s['credit_limit']) : '<span class="text-gray-400">—</span>' ?>
              </td>
              <td class="px-4 py-3.5 text-right hidden lg:table-cell">
                <?php if ($s['balance_owed'] > 0): ?>
                  <span class="font-medium text-red-600"><?= fmt_money($s['balance_owed']) ?></span>
                <?php else: ?>
                  <span class="text-gray-400">—</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3.5 text-right hidden xl:table-cell">
                <span class="font-medium text-gray-700"><?= $s['purchase_count'] ?></span>
              </td>
              <td class="px-4 py-3.5">
                <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium <?= $s['status']==='active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
                  <?= ucfirst($s['status']) ?>
                </span>
              </td>
              <td class="px-4 py-3.5 text-right">
                <a href="/crm/suppliers/view?id=<?= $s['id'] ?>"
                   class="text-gray-400 hover:text-amber-600 transition-colors" title="View">
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
