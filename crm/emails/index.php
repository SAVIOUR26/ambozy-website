<?php
$page_title = 'Email Log';
$active_nav = 'emails';
require_once __DIR__ . '/../partials/header.php';

$type_filter   = $_GET['type']   ?? '';
$status_filter = $_GET['status'] ?? '';
$search        = trim($_GET['q'] ?? '');
$page          = max(1, (int)($_GET['page'] ?? 1));
$per_page      = 25;
$offset        = ($page - 1) * $per_page;

$emails = []; $total = 0;

if ($pdo) {
    $where = ['1=1']; $params = [];

    if ($type_filter) { $where[] = 'type=?'; $params[] = $type_filter; }
    if ($status_filter) { $where[] = 'status=?'; $params[] = $status_filter; }
    if ($search) {
        $where[] = '(recipient_email LIKE ? OR recipient_name LIKE ? OR subject LIKE ?)';
        $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%";
    }

    $w = implode(' AND ', $where);
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM email_logs WHERE $w");
    $cnt->execute($params); $total = (int)$cnt->fetchColumn();

    $q = $pdo->prepare("SELECT * FROM email_logs WHERE $w ORDER BY sent_at DESC LIMIT $per_page OFFSET $offset");
    $q->execute($params); $emails = $q->fetchAll();
}

$pages = (int)ceil($total / $per_page);

$type_labels = [
    'invoice'  => 'Invoice',
    'receipt'  => 'Receipt',
    'quote'    => 'Quotation',
    'order'    => 'Order Update',
    'lead'     => 'Lead Ack.',
    'general'  => 'General',
];

function email_type_badge(string $t): string {
    return match($t) {
        'invoice' => 'bg-blue-100 text-blue-700',
        'receipt' => 'bg-green-100 text-green-700',
        'quote'   => 'bg-amber-100 text-amber-700',
        'order'   => 'bg-purple-100 text-purple-700',
        'lead'    => 'bg-cyan-100 text-cyan-700',
        default   => 'bg-gray-100 text-gray-600',
    };
}
?>

<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
  <h1 class="text-lg font-bold text-gray-800">Email Log</h1>
  <p class="text-sm text-gray-400"><?= number_format($total) ?> email<?= $total != 1 ? 's' : '' ?> sent</p>
</div>

<!-- Filters -->
<form method="GET" class="bg-white rounded-xl border border-gray-100 shadow-sm p-4 mb-5 flex flex-wrap gap-3 items-end">
  <div class="flex-1 min-w-48">
    <label class="block text-xs font-medium text-gray-500 mb-1">Search</label>
    <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Recipient, subject…"
           class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
  </div>
  <div>
    <label class="block text-xs font-medium text-gray-500 mb-1">Type</label>
    <select name="type" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
      <option value="">All types</option>
      <?php foreach ($type_labels as $v => $l): ?>
        <option value="<?= $v ?>" <?= $type_filter === $v ? 'selected' : '' ?>><?= $l ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div>
    <label class="block text-xs font-medium text-gray-500 mb-1">Status</label>
    <select name="status" class="border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
      <option value="">All</option>
      <option value="sent" <?= $status_filter === 'sent' ? 'selected' : '' ?>>Sent</option>
      <option value="failed" <?= $status_filter === 'failed' ? 'selected' : '' ?>>Failed</option>
    </select>
  </div>
  <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold px-4 py-2 rounded-lg">Filter</button>
  <?php if ($search || $type_filter || $status_filter): ?>
    <a href="/crm/emails/" class="text-sm text-gray-400 hover:text-gray-600 py-2">Clear</a>
  <?php endif; ?>
</form>

<!-- Table -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <?php if (empty($emails)): ?>
    <div class="text-center py-16">
      <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
      </svg>
      <p class="text-gray-400 text-sm">No emails found</p>
    </div>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Date / Time</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Type</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Recipient</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Subject</th>
            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Status</th>
            <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Related</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($emails as $em): ?>
          <tr class="hover:bg-gray-50 transition-colors" x-data="{ expanded: false }">
            <td class="px-5 py-3 text-xs text-gray-400 whitespace-nowrap">
              <?= date('d M Y', strtotime($em['sent_at'])) ?><br>
              <span class="text-gray-300"><?= date('H:i', strtotime($em['sent_at'])) ?></span>
            </td>
            <td class="px-4 py-3">
              <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= email_type_badge($em['type']) ?>">
                <?= $type_labels[$em['type']] ?? ucfirst($em['type']) ?>
              </span>
            </td>
            <td class="px-4 py-3">
              <p class="font-medium text-gray-800"><?= htmlspecialchars($em['recipient_name'] ?: '—') ?></p>
              <p class="text-xs text-gray-400"><?= htmlspecialchars($em['recipient_email']) ?></p>
            </td>
            <td class="px-4 py-3 text-gray-700 max-w-xs truncate" title="<?= htmlspecialchars($em['subject']) ?>">
              <?= htmlspecialchars($em['subject']) ?>
            </td>
            <td class="px-4 py-3 text-center">
              <?php if ($em['status'] === 'sent'): ?>
                <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-700 bg-green-100 px-2 py-0.5 rounded-full">
                  <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                  Sent
                </span>
              <?php else: ?>
                <span class="text-xs font-semibold text-red-700 bg-red-100 px-2 py-0.5 rounded-full">Failed</span>
              <?php endif; ?>
            </td>
            <td class="px-4 py-3 text-center">
              <?php if ($em['related_type'] && $em['related_id']): ?>
                <?php
                $link = match($em['related_type']) {
                    'invoice'   => "/crm/invoices/view.php?id={$em['related_id']}",
                    'quotation' => "/crm/quotes/view.php?id={$em['related_id']}",
                    'order'     => "/crm/orders/view.php?id={$em['related_id']}",
                    'lead'      => "/crm/leads/view.php?id={$em['related_id']}",
                    default     => null,
                };
                ?>
                <?php if ($link): ?>
                  <a href="<?= $link ?>" class="text-xs text-amber-600 hover:text-amber-700 font-medium capitalize">
                    <?= $em['related_type'] ?> #<?= $em['related_id'] ?>
                  </a>
                <?php else: ?>
                  <span class="text-xs text-gray-400 capitalize"><?= $em['related_type'] ?></span>
                <?php endif; ?>
              <?php else: ?>
                <span class="text-gray-300">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="px-5 py-4 border-t border-gray-100 flex items-center justify-between text-sm">
      <p class="text-gray-400">
        Showing <?= number_format($offset + 1) ?>–<?= number_format(min($offset + $per_page, $total)) ?> of <?= number_format($total) ?>
      </p>
      <div class="flex gap-1">
        <?php
        $qs = http_build_query(array_filter(['q'=>$search,'type'=>$type_filter,'status'=>$status_filter]));
        $qs = $qs ? "&$qs" : '';
        ?>
        <?php if ($page > 1): ?>
          <a href="?page=<?= $page-1 ?><?= $qs ?>" class="px-3 py-1.5 border border-gray-200 rounded-lg text-gray-500 hover:border-amber-400 hover:text-amber-600">← Prev</a>
        <?php endif; ?>
        <?php for ($p = max(1,$page-2); $p <= min($pages,$page+2); $p++): ?>
          <a href="?page=<?= $p ?><?= $qs ?>"
             class="px-3 py-1.5 border rounded-lg <?= $p===$page?'bg-amber-500 text-white border-amber-500':'border-gray-200 text-gray-500 hover:border-amber-400' ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>
        <?php if ($page < $pages): ?>
          <a href="?page=<?= $page+1 ?><?= $qs ?>" class="px-3 py-1.5 border border-gray-200 rounded-lg text-gray-500 hover:border-amber-400 hover:text-amber-600">Next →</a>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
