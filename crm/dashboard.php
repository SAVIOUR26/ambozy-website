<?php
$page_title = 'Dashboard';
$active_nav = 'dashboard';
require_once __DIR__ . '/partials/header.php';

// ── KPI queries ──────────────────────────────────────────────
$stats = [
    'clients'       => 0,
    'new_leads'     => 0,
    'active_orders' => 0,
    'revenue_mtd'   => 0,
    'outstanding'   => 0,
    'quotes_sent'   => 0,
];
$recent_leads    = [];
$recent_orders   = [];
$recent_payments = [];

if ($pdo) {
    $stats['clients']       = (int)$pdo->query("SELECT COUNT(*) FROM clients WHERE status='active'")->fetchColumn();
    $stats['new_leads']     = (int)$pdo->query("SELECT COUNT(*) FROM leads WHERE status='new'")->fetchColumn();
    $stats['active_orders'] = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status IN ('pending','in_production','ready')")->fetchColumn();
    $stats['revenue_mtd']   = (float)$pdo->query("SELECT IFNULL(SUM(amount),0) FROM payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())")->fetchColumn();
    $stats['outstanding']   = (float)$pdo->query("SELECT IFNULL(SUM(total - amount_paid),0) FROM invoices WHERE status NOT IN ('paid','cancelled')")->fetchColumn();
    $stats['quotes_sent']   = (int)$pdo->query("SELECT COUNT(*) FROM quotations WHERE status='sent' AND MONTH(created_at)=MONTH(CURDATE())")->fetchColumn();

    $recent_leads = $pdo->query(
        "SELECT l.*, c.name AS client_name
         FROM leads l LEFT JOIN clients c ON l.client_id = c.id
         ORDER BY l.created_at DESC LIMIT 6"
    )->fetchAll();

    $recent_orders = $pdo->query(
        "SELECT o.*, c.name AS client_name
         FROM orders o JOIN clients c ON o.client_id = c.id
         WHERE o.status NOT IN ('completed','cancelled')
         ORDER BY o.created_at DESC LIMIT 6"
    )->fetchAll();

    $recent_payments = $pdo->query(
        "SELECT p.*, c.name AS client_name, i.invoice_number
         FROM payments p
         JOIN clients c  ON p.client_id  = c.id
         JOIN invoices i ON p.invoice_id = i.id
         ORDER BY p.created_at DESC LIMIT 5"
    )->fetchAll();
}
?>

<!-- KPI Cards -->
<div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4 mb-6">
  <?php
  $cards = [
    ['label'=>'Active Clients',    'value'=> $stats['clients'],       'icon'=>'👥', 'color'=>'bg-blue-50 text-blue-600',   'fmt'=>'int'],
    ['label'=>'New Leads',         'value'=> $stats['new_leads'],     'icon'=>'📥', 'color'=>'bg-amber-50 text-amber-600', 'fmt'=>'int'],
    ['label'=>'Active Orders',     'value'=> $stats['active_orders'], 'icon'=>'⚙️', 'color'=>'bg-purple-50 text-purple-600','fmt'=>'int'],
    ['label'=>'Revenue (MTD)',     'value'=> $stats['revenue_mtd'],   'icon'=>'💰', 'color'=>'bg-green-50 text-green-600',  'fmt'=>'money'],
    ['label'=>'Outstanding',       'value'=> $stats['outstanding'],   'icon'=>'⚠️', 'color'=>'bg-red-50 text-red-600',     'fmt'=>'money'],
    ['label'=>'Quotes Sent (MTD)', 'value'=> $stats['quotes_sent'],   'icon'=>'📄', 'color'=>'bg-indigo-50 text-indigo-600','fmt'=>'int'],
  ];
  foreach ($cards as $c):
    $display = $c['fmt'] === 'money' ? fmt_money($c['value']) : number_format($c['value']);
  ?>
  <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
    <div class="flex items-center gap-2 mb-2">
      <span class="text-lg"><?= $c['icon'] ?></span>
      <span class="text-xs text-gray-400 font-medium"><?= $c['label'] ?></span>
    </div>
    <p class="text-xl font-bold text-gray-900"><?= $display ?></p>
  </div>
  <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

  <!-- Recent Leads -->
  <div class="xl:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800">Recent Leads</h2>
      <a href="/crm/leads/" class="text-xs text-amber-600 hover:text-amber-700 font-medium">View all →</a>
    </div>
    <?php if (empty($recent_leads)): ?>
      <p class="text-gray-400 text-sm text-center py-10">No leads yet. They'll appear here when clients submit the contact form.</p>
    <?php else: ?>
      <div class="divide-y divide-gray-50">
        <?php foreach ($recent_leads as $lead): ?>
          <a href="/crm/leads/view.php?id=<?= $lead['id'] ?>"
             class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50 transition-colors">
            <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center text-xs font-bold shrink-0">
              <?= strtoupper(substr($lead['name'], 0, 1)) ?>
            </div>
            <div class="flex-1 min-w-0">
              <p class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($lead['name']) ?></p>
              <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($lead['service_interest'] ?? '—') ?></p>
            </div>
            <div class="text-right shrink-0">
              <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium <?= lead_badge($lead['status']) ?>">
                <?= ucfirst($lead['status']) ?>
              </span>
              <p class="text-xs text-gray-400 mt-0.5"><?= date('d M', strtotime($lead['created_at'])) ?></p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Right column -->
  <div class="space-y-5">

    <!-- Active Orders -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Active Orders</h2>
        <a href="/crm/orders/" class="text-xs text-amber-600 hover:text-amber-700 font-medium">View all →</a>
      </div>
      <?php if (empty($recent_orders)): ?>
        <p class="text-gray-400 text-sm text-center py-8">No active orders.</p>
      <?php else: ?>
        <div class="divide-y divide-gray-50">
          <?php foreach ($recent_orders as $ord): ?>
            <a href="/crm/orders/view.php?id=<?= $ord['id'] ?>"
               class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition-colors">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($ord['order_number']) ?></p>
                <p class="text-xs text-gray-400 truncate"><?= htmlspecialchars($ord['client_name']) ?></p>
              </div>
              <span class="text-xs px-2 py-0.5 rounded-full font-medium shrink-0 <?= order_badge($ord['status']) ?>">
                <?= ucfirst(str_replace('_', ' ', $ord['status'])) ?>
              </span>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Recent Payments -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
        <h2 class="font-semibold text-gray-800">Recent Payments</h2>
        <a href="/crm/invoices/" class="text-xs text-amber-600 hover:text-amber-700 font-medium">Invoices →</a>
      </div>
      <?php if (empty($recent_payments)): ?>
        <p class="text-gray-400 text-sm text-center py-8">No payments recorded.</p>
      <?php else: ?>
        <div class="divide-y divide-gray-50">
          <?php foreach ($recent_payments as $pay): ?>
            <div class="flex items-center gap-3 px-5 py-3">
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 truncate"><?= htmlspecialchars($pay['client_name']) ?></p>
                <p class="text-xs text-gray-400"><?= htmlspecialchars($pay['invoice_number']) ?></p>
              </div>
              <div class="text-right shrink-0">
                <p class="text-sm font-semibold text-green-600"><?= fmt_money($pay['amount']) ?></p>
                <p class="text-xs text-gray-400"><?= date('d M', strtotime($pay['payment_date'])) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/partials/footer.php'; ?>
