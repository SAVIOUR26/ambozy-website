<?php
$page_title = 'Purchases';
$active_nav = 'purchases';
$header_actions = '<a href="/crm/purchases/create"
   class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
  New Purchase
</a>';
require_once __DIR__ . '/../partials/header.php';

$search       = trim($_GET['q'] ?? '');
$status_filter = $_GET['status'] ?? '';
$type_filter  = $_GET['type'] ?? '';
$page         = max(1, (int)($_GET['page'] ?? 1));
$limit        = 20;
$offset       = ($page - 1) * $limit;

$purchases = [];
$total     = 0;

if ($pdo) { try {
    $where  = ['1=1'];
    $params = [];
    if ($search !== '') {
        $where[]  = '(p.purchase_number LIKE ? OR s.name LIKE ?)';
        $like     = "%$search%";
        $params[] = $like; $params[] = $like;
    }
    if ($status_filter) { $where[] = 'p.status = ?'; $params[] = $status_filter; }
    if ($type_filter)   { $where[] = 'p.payment_type = ?'; $params[] = $type_filter; }
    $sql_where = implode(' AND ', $where);

    $cnt = $pdo->prepare("SELECT COUNT(*) FROM purchases p JOIN suppliers s ON p.supplier_id = s.id WHERE $sql_where");
    $cnt->execute($params);
    $total = (int)$cnt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT p.*, s.name AS supplier_name
         FROM purchases p JOIN suppliers s ON p.supplier_id = s.id
         WHERE $sql_where
         ORDER BY p.purchase_date DESC, p.id DESC
         LIMIT $limit OFFSET $offset"
    );
    $stmt->execute($params);
    $purchases = $stmt->fetchAll();
} catch (PDOException $e) { error_log('Purchases index: ' . $e->getMessage()); } }

$pages = max(1, (int)ceil($total / $limit));
?>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-5">
  <form method="GET" class="flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-48">
      <label class="block text-xs text-gray-500 font-medium mb-1">Search</label>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Purchase #, supplier…"
             class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
    </div>
    <div>
      <label class="block text-xs text-gray-500 font-medium mb-1">Status</label>
      <select name="status" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
        <option value="">All</option>
        <?php foreach (['pending','received','partial','paid','cancelled'] as $s): ?>
          <option value="<?= $s ?>" <?= $status_filter===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-xs text-gray-500 font-medium mb-1">Type</label>
      <select name="type" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
        <option value="">All</option>
        <option value="cash"   <?= $type_filter==='cash'  ?'selected':'' ?>>Cash</option>
        <option value="credit" <?= $type_filter==='credit'?'selected':'' ?>>Credit</option>
      </select>
    </div>
    <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">Filter</button>
    <?php if ($search || $status_filter || $type_filter): ?>
      <a href="/crm/purchases/" class="text-sm text-gray-400 hover:text-gray-600 py-2">Clear</a>
    <?php endif; ?>
  </form>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="px-5 py-4 border-b border-gray-100">
    <p class="text-sm text-gray-500"><?= number_format($total) ?> purchase<?= $total !== 1 ? 's' : '' ?></p>
  </div>

  <?php if (empty($purchases)): ?>
    <div class="text-center py-16">
      <p class="text-4xl mb-3">🛒</p>
      <p class="text-gray-500 font-medium">No purchases recorded</p>
      <a href="/crm/purchases/create" class="mt-3 inline-block text-amber-600 hover:text-amber-700 text-sm font-medium">+ Record first purchase</a>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Purchase #</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Supplier</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Date</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Type</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Balance</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($purchases as $p): ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3.5 font-mono text-xs font-medium text-gray-900"><?= htmlspecialchars($p['purchase_number']) ?></td>
              <td class="px-4 py-3.5">
                <a href="/crm/suppliers/view?id=<?= $p['supplier_id'] ?>"
                   class="text-gray-700 hover:text-amber-600 transition-colors font-medium">
                  <?= htmlspecialchars($p['supplier_name']) ?>
                </a>
              </td>
              <td class="px-4 py-3.5 text-gray-500 hidden md:table-cell"><?= date('d M Y', strtotime($p['purchase_date'])) ?></td>
              <td class="px-4 py-3.5 hidden md:table-cell">
                <span class="text-xs px-2 py-0.5 rounded-full font-medium <?= $p['payment_type']==='credit' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700' ?>">
                  <?= ucfirst($p['payment_type']) ?>
                </span>
              </td>
              <td class="px-4 py-3.5 text-right font-semibold text-gray-900"><?= fmt_money($p['total']) ?></td>
              <td class="px-4 py-3.5 text-right hidden lg:table-cell">
                <?php $bal = $p['total'] - $p['amount_paid']; ?>
                <?php if ($bal > 0): ?>
                  <span class="font-medium text-red-600"><?= fmt_money($bal) ?></span>
                <?php else: ?>
                  <span class="text-gray-400">—</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3.5">
                <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium <?= purchase_badge($p['status']) ?>">
                  <?= ucfirst($p['status']) ?>
                </span>
              </td>
              <td class="px-4 py-3.5 text-right">
                <a href="/crm/purchases/view?id=<?= $p['id'] ?>" class="text-gray-400 hover:text-amber-600 transition-colors" title="View">
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
