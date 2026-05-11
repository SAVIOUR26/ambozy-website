<?php
$page_title = 'Loans & Credit';
$active_nav = 'loans';
$header_actions = '<a href="/crm/loans/create"
   class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
  Add Loan
</a>';
require_once __DIR__ . '/../partials/header.php';

$status_filter = $_GET['status'] ?? '';
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset = ($page - 1) * $limit;

$loans      = [];
$total      = 0;
$total_outstanding = 0;

if ($pdo) { try {
    $where  = ['1=1'];
    $params = [];
    if ($status_filter) { $where[] = 'l.status = ?'; $params[] = $status_filter; }
    $sql_where = implode(' AND ', $where);

    $cnt = $pdo->prepare("SELECT COUNT(*), IFNULL(SUM(l.principal - l.amount_repaid), 0) FROM loans l WHERE $sql_where AND l.status = 'active'");
    $cnt->execute([]);
    [$total, $total_outstanding] = $cnt->fetch(PDO::FETCH_NUM);

    $cnt2 = $pdo->prepare("SELECT COUNT(*) FROM loans l WHERE $sql_where");
    $cnt2->execute($params);
    $total = (int)$cnt2->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT l.*, (l.principal - l.amount_repaid) AS outstanding
         FROM loans l
         WHERE $sql_where
         ORDER BY l.disbursement_date DESC
         LIMIT $limit OFFSET $offset"
    );
    $stmt->execute($params);
    $loans = $stmt->fetchAll();

    $outs_stmt = $pdo->query("SELECT IFNULL(SUM(principal - amount_repaid),0) FROM loans WHERE status='active'");
    $total_outstanding = (float)$outs_stmt->fetchColumn();
} catch (PDOException $e) { error_log('Loans: ' . $e->getMessage()); } }

$pages = max(1, (int)ceil($total / $limit));
?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-5">
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
    <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Total Outstanding</p>
    <p class="text-2xl font-bold text-red-600"><?= fmt_money($total_outstanding) ?></p>
  </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-5">
  <form method="GET" class="flex flex-wrap gap-3 items-end">
    <div>
      <label class="block text-xs text-gray-500 font-medium mb-1">Status</label>
      <select name="status" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
        <option value="">All</option>
        <?php foreach (['active','fully_paid','defaulted','written_off'] as $s): ?>
          <option value="<?= $s ?>" <?= $status_filter===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">Filter</button>
    <?php if ($status_filter): ?>
      <a href="/crm/loans/" class="text-sm text-gray-400 hover:text-gray-600 py-2">Clear</a>
    <?php endif; ?>
  </form>
</div>

<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="px-5 py-4 border-b border-gray-100">
    <p class="text-sm text-gray-500"><?= number_format($total) ?> loan<?= $total !== 1 ? 's' : '' ?></p>
  </div>

  <?php if (empty($loans)): ?>
    <div class="text-center py-16">
      <p class="text-4xl mb-3">🏦</p>
      <p class="text-gray-500 font-medium">No loans recorded</p>
      <a href="/crm/loans/create" class="mt-3 inline-block text-amber-600 hover:text-amber-700 text-sm font-medium">+ Record a loan</a>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Ref / Lender</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Type</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Principal</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Repaid</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Outstanding</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Due Date</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($loans as $ln): ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3.5">
                <a href="/crm/loans/view?id=<?= $ln['id'] ?>"
                   class="font-medium text-gray-900 hover:text-amber-600 transition-colors block"><?= htmlspecialchars($ln['lender']) ?></a>
                <p class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($ln['ref']) ?></p>
              </td>
              <td class="px-4 py-3.5 hidden md:table-cell text-gray-500 capitalize"><?= str_replace('_',' ',$ln['loan_type']) ?></td>
              <td class="px-4 py-3.5 text-right font-medium text-gray-900"><?= fmt_money($ln['principal']) ?></td>
              <td class="px-4 py-3.5 text-right hidden lg:table-cell text-green-600"><?= fmt_money($ln['amount_repaid']) ?></td>
              <td class="px-4 py-3.5 text-right font-semibold <?= $ln['outstanding'] > 0 ? 'text-red-600' : 'text-gray-400' ?>">
                <?= $ln['outstanding'] > 0 ? fmt_money($ln['outstanding']) : '—' ?>
              </td>
              <td class="px-4 py-3.5 hidden md:table-cell text-gray-500">
                <?= $ln['due_date'] ? date('d M Y', strtotime($ln['due_date'])) : '—' ?>
              </td>
              <td class="px-4 py-3.5">
                <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium <?= loan_badge($ln['status']) ?>">
                  <?= ucfirst(str_replace('_',' ',$ln['status'])) ?>
                </span>
              </td>
              <td class="px-4 py-3.5 text-right">
                <a href="/crm/loans/view?id=<?= $ln['id'] ?>" class="text-gray-400 hover:text-amber-600 transition-colors" title="View">
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
