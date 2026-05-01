<?php
$page_title = 'Price Catalog';
$active_nav = 'catalog';
$header_actions = '<a href="/crm/catalog/create" class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>Add Item</a>';
require_once __DIR__ . '/../partials/header.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id']) && $pdo) {
    $pdo->prepare("DELETE FROM catalog_items WHERE id=?")->execute([(int)$_POST['delete_id']]);
    flash('success','Item removed.'); redirect('/crm/catalog/');
}

$search = trim($_GET['q'] ?? ''); $category = trim($_GET['cat'] ?? '');
$items = []; $cats = [];
if ($pdo) {
    $cats = $pdo->query("SELECT DISTINCT category FROM catalog_items WHERE category IS NOT NULL ORDER BY category")->fetchAll(PDO::FETCH_COLUMN);
    $where = ['1=1']; $params = [];
    if ($search) { $where[] = '(name LIKE ? OR code LIKE ? OR category LIKE ?)'; $like = "%$search%"; $params = array_merge($params,[$like,$like,$like]); }
    if ($category) { $where[] = 'category = ?'; $params[] = $category; }
    $stmt = $pdo->prepare("SELECT * FROM catalog_items WHERE ".implode(' AND ',$where)." ORDER BY category, name");
    $stmt->execute($params); $items = $stmt->fetchAll();
}
$grouped = [];
foreach ($items as $item) { $grouped[$item['category'] ?? 'Uncategorised'][] = $item; }
?>
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-5">
  <form method="GET" class="flex gap-3 flex-wrap items-end">
    <div class="flex-1 min-w-40">
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search items…"
             class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
    </div>
    <select name="cat" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
      <option value="">All categories</option>
      <?php foreach ($cats as $c): ?><option value="<?= htmlspecialchars($c) ?>" <?= $category===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option><?php endforeach; ?>
    </select>
    <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white text-sm font-medium px-4 py-2 rounded-lg">Filter</button>
    <?php if ($search||$category): ?><a href="/crm/catalog/" class="text-sm text-gray-400 hover:text-gray-600 py-2">Clear</a><?php endif; ?>
  </form>
</div>
<?php if (empty($items)): ?>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm text-center py-16">
    <p class="text-3xl mb-3">📋</p>
    <p class="text-gray-500 font-medium">No catalog items yet</p>
    <p class="text-gray-400 text-sm mt-1">Add your services and products to use them in quotes and invoices.</p>
    <a href="/crm/catalog/create" class="mt-4 inline-block text-amber-600 hover:text-amber-700 text-sm font-medium">+ Add first item</a>
  </div>
<?php else: ?>
  <?php foreach ($grouped as $cat => $cat_items): ?>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden mb-4">
    <div class="px-5 py-3.5 border-b border-gray-100 bg-gray-50">
      <h3 class="text-sm font-semibold text-gray-600"><?= htmlspecialchars($cat) ?> (<?= count($cat_items) ?>)</h3>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead><tr class="border-b border-gray-50">
          <th class="text-left px-5 py-3 text-xs font-semibold text-gray-400 uppercase">Name</th>
          <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400 uppercase hidden md:table-cell">Code</th>
          <th class="text-right px-4 py-3 text-xs font-semibold text-gray-400 uppercase">Unit Price (UGX)</th>
          <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400 uppercase hidden lg:table-cell">Unit</th>
          <th class="text-left px-4 py-3 text-xs font-semibold text-gray-400 uppercase">Status</th>
          <th class="px-4 py-3"></th>
        </tr></thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($cat_items as $it): ?>
          <tr class="hover:bg-gray-50">
            <td class="px-5 py-3.5">
              <p class="font-medium text-gray-800"><?= htmlspecialchars($it['name']) ?></p>
              <?php if ($it['description']): ?><p class="text-xs text-gray-400 truncate max-w-xs"><?= htmlspecialchars($it['description']) ?></p><?php endif; ?>
            </td>
            <td class="px-4 py-3.5 hidden md:table-cell font-mono text-xs text-gray-500"><?= htmlspecialchars($it['code']??'—') ?></td>
            <td class="px-4 py-3.5 text-right font-semibold text-gray-800"><?= number_format($it['unit_price'],0) ?></td>
            <td class="px-4 py-3.5 hidden lg:table-cell text-gray-500"><?= htmlspecialchars($it['unit']) ?></td>
            <td class="px-4 py-3.5">
              <span class="text-xs px-2 py-0.5 rounded-full font-medium <?= $it['is_active']?'bg-green-100 text-green-700':'bg-gray-100 text-gray-400' ?>">
                <?= $it['is_active']?'Active':'Inactive' ?>
              </span>
            </td>
            <td class="px-4 py-3.5 text-right">
              <div class="flex items-center justify-end gap-2">
                <a href="/crm/catalog/edit?id=<?= $it['id'] ?>" class="text-gray-400 hover:text-blue-600">
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </a>
                <form method="POST" onsubmit="return confirm('Delete this item?')">
                  <input type="hidden" name="delete_id" value="<?= $it['id'] ?>">
                  <button type="submit" class="text-gray-400 hover:text-red-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                  </button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
