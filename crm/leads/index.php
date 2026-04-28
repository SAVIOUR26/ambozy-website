<?php
$page_title = 'Leads';
$active_nav = 'leads';
$header_actions = '<a href="/crm/leads/create.php"
   class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
  New Lead
</a>';
require_once __DIR__ . '/../partials/header.php';

$search = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$limit  = 25;
$offset = ($page - 1) * $limit;

$leads  = [];
$total  = 0;
$counts = [];

if ($pdo) {
    // Status counts for the filter tabs
    $rows = $pdo->query(
        "SELECT status, COUNT(*) AS n FROM leads GROUP BY status"
    )->fetchAll();
    foreach ($rows as $r) { $counts[$r['status']] = $r['n']; }

    $where  = ['1=1'];
    $params = [];
    if ($search !== '') {
        $where[]  = '(l.name LIKE ? OR l.email LIKE ? OR l.company LIKE ? OR l.ref LIKE ?)';
        $like     = "%$search%";
        $params   = array_merge($params, [$like,$like,$like,$like]);
    }
    if ($status) { $where[] = 'l.status = ?'; $params[] = $status; }

    $sql_where = implode(' AND ', $where);

    $cnt = $pdo->prepare("SELECT COUNT(*) FROM leads l WHERE $sql_where");
    $cnt->execute($params);
    $total = (int)$cnt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT l.*, c.name AS client_name
         FROM leads l
         LEFT JOIN clients c ON l.client_id = c.id
         WHERE $sql_where
         ORDER BY FIELD(l.status,'new','contacted','qualified','quoted','won','lost'), l.created_at DESC
         LIMIT $limit OFFSET $offset"
    );
    $stmt->execute($params);
    $leads = $stmt->fetchAll();
}

$pages = max(1, (int)ceil($total / $limit));
$all_statuses = ['new','contacted','qualified','quoted','won','lost'];
?>

<!-- Status tab pills -->
<div class="flex gap-2 mb-5 flex-wrap">
  <a href="?<?= http_build_query(array_merge($_GET,['status'=>'','page'=>1])) ?>"
     class="px-3 py-1.5 rounded-full text-xs font-medium transition-colors
            <?= !$status ? 'bg-amber-500 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:border-amber-300' ?>">
    All (<?= array_sum($counts) ?>)
  </a>
  <?php
  $pill_colors = ['new'=>'blue','contacted'=>'yellow','qualified'=>'purple','quoted'=>'indigo','won'=>'green','lost'=>'red'];
  foreach ($all_statuses as $s):
    $cnt_s = $counts[$s] ?? 0;
    $active_pill = $status === $s;
  ?>
    <a href="?<?= http_build_query(array_merge($_GET,['status'=>$s,'page'=>1])) ?>"
       class="px-3 py-1.5 rounded-full text-xs font-medium transition-colors
              <?= $active_pill ? 'bg-amber-500 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:border-amber-300' ?>">
      <?= ucfirst($s) ?> (<?= $cnt_s ?>)
    </a>
  <?php endforeach; ?>
</div>

<!-- Search bar -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-5">
  <form method="GET" class="flex gap-3 items-end flex-wrap">
    <input type="hidden" name="status" value="<?= htmlspecialchars($status) ?>">
    <div class="flex-1 min-w-48">
      <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
             placeholder="Search by name, email, company, ref…"
             class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
    </div>
    <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
      Search
    </button>
    <?php if ($search): ?>
      <a href="?status=<?= urlencode($status) ?>" class="text-sm text-gray-400 hover:text-gray-600 py-2">Clear</a>
    <?php endif; ?>
  </form>
</div>

<!-- Leads table -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="px-5 py-4 border-b border-gray-100">
    <p class="text-sm text-gray-500"><?= number_format($total) ?> lead<?= $total!==1?'s':'' ?></p>
  </div>

  <?php if (empty($leads)): ?>
    <div class="text-center py-16">
      <p class="text-3xl mb-3">📥</p>
      <p class="text-gray-500 font-medium">No leads found</p>
      <p class="text-gray-400 text-sm mt-1">Leads appear when clients submit the contact form, or you can add one manually.</p>
      <a href="/crm/leads/create.php" class="mt-3 inline-block text-amber-600 hover:text-amber-700 text-sm font-medium">
        + Add a lead manually
      </a>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Lead</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden md:table-cell">Service Interest</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden lg:table-cell">Source</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider hidden xl:table-cell">Date</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($leads as $lead): ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3.5">
                <div class="flex items-center gap-3">
                  <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-bold shrink-0">
                    <?= strtoupper(substr($lead['name'],0,1)) ?>
                  </div>
                  <div>
                    <a href="/crm/leads/view.php?id=<?= $lead['id'] ?>"
                       class="font-medium text-gray-900 hover:text-amber-600 transition-colors">
                      <?= htmlspecialchars($lead['name']) ?>
                    </a>
                    <p class="text-xs text-gray-400">
                      <?= htmlspecialchars($lead['ref']) ?>
                      <?php if ($lead['company']): ?>· <?= htmlspecialchars($lead['company']) ?><?php endif; ?>
                    </p>
                  </div>
                </div>
              </td>
              <td class="px-4 py-3.5 hidden md:table-cell text-gray-600">
                <?= htmlspecialchars($lead['service_interest'] ?? '—') ?>
                <?php if ($lead['budget']): ?>
                  <span class="text-xs text-gray-400 block"><?= htmlspecialchars($lead['budget']) ?></span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3.5 hidden lg:table-cell text-gray-500 capitalize">
                <?= $lead['source'] ?>
              </td>
              <td class="px-4 py-3.5">
                <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium <?= lead_badge($lead['status']) ?>">
                  <?= ucfirst($lead['status']) ?>
                </span>
                <?php if ($lead['client_id']): ?>
                  <span class="ml-1 inline-block text-xs px-1.5 py-0.5 rounded bg-green-100 text-green-700 font-medium">Converted</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3.5 hidden xl:table-cell text-gray-400 text-xs">
                <?= date('d M Y', strtotime($lead['created_at'])) ?>
              </td>
              <td class="px-4 py-3.5 text-right">
                <div class="flex items-center justify-end gap-2">
                  <a href="/crm/leads/view.php?id=<?= $lead['id'] ?>"
                     class="text-gray-400 hover:text-amber-600 transition-colors" title="View">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                  </a>
                  <?php if (!$lead['client_id']): ?>
                    <a href="/crm/leads/convert.php?id=<?= $lead['id'] ?>"
                       class="text-gray-400 hover:text-green-600 transition-colors" title="Convert to client">
                      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </a>
                  <?php endif; ?>
                </div>
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
