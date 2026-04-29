<?php
$page_title = 'Quotations';
$active_nav = 'quotes';
$header_actions = '<a href="/crm/quotes/create.php" class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>New Quote</a>';
require_once __DIR__ . '/../partials/header.php';

$status = $_GET['status'] ?? '';
$search = trim($_GET['q'] ?? '');
$page   = max(1,(int)($_GET['page'] ?? 1));
$limit  = 20; $offset = ($page-1)*$limit;
$quotes = []; $total = 0; $counts = [];

if ($pdo) { try {
    $rows = $pdo->query("SELECT status, COUNT(*) n FROM quotations GROUP BY status")->fetchAll();
    foreach ($rows as $r) { $counts[$r['status']] = $r['n']; }
    $where = ['1=1']; $params = [];
    if ($status) { $where[] = 'q.status=?'; $params[] = $status; }
    if ($search) { $like = "%$search%"; $where[] = '(q.quote_number LIKE ? OR q.title LIKE ? OR c.name LIKE ?)'; $params = array_merge($params,[$like,$like,$like]); }
    $sw = implode(' AND ',$where);
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM quotations q JOIN clients c ON q.client_id=c.id WHERE $sw"); $cnt->execute($params); $total=(int)$cnt->fetchColumn();
    $stmt = $pdo->prepare("SELECT q.*, c.name client_name FROM quotations q JOIN clients c ON q.client_id=c.id WHERE $sw ORDER BY q.created_at DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params); $quotes = $stmt->fetchAll();
} catch (PDOException $e) { error_log('Quotes: ' . $e->getMessage()); } }
$pages = max(1,(int)ceil($total/$limit));
?>
<div class="flex gap-2 mb-5 flex-wrap">
  <a href="?" class="px-3 py-1.5 rounded-full text-xs font-medium <?= !$status?'bg-amber-500 text-white':'bg-white border border-gray-200 text-gray-600 hover:border-amber-300' ?>">All (<?= array_sum($counts) ?>)</a>
  <?php foreach(['draft','sent','accepted','rejected','expired'] as $s): ?>
    <a href="?status=<?= $s ?>" class="px-3 py-1.5 rounded-full text-xs font-medium <?= $status===$s?'bg-amber-500 text-white':'bg-white border border-gray-200 text-gray-600 hover:border-amber-300' ?>"><?= ucfirst($s) ?> (<?= $counts[$s]??0 ?>)</a>
  <?php endforeach; ?>
</div>
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-5">
  <form method="GET" class="flex gap-3 flex-wrap items-end">
    <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
    <div class="flex-1 min-w-48"><input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search quote no., title, client…" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400"></div>
    <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white text-sm font-medium px-4 py-2 rounded-lg">Search</button>
    <?php if($search): ?><a href="?status=<?= urlencode($status) ?>" class="text-sm text-gray-400 hover:text-gray-600 py-2">Clear</a><?php endif; ?>
  </form>
</div>
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="px-5 py-4 border-b border-gray-100"><p class="text-sm text-gray-500"><?= number_format($total) ?> quotation<?= $total!==1?'s':'' ?></p></div>
  <?php if(empty($quotes)): ?>
    <div class="text-center py-16"><p class="text-3xl mb-3">📄</p><p class="text-gray-500 font-medium">No quotations yet</p><a href="/crm/quotes/create.php" class="mt-3 inline-block text-amber-600 text-sm font-medium">+ Create your first quote</a></div>
  <?php else: ?>
  <div class="overflow-x-auto"><table class="w-full text-sm">
    <thead class="bg-gray-50 border-b border-gray-100"><tr>
      <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Quote</th>
      <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Client</th>
      <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Total</th>
      <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Valid Until</th>
      <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
      <th class="px-4 py-3"></th>
    </tr></thead>
    <tbody class="divide-y divide-gray-50">
      <?php foreach($quotes as $q): ?>
      <tr class="hover:bg-gray-50">
        <td class="px-5 py-3.5">
          <a href="/crm/quotes/view.php?id=<?= $q['id'] ?>" class="font-medium text-gray-900 hover:text-amber-600"><?= htmlspecialchars($q['quote_number']) ?></a>
          <p class="text-xs text-gray-400 truncate max-w-xs"><?= htmlspecialchars($q['title']) ?></p>
        </td>
        <td class="px-4 py-3.5 hidden md:table-cell text-gray-700"><?= htmlspecialchars($q['client_name']) ?></td>
        <td class="px-4 py-3.5 text-right font-semibold text-gray-800"><?= fmt_money($q['total']) ?></td>
        <td class="px-4 py-3.5 hidden lg:table-cell text-gray-500 text-xs"><?= $q['valid_until']?date('d M Y',strtotime($q['valid_until'])):'—' ?></td>
        <td class="px-4 py-3.5"><span class="text-xs px-2 py-0.5 rounded-full font-medium <?= quote_badge($q['status']) ?>"><?= ucfirst($q['status']) ?></span></td>
        <td class="px-4 py-3.5 text-right">
          <div class="flex items-center justify-end gap-2">
            <a href="/crm/quotes/view.php?id=<?= $q['id'] ?>" class="text-gray-400 hover:text-amber-600" title="View"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
            <a href="/crm/quotes/pdf.php?id=<?= $q['id'] ?>" target="_blank" class="text-gray-400 hover:text-indigo-600" title="PDF"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></a>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if($pages>1): ?>
    <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
      <p class="text-xs text-gray-400">Page <?= $page ?> of <?= $pages ?></p>
      <div class="flex gap-1">
        <?php for($i=max(1,$page-2);$i<=min($pages,$page+2);$i++): ?>
          <a href="?<?= http_build_query(array_merge($_GET,['page'=>$i])) ?>" class="w-8 h-8 flex items-center justify-center rounded text-xs font-medium <?= $i===$page?'bg-amber-500 text-white':'text-gray-500 hover:bg-gray-100' ?>"><?= $i ?></a>
        <?php endfor; ?>
      </div>
    </div>
  <?php endif; ?>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
