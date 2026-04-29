<?php
$page_title = 'Orders';
$active_nav = 'orders';
$header_actions = '<a href="/crm/orders/create.php" class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>New Order</a>';
require_once __DIR__ . '/../partials/header.php';

$status = $_GET['status'] ?? ''; $search = trim($_GET['q'] ?? '');
$page = max(1,(int)($_GET['page']??1)); $limit=20; $offset=($page-1)*$limit;
$orders=[]; $total=0; $counts=[];
if ($pdo) { try {
    $rows=$pdo->query("SELECT status,COUNT(*) n FROM orders GROUP BY status")->fetchAll();
    foreach($rows as $r){$counts[$r['status']]=$r['n'];}
    $where=['1=1'];$params=[];
    if($status){$where[]='o.status=?';$params[]=$status;}
    if($search){$like="%$search%";$where[]='(o.order_number LIKE ? OR o.title LIKE ? OR c.name LIKE ?)';$params=array_merge($params,[$like,$like,$like]);}
    $sw=implode(' AND ',$where);
    $cnt=$pdo->prepare("SELECT COUNT(*) FROM orders o JOIN clients c ON o.client_id=c.id WHERE $sw");$cnt->execute($params);$total=(int)$cnt->fetchColumn();
    $stmt=$pdo->prepare("SELECT o.*,c.name client_name FROM orders o JOIN clients c ON o.client_id=c.id WHERE $sw ORDER BY FIELD(o.status,'pending','in_production','ready','delivered','completed','cancelled'),o.created_at DESC LIMIT $limit OFFSET $offset");
    $stmt->execute($params);$orders=$stmt->fetchAll();
} catch (PDOException $e) { error_log('Orders: ' . $e->getMessage()); } }
$pages=max(1,(int)ceil($total/$limit));
$all_statuses=['pending','in_production','ready','delivered','completed','cancelled'];
?>
<div class="flex gap-2 mb-5 flex-wrap">
  <a href="?" class="px-3 py-1.5 rounded-full text-xs font-medium <?= !$status?'bg-amber-500 text-white':'bg-white border border-gray-200 text-gray-600 hover:border-amber-300' ?>">All (<?=array_sum($counts)?>)</a>
  <?php foreach($all_statuses as $s): ?>
    <a href="?status=<?=$s?>" class="px-3 py-1.5 rounded-full text-xs font-medium <?= $status===$s?'bg-amber-500 text-white':'bg-white border border-gray-200 text-gray-600 hover:border-amber-300' ?>"><?=ucfirst(str_replace('_',' ',$s))?> (<?=$counts[$s]??0?>)</a>
  <?php endforeach; ?>
</div>
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-5">
  <form method="GET" class="flex gap-3 flex-wrap items-end">
    <input type="hidden" name="status" value="<?=htmlspecialchars($status)?>">
    <div class="flex-1 min-w-48"><input type="text" name="q" value="<?=htmlspecialchars($search)?>" placeholder="Search order no., title, client…" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400"></div>
    <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white text-sm font-medium px-4 py-2 rounded-lg">Search</button>
    <?php if($search): ?><a href="?status=<?=urlencode($status)?>" class="text-sm text-gray-400 hover:text-gray-600 py-2">Clear</a><?php endif; ?>
  </form>
</div>
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="px-5 py-4 border-b border-gray-100"><p class="text-sm text-gray-500"><?=number_format($total)?> order<?=$total!==1?'s':''?></p></div>
  <?php if(empty($orders)): ?>
    <div class="text-center py-16"><p class="text-3xl mb-3">⚙️</p><p class="text-gray-500 font-medium">No orders yet</p><a href="/crm/orders/create.php" class="mt-3 inline-block text-amber-600 text-sm font-medium">+ Book first order</a></div>
  <?php else: ?>
  <div class="overflow-x-auto"><table class="w-full text-sm">
    <thead class="bg-gray-50 border-b border-gray-100"><tr>
      <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Order</th>
      <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase hidden md:table-cell">Client</th>
      <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Due Date</th>
      <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
      <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase hidden lg:table-cell">Priority</th>
      <th class="px-4 py-3"></th>
    </tr></thead>
    <tbody class="divide-y divide-gray-50">
      <?php foreach($orders as $o): ?>
      <tr class="hover:bg-gray-50">
        <td class="px-5 py-3.5">
          <a href="/crm/orders/view.php?id=<?=$o['id']?>" class="font-medium text-gray-900 hover:text-amber-600"><?=htmlspecialchars($o['order_number'])?></a>
          <p class="text-xs text-gray-400 truncate max-w-xs"><?=htmlspecialchars($o['title'])?></p>
        </td>
        <td class="px-4 py-3.5 hidden md:table-cell text-gray-700"><?=htmlspecialchars($o['client_name'])?></td>
        <td class="px-4 py-3.5 hidden lg:table-cell text-gray-500 text-xs"><?=$o['due_date']?date('d M Y',strtotime($o['due_date'])):'—'?></td>
        <td class="px-4 py-3.5"><span class="text-xs px-2 py-0.5 rounded-full font-medium <?=order_badge($o['status'])?>"><?=ucfirst(str_replace('_',' ',$o['status']))?></span></td>
        <td class="px-4 py-3.5 hidden lg:table-cell">
          <?php if($o['priority']==='urgent'): ?><span class="text-xs px-2 py-0.5 rounded-full bg-red-100 text-red-700 font-medium">Urgent</span><?php else: ?><span class="text-xs text-gray-400">Normal</span><?php endif; ?>
        </td>
        <td class="px-4 py-3.5 text-right">
          <a href="/crm/orders/view.php?id=<?=$o['id']?>" class="text-gray-400 hover:text-amber-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table></div>
  <?php if($pages>1): ?>
    <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between">
      <p class="text-xs text-gray-400">Page <?=$page?> of <?=$pages?></p>
      <div class="flex gap-1"><?php for($i=max(1,$page-2);$i<=min($pages,$page+2);$i++): ?><a href="?<?=http_build_query(array_merge($_GET,['page'=>$i]))?>" class="w-8 h-8 flex items-center justify-center rounded text-xs font-medium <?=$i===$page?'bg-amber-500 text-white':'text-gray-500 hover:bg-gray-100'?>"><?=$i?></a><?php endfor; ?></div>
    </div>
  <?php endif; ?>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
