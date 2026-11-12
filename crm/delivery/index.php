<?php
require_once __DIR__ . '/../partials/bootstrap.php';
require_once __DIR__ . '/../../includes/crm_delivery.php';

$page_title = 'Delivery Notes';
$active_nav = 'delivery';
$header_actions = '<a href="/crm/delivery/create" class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>New Delivery Note</a>';
require_once __DIR__ . '/../partials/header.php';

$status = $_GET['status'] ?? ''; $search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1)); $limit = 20; $offset = ($page - 1) * $limit;
$notes = []; $total = 0; $counts = [];

if ($pdo) { try {
    ensure_delivery_tables($pdo);
    foreach ($pdo->query("SELECT status, COUNT(*) n FROM delivery_notes GROUP BY status")->fetchAll() as $r) $counts[$r['status']] = $r['n'];
    $where = ['1=1']; $params = [];
    if ($status) { $where[] = 'd.status=?'; $params[] = $status; }
    if ($search) { $like = "%$search%"; $where[] = '(d.dn_number LIKE ? OR d.title LIKE ? OR c.name LIKE ?)'; array_push($params, $like, $like, $like); }
    $sw = implode(' AND ', $where);
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM delivery_notes d JOIN clients c ON d.client_id=c.id WHERE $sw"); $cnt->execute($params); $total = (int)$cnt->fetchColumn();
    $stmt = $pdo->prepare(
        "SELECT d.*, c.name client_name, q.quote_number, i.invoice_number,
                (SELECT COUNT(*) FROM delivery_note_items x WHERE x.delivery_note_id=d.id) item_count
         FROM delivery_notes d JOIN clients c ON d.client_id=c.id
         LEFT JOIN quotations q ON d.quotation_id=q.id
         LEFT JOIN invoices i ON d.invoice_id=i.id
         WHERE $sw ORDER BY d.created_at DESC LIMIT $limit OFFSET $offset"
    );
    $stmt->execute($params); $notes = $stmt->fetchAll();
} catch (PDOException $e) { error_log('Delivery list: ' . $e->getMessage()); } }
$pages = max(1, (int)ceil($total / $limit));
?>
<div class="flex gap-2 mb-5 flex-wrap">
  <a href="?" class="px-3 py-1.5 rounded-full text-xs font-medium <?= !$status ? 'bg-amber-500 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:border-amber-300' ?>">All (<?= array_sum($counts) ?>)</a>
  <?php foreach (['draft','dispatched','delivered','cancelled'] as $s): ?>
    <a href="?status=<?= $s ?>" class="px-3 py-1.5 rounded-full text-xs font-medium <?= $status === $s ? 'bg-amber-500 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:border-amber-300' ?>"><?= ucfirst($s) ?> (<?= $counts[$s] ?? 0 ?>)</a>
  <?php endforeach; ?>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-5">
  <form method="GET" class="flex gap-3 flex-wrap items-end">
    <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
    <div class="flex-1 min-w-48"><input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search DN no., description, client…" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400"></div>
    <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white text-sm font-medium px-4 py-2 rounded-lg">Search</button>
    <?php if ($search): ?><a href="?status=<?= urlencode($status) ?>" class="text-sm text-gray-400 hover:text-gray-600 py-2">Clear</a><?php endif; ?>
  </form>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="px-5 py-4 border-b border-gray-100"><p class="text-sm text-gray-500"><?= number_format($total) ?> delivery note<?= $total !== 1 ? 's' : '' ?></p></div>
  <?php if (empty($notes)): ?>
    <div class="text-center py-16"><p class="text-3xl mb-3">🚚</p><p class="text-gray-500 font-medium">No delivery notes yet</p>
      <p class="text-sm text-gray-400 mt-1">Create one from an approved quotation or an invoice, or start from scratch.</p>
      <a href="/crm/delivery/create" class="mt-3 inline-block text-amber-600 text-sm font-medium">+ New delivery note</a></div>
  <?php else: ?>
  <div class="overflow-x-auto"><table class="w-full text-sm">
    <thead class="bg-gray-50 border-b border-gray-100"><tr>
      <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Delivery Note</th>
      <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Client</th>
      <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Source</th>
      <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Date</th>
      <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Items</th>
      <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
      <th class="px-4 py-3"></th>
    </tr></thead>
    <tbody class="divide-y divide-gray-50">
      <?php foreach ($notes as $n): ?>
      <tr class="hover:bg-gray-50">
        <td class="px-5 py-3.5">
          <a href="/crm/delivery/view?id=<?= $n['id'] ?>" class="font-medium text-gray-900 hover:text-amber-600 font-mono"><?= htmlspecialchars($n['dn_number']) ?></a>
          <p class="text-xs text-gray-400 truncate max-w-xs"><?= htmlspecialchars($n['title']) ?></p>
        </td>
        <td class="px-4 py-3.5 hidden md:table-cell text-gray-700"><?= htmlspecialchars($n['client_name']) ?></td>
        <td class="px-4 py-3.5 hidden lg:table-cell text-xs text-gray-500 font-mono"><?= htmlspecialchars($n['quote_number'] ?: ($n['invoice_number'] ?: '—')) ?></td>
        <td class="px-4 py-3.5 hidden md:table-cell text-gray-500 text-xs"><?= date('d M Y', strtotime($n['delivery_date'])) ?></td>
        <td class="px-4 py-3.5 hidden lg:table-cell text-center text-gray-500"><?= (int)$n['item_count'] ?></td>
        <td class="px-4 py-3.5"><span class="text-xs px-2 py-0.5 rounded-full font-medium <?= delivery_badge($n['status']) ?>"><?= ucfirst($n['status']) ?></span></td>
        <td class="px-4 py-3.5 text-right whitespace-nowrap">
          <a href="/crm/delivery/pdf?id=<?= $n['id'] ?>&download=1" target="_blank" class="inline-flex p-1.5 text-gray-400 hover:text-indigo-600" title="Print / PDF">🖨</a>
          <?= row_actions('/crm/delivery', (int)$n['id'], 'Delete delivery note ' . $n['dn_number'] . '? This cannot be undone.') ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if ($pages > 1): ?>
    <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
      <p class="text-xs text-gray-400">Page <?= $page ?> of <?= $pages ?></p>
      <div class="flex gap-1">
        <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
          <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="w-8 h-8 flex items-center justify-center rounded text-xs font-medium <?= $i === $page ? 'bg-amber-500 text-white' : 'text-gray-500 hover:bg-gray-100' ?>"><?= $i ?></a>
        <?php endfor; ?>
      </div>
    </div>
  <?php endif; ?>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
