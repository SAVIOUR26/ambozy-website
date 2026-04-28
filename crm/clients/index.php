<?php
$page_title = 'Clients';
$active_nav = 'clients';
$header_actions = '<a href="/crm/clients/create.php"
   class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
  New Client
</a>';
require_once __DIR__ . '/../partials/header.php';

// ── Filters ──────────────────────────────────────────────────
$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$type   = $_GET['type'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 20;
$offset = ($page - 1) * $limit;

$clients = [];
$total   = 0;

if ($pdo) {
    $where  = ['1=1'];
    $params = [];

    if ($search !== '') {
        $where[]  = '(c.name LIKE ? OR c.email LIKE ? OR c.company LIKE ? OR c.code LIKE ?)';
        $like     = "%$search%";
        $params   = array_merge($params, [$like, $like, $like, $like]);
    }
    if ($status) { $where[] = 'c.status = ?'; $params[] = $status; }
    if ($type)   { $where[] = 'c.type   = ?'; $params[] = $type; }

    $sql_where = implode(' AND ', $where);

    $total = (int)$pdo->prepare("SELECT COUNT(*) FROM clients c WHERE $sql_where")->execute($params) ?
             $pdo->prepare("SELECT COUNT(*) FROM clients c WHERE $sql_where")->execute($params) ? 0 : 0 : 0;

    // recount properly
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM clients c WHERE $sql_where");
    $cnt->execute($params);
    $total = (int)$cnt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT c.*,
                (SELECT COUNT(*) FROM orders   o WHERE o.client_id = c.id) AS order_count,
                (SELECT COUNT(*) FROM invoices i WHERE i.client_id = c.id AND i.status NOT IN ('paid','cancelled')) AS open_invoices,
                (SELECT IFNULL(SUM(i.total - i.amount_paid),0) FROM invoices i WHERE i.client_id = c.id AND i.status NOT IN ('paid','cancelled')) AS balance
         FROM clients c
         WHERE $sql_where
         ORDER BY c.created_at DESC
         LIMIT $limit OFFSET $offset"
    );
    $stmt->execute($params);
    $clients = $stmt->fetchAll();
}

$pages = max(1, (int)ceil($total / $limit));
?>

<!-- Search & filter bar -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-5">
  <form method="GET" class="flex flex-wrap gap-3 items-end">
    <div class="flex-1 min-w-48">
      <label class="block text-xs text-gray-500 font-medium mb-1">Search</label>
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Name, email, company…"
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
    <div>
      <label class="block text-xs text-gray-500 font-medium mb-1">Type</label>
      <select name="type" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
        <option value="">All</option>
        <option value="business"   <?= $type==='business'   ? 'selected':'' ?>>Business</option>
        <option value="individual" <?= $type==='individual' ? 'selected':'' ?>>Individual</option>
      </select>
    </div>
    <button type="submit"
            class="bg-amber-500 hover:bg-amber-400 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
      Filter
    </button>
    <?php if ($search || $status || $type): ?>
      <a href="/crm/clients/" class="text-sm text-gray-400 hover:text-gray-600 py-2">Clear</a>
    <?php endif; ?>
  </form>
</div>

<!-- Table -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
    <p class="text-sm text-gray-500">
      <?= number_format($total) ?> client<?= $total !== 1 ? 's' : '' ?>
      <?= $search ? " matching <strong>".htmlspecialchars($search)."</strong>" : '' ?>
    </p>
  </div>

  <?php if (empty($clients)): ?>
    <div class="text-center py-16">
      <p class="text-3xl mb-3">👥</p>
      <p class="text-gray-500 font-medium">No clients found</p>
      <a href="/crm/clients/create.php" class="mt-3 inline-block text-amber-600 hover:text-amber-700 text-sm font-medium">
        + Add your first client
      </a>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Client</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Contact</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Type</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Orders</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden xl:table-cell">Balance Due</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($clients as $cl): ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3.5">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center text-xs font-bold shrink-0">
                    <?= strtoupper(substr($cl['name'], 0, 1)) ?>
                  </div>
                  <div>
                    <a href="/crm/clients/view.php?id=<?= $cl['id'] ?>"
                       class="font-medium text-gray-900 hover:text-amber-600 transition-colors">
                      <?= htmlspecialchars($cl['name']) ?>
                    </a>
                    <p class="text-xs text-gray-400"><?= htmlspecialchars($cl['code']) ?></p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3.5 hidden md:table-cell">
                <p class="text-gray-700"><?= htmlspecialchars($cl['email'] ?? '—') ?></p>
                <p class="text-xs text-gray-400"><?= htmlspecialchars($cl['phone'] ?? '') ?></p>
              </td>
              <td class="px-4 py-3.5 hidden lg:table-cell">
                <span class="text-gray-600 capitalize"><?= $cl['type'] ?></span>
              </td>
              <td class="px-4 py-3.5 text-right hidden lg:table-cell">
                <span class="font-medium text-gray-700"><?= $cl['order_count'] ?></span>
              </td>
              <td class="px-4 py-3.5 text-right hidden xl:table-cell">
                <?php if ($cl['balance'] > 0): ?>
                  <span class="font-medium text-red-600"><?= fmt_money($cl['balance']) ?></span>
                <?php else: ?>
                  <span class="text-gray-400">—</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3.5">
                <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium <?= client_badge($cl['status']) ?>">
                  <?= ucfirst($cl['status']) ?>
                </span>
              </td>
              <td class="px-4 py-3.5 text-right">
                <div class="flex items-center justify-end gap-2">
                  <a href="/crm/clients/view.php?id=<?= $cl['id'] ?>"
                     class="text-gray-400 hover:text-amber-600 transition-colors" title="View">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                  </a>
                  <a href="/crm/clients/edit.php?id=<?= $cl['id'] ?>"
                     class="text-gray-400 hover:text-blue-600 transition-colors" title="Edit">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
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
