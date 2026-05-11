<?php
$page_title = 'Statutory Obligations';
$active_nav = 'statutory';
$header_actions = '<a href="/crm/statutory/create"
   class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
  Add Obligation
</a>';
require_once __DIR__ . '/../partials/header.php';

$type_filter   = $_GET['type']   ?? '';
$status_filter = $_GET['status'] ?? '';
$year_filter   = (int)($_GET['year'] ?? date('Y'));
$page          = max(1, (int)($_GET['page'] ?? 1));
$limit         = 25;
$offset        = ($page - 1) * $limit;

$obligations  = [];
$total        = 0;
$total_due    = 0;
$total_paid   = 0;

if ($pdo) { try {
    $where  = ['1=1'];
    $params = [];
    if ($type_filter)   { $where[] = 'o.type = ?';         $params[] = $type_filter; }
    if ($status_filter) { $where[] = 'o.status = ?';       $params[] = $status_filter; }
    if ($year_filter)   { $where[] = 'o.period_year = ?';  $params[] = $year_filter; }
    $sql_where = implode(' AND ', $where);

    $cnt = $pdo->prepare("SELECT COUNT(*), IFNULL(SUM(o.amount_due),0), IFNULL(SUM(o.amount_paid),0) FROM statutory_obligations o WHERE $sql_where");
    $cnt->execute($params);
    [$total, $total_due, $total_paid] = $cnt->fetch(PDO::FETCH_NUM);
    $total = (int)$total; $total_due = (float)$total_due; $total_paid = (float)$total_paid;

    $stmt = $pdo->prepare(
        "SELECT o.* FROM statutory_obligations o
         WHERE $sql_where
         ORDER BY o.period_year DESC, o.period_month DESC, o.type
         LIMIT $limit OFFSET $offset"
    );
    $stmt->execute($params);
    $obligations = $stmt->fetchAll();
} catch (PDOException $e) { error_log('Statutory: ' . $e->getMessage()); } }

$pages      = max(1, (int)ceil($total / $limit));
$month_names = ['','Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
$type_labels = ['paye'=>'PAYE','vat'=>'VAT','withholding_tax'=>'WHT','nssf'=>'NSSF','local_service_tax'=>'LST','other'=>'Other'];
?>

<!-- Summary -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
    <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Total Due</p>
    <p class="text-2xl font-bold text-gray-900"><?= fmt_money($total_due) ?></p>
  </div>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
    <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Total Paid</p>
    <p class="text-2xl font-bold text-green-600"><?= fmt_money($total_paid) ?></p>
  </div>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4">
    <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Outstanding</p>
    <p class="text-2xl font-bold text-red-600"><?= fmt_money(max(0, $total_due - $total_paid)) ?></p>
  </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-5">
  <form method="GET" class="flex flex-wrap gap-3 items-end">
    <div>
      <label class="block text-xs text-gray-500 font-medium mb-1">Type</label>
      <select name="type" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
        <option value="">All Types</option>
        <?php foreach ($type_labels as $v=>$l): ?>
          <option value="<?= $v ?>" <?= $type_filter===$v?'selected':'' ?>><?= $l ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label class="block text-xs text-gray-500 font-medium mb-1">Status</label>
      <select name="status" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
        <option value="">All</option>
        <option value="pending" <?= $status_filter==='pending'?'selected':'' ?>>Pending</option>
        <option value="paid"    <?= $status_filter==='paid'   ?'selected':'' ?>>Paid</option>
        <option value="partial" <?= $status_filter==='partial'?'selected':'' ?>>Partial</option>
        <option value="overdue" <?= $status_filter==='overdue'?'selected':'' ?>>Overdue</option>
      </select>
    </div>
    <div>
      <label class="block text-xs text-gray-500 font-medium mb-1">Year</label>
      <select name="year" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
        <?php for ($y = date('Y'); $y >= date('Y')-3; $y--): ?>
          <option value="<?= $y ?>" <?= $year_filter===$y?'selected':'' ?>><?= $y ?></option>
        <?php endfor; ?>
      </select>
    </div>
    <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">Filter</button>
  </form>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="px-5 py-4 border-b border-gray-100">
    <p class="text-sm text-gray-500"><?= number_format($total) ?> obligation<?= $total !== 1 ? 's' : '' ?></p>
  </div>

  <?php if (empty($obligations)): ?>
    <div class="text-center py-16">
      <p class="text-4xl mb-3">🏛️</p>
      <p class="text-gray-500 font-medium">No statutory obligations found</p>
      <p class="text-gray-400 text-sm mt-1">They're auto-created when payroll is run. You can also add manually.</p>
      <a href="/crm/statutory/create" class="mt-3 inline-block text-amber-600 hover:text-amber-700 text-sm font-medium">+ Add manually</a>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Period</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Amount Due</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Paid</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Due Date</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Reference</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($obligations as $ob): ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3.5 font-medium text-gray-900">
                <?= $month_names[$ob['period_month']] ?> <?= $ob['period_year'] ?>
              </td>
              <td class="px-4 py-3.5">
                <span class="text-xs font-semibold px-2 py-1 rounded bg-slate-100 text-slate-700">
                  <?= $type_labels[$ob['type']] ?? strtoupper($ob['type']) ?>
                </span>
              </td>
              <td class="px-4 py-3.5 text-right font-medium text-gray-900"><?= fmt_money($ob['amount_due']) ?></td>
              <td class="px-4 py-3.5 text-right text-green-600 hidden md:table-cell"><?= fmt_money($ob['amount_paid']) ?></td>
              <td class="px-4 py-3.5 hidden md:table-cell <?= $ob['due_date'] && strtotime($ob['due_date']) < time() && $ob['status'] !== 'paid' ? 'text-red-600 font-medium' : 'text-gray-500' ?>">
                <?= $ob['due_date'] ? date('d M Y', strtotime($ob['due_date'])) : '—' ?>
              </td>
              <td class="px-4 py-3.5 text-gray-400 font-mono text-xs hidden lg:table-cell"><?= htmlspecialchars($ob['reference'] ?? '—') ?></td>
              <td class="px-4 py-3.5">
                <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium <?= statutory_badge($ob['status']) ?>">
                  <?= ucfirst($ob['status']) ?>
                </span>
              </td>
              <td class="px-4 py-3.5 text-right">
                <?php if ($ob['status'] !== 'paid'): ?>
                  <a href="/crm/statutory/pay?id=<?= $ob['id'] ?>"
                     class="text-xs text-amber-600 hover:text-amber-700 font-medium">Pay</a>
                <?php endif; ?>
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
