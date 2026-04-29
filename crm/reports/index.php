<?php
$page_title = 'Reports';
$active_nav = 'reports';
require_once __DIR__ . '/../partials/header.php';

$period = $_GET['period'] ?? 'this_month';
$now    = new DateTimeImmutable();

switch ($period) {
    case 'last_month':
        $start = $now->modify('first day of last month')->format('Y-m-d');
        $end   = $now->modify('last day of last month')->format('Y-m-d');
        $label = 'Last Month';
        break;
    case 'this_quarter':
        $qm    = (int)ceil((int)$now->format('n') / 3) * 3 - 2;
        $start = $now->setDate((int)$now->format('Y'), $qm, 1)->format('Y-m-d');
        $end   = $now->setDate((int)$now->format('Y'), $qm + 2, 1)->modify('last day of this month')->format('Y-m-d');
        $label = 'This Quarter';
        break;
    case 'this_year':
        $start = $now->format('Y') . '-01-01';
        $end   = $now->format('Y') . '-12-31';
        $label = 'This Year';
        break;
    case 'last_30':
        $start = $now->modify('-30 days')->format('Y-m-d');
        $end   = date('Y-m-d');
        $label = 'Last 30 Days';
        break;
    default: // this_month
        $start = $now->format('Y-m-01');
        $end   = $now->format('Y-m-t');
        $label = 'This Month';
        $period = 'this_month';
}

// ---- Queries ----
$revenue_total   = 0;
$revenue_paid    = 0;
$revenue_pending = 0;
$invoices_count  = 0;
$payments_count  = 0;
$new_clients     = 0;
$orders_completed= 0;
$top_clients     = [];
$by_status       = [];
$monthly_revenue = [];
$top_services    = [];
$recent_payments = [];
$overdue_list    = [];

if ($pdo) {
    // KPIs
    $kpi = $pdo->prepare(
        "SELECT
           COUNT(*) invoices_count,
           COALESCE(SUM(total),0) revenue_total,
           COALESCE(SUM(amount_paid),0) revenue_paid
         FROM invoices
         WHERE DATE(created_at) BETWEEN ? AND ? AND status != 'cancelled'"
    );
    $kpi->execute([$start, $end]);
    $krow = $kpi->fetch();
    $revenue_total   = (float)$krow['revenue_total'];
    $revenue_paid    = (float)$krow['revenue_paid'];
    $revenue_pending = max(0, $revenue_total - $revenue_paid);
    $invoices_count  = (int)$krow['invoices_count'];

    $payments_count = (int)$pdo->prepare(
        "SELECT COUNT(*) FROM payments WHERE DATE(payment_date) BETWEEN ? AND ?"
    )->execute([$start, $end]) ? $pdo->query("SELECT FOUND_ROWS()")->fetchColumn() : 0;
    $pc = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE DATE(payment_date) BETWEEN ? AND ?");
    $pc->execute([$start, $end]); $payments_count = (int)$pc->fetchColumn();

    $nc = $pdo->prepare("SELECT COUNT(*) FROM clients WHERE DATE(created_at) BETWEEN ? AND ?");
    $nc->execute([$start, $end]); $new_clients = (int)$nc->fetchColumn();

    $oc = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE status='completed' AND DATE(updated_at) BETWEEN ? AND ?");
    $oc->execute([$start, $end]); $orders_completed = (int)$oc->fetchColumn();

    // Monthly revenue (last 12 months)
    $mr = $pdo->query(
        "SELECT DATE_FORMAT(payment_date,'%Y-%m') ym, SUM(amount) total
         FROM payments
         WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH)
         GROUP BY ym ORDER BY ym"
    );
    $monthly_revenue = $mr ? $mr->fetchAll() : [];

    // Top clients by revenue (in period)
    $tc = $pdo->prepare(
        "SELECT c.name, c.company, c.id,
                COALESCE(SUM(p.amount),0) paid
         FROM payments p
         JOIN invoices i ON p.invoice_id=i.id
         JOIN clients c ON i.client_id=c.id
         WHERE DATE(p.payment_date) BETWEEN ? AND ?
         GROUP BY c.id ORDER BY paid DESC LIMIT 8"
    );
    $tc->execute([$start, $end]); $top_clients = $tc->fetchAll();

    // Invoices by status (all time, not just period — for aging)
    $bs = $pdo->query(
        "SELECT status, COUNT(*) cnt, COALESCE(SUM(total-amount_paid),0) outstanding
         FROM invoices WHERE status NOT IN ('cancelled')
         GROUP BY status"
    );
    $by_status = $bs ? $bs->fetchAll() : [];

    // Top services/catalog items by revenue
    $ts = $pdo->prepare(
        "SELECT COALESCE(ci.name, ii.description) item_name,
                COALESCE(ci.category,'Custom') category,
                COUNT(*) times_sold,
                SUM(ii.total) revenue
         FROM invoice_items ii
         LEFT JOIN catalog_items ci ON ii.catalog_item_id=ci.id
         JOIN invoices inv ON ii.invoice_id=inv.id
         WHERE DATE(inv.created_at) BETWEEN ? AND ? AND inv.status != 'cancelled'
         GROUP BY item_name, category ORDER BY revenue DESC LIMIT 10"
    );
    $ts->execute([$start, $end]); $top_services = $ts->fetchAll();

    // Recent payments
    $rp = $pdo->prepare(
        "SELECT p.*, i.invoice_number, c.name client_name
         FROM payments p JOIN invoices i ON p.invoice_id=i.id JOIN clients c ON i.client_id=c.id
         WHERE DATE(p.payment_date) BETWEEN ? AND ?
         ORDER BY p.payment_date DESC, p.id DESC LIMIT 10"
    );
    $rp->execute([$start, $end]); $recent_payments = $rp->fetchAll();

    // Overdue invoices
    $ov = $pdo->query(
        "SELECT i.*, c.name client_name, (i.total-i.amount_paid) balance
         FROM invoices i JOIN clients c ON i.client_id=c.id
         WHERE i.status IN ('sent','overdue','partial') AND i.due_date < CURDATE()
         ORDER BY i.due_date ASC LIMIT 10"
    );
    $overdue_list = $ov ? $ov->fetchAll() : [];
}

// Build chart data
$chart_labels = []; $chart_data = [];
if (!empty($monthly_revenue)) {
    $filled = [];
    $cursor = new DateTimeImmutable('11 months ago');
    for ($i = 0; $i < 12; $i++) {
        $key = $cursor->format('Y-m');
        $filled[$key] = 0;
        $cursor = $cursor->modify('+1 month');
    }
    foreach ($monthly_revenue as $row) {
        if (isset($filled[$row['ym']])) $filled[$row['ym']] = (float)$row['total'];
    }
    foreach ($filled as $ym => $val) {
        $chart_labels[] = date('M y', strtotime($ym . '-01'));
        $chart_data[]   = $val;
    }
}
$chart_labels_json = json_encode($chart_labels);
$chart_data_json   = json_encode($chart_data);
?>

<!-- Period selector -->
<div class="flex items-center justify-between mb-6 flex-wrap gap-3">
  <h1 class="text-lg font-bold text-gray-800">Reports — <?= $label ?></h1>
  <div class="flex gap-1 bg-white border border-gray-200 rounded-lg p-1 text-sm">
    <?php foreach (['this_month'=>'This Month','last_month'=>'Last Month','this_quarter'=>'Quarter','this_year'=>'Year','last_30'=>'30 Days'] as $val=>$lbl): ?>
      <a href="?period=<?= $val ?>"
         class="px-3 py-1.5 rounded-md font-medium transition-colors <?= $period===$val?'bg-amber-500 text-white':'text-gray-500 hover:bg-gray-50' ?>">
        <?= $lbl ?>
      </a>
    <?php endforeach; ?>
  </div>
</div>

<!-- KPI row -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
  <?php
  $kpis = [
    ['label'=>'Revenue Billed', 'value'=>fmt_money($revenue_total),    'color'=>'text-amber-600',  'sub'=>"$invoices_count invoices"],
    ['label'=>'Revenue Collected','value'=>fmt_money($revenue_paid),   'color'=>'text-green-600',  'sub'=>"$payments_count payments"],
    ['label'=>'Outstanding',    'value'=>fmt_money($revenue_pending),   'color'=>'text-red-600',    'sub'=>count($overdue_list).' overdue'],
    ['label'=>'New Clients',    'value'=>$new_clients,                  'color'=>'text-blue-600',   'sub'=>"$orders_completed orders completed"],
  ];
  foreach ($kpis as $k): ?>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
    <p class="text-xs font-medium text-gray-400 uppercase tracking-wider"><?= $k['label'] ?></p>
    <p class="text-2xl font-bold mt-1 <?= $k['color'] ?>"><?= $k['value'] ?></p>
    <p class="text-xs text-gray-400 mt-1"><?= $k['sub'] ?></p>
  </div>
  <?php endforeach; ?>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-5">

  <!-- Revenue chart -->
  <div class="xl:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-5">
    <h2 class="font-semibold text-gray-800 mb-4">Revenue Collected — Last 12 Months</h2>
    <?php if (empty($chart_data) || max($chart_data) == 0): ?>
      <div class="flex items-center justify-center h-40 text-gray-300 text-sm">No payment data yet</div>
    <?php else: ?>
      <canvas id="revenueChart" height="100"></canvas>
    <?php endif; ?>
  </div>

  <!-- Invoice status breakdown -->
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
    <h2 class="font-semibold text-gray-800 mb-4">Invoice Status</h2>
    <?php if (empty($by_status)): ?>
      <p class="text-sm text-gray-300 text-center mt-8">No invoices yet</p>
    <?php else: ?>
      <div class="space-y-3">
        <?php
        $status_colors = [
          'draft'=>['bg-gray-200','text-gray-600'],
          'sent'=>['bg-blue-100','text-blue-700'],
          'partial'=>['bg-amber-100','text-amber-700'],
          'paid'=>['bg-green-100','text-green-700'],
          'overdue'=>['bg-red-100','text-red-700'],
        ];
        foreach ($by_status as $row):
            [$bg,$tc] = $status_colors[$row['status']] ?? ['bg-gray-100','text-gray-600'];
        ?>
        <div class="flex items-center justify-between">
          <div class="flex items-center gap-2">
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $bg ?> <?= $tc ?>"><?= ucfirst($row['status']) ?></span>
            <span class="text-sm text-gray-500"><?= $row['cnt'] ?> invoice<?= $row['cnt']!=1?'s':'' ?></span>
          </div>
          <?php if ($row['outstanding'] > 0): ?>
            <span class="text-xs font-medium text-red-600"><?= fmt_money($row['outstanding']) ?></span>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

</div>

<div class="grid grid-cols-1 xl:grid-cols-2 gap-5 mb-5">

  <!-- Top clients -->
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800">Top Clients by Revenue</h2>
      <p class="text-xs text-gray-400 mt-0.5"><?= $label ?></p>
    </div>
    <?php if (empty($top_clients)): ?>
      <p class="text-sm text-gray-300 text-center py-10">No payments in this period</p>
    <?php else:
      $max_paid = max(array_column($top_clients,'paid')) ?: 1;
    ?>
    <div class="divide-y divide-gray-50">
      <?php foreach ($top_clients as $i => $cl): ?>
      <div class="px-5 py-3">
        <div class="flex items-center justify-between mb-1">
          <a href="/crm/clients/view.php?id=<?= $cl['id'] ?>" class="text-sm font-medium text-gray-800 hover:text-amber-600">
            <?= htmlspecialchars($cl['name']) ?><?= $cl['company']?' <span class="text-gray-400 font-normal text-xs">('.htmlspecialchars($cl['company']).')</span>':'' ?>
          </a>
          <span class="text-sm font-semibold text-green-700"><?= fmt_money($cl['paid']) ?></span>
        </div>
        <div class="bg-gray-100 rounded-full h-1.5">
          <div class="bg-amber-500 h-1.5 rounded-full" style="width:<?= round($cl['paid']/$max_paid*100) ?>%"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Top services -->
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800">Top Services / Items</h2>
      <p class="text-xs text-gray-400 mt-0.5"><?= $label ?></p>
    </div>
    <?php if (empty($top_services)): ?>
      <p class="text-sm text-gray-300 text-center py-10">No invoiced items in this period</p>
    <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Item</th>
            <th class="text-center px-3 py-3 text-xs font-semibold text-gray-500 uppercase">Sold</th>
            <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Revenue</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($top_services as $svc): ?>
          <tr>
            <td class="px-5 py-3">
              <p class="font-medium text-gray-800"><?= htmlspecialchars($svc['item_name']) ?></p>
              <p class="text-xs text-gray-400"><?= htmlspecialchars($svc['category']) ?></p>
            </td>
            <td class="px-3 py-3 text-center text-gray-500"><?= $svc['times_sold'] ?>×</td>
            <td class="px-5 py-3 text-right font-semibold text-gray-800"><?= fmt_money($svc['revenue']) ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

</div>

<!-- Overdue invoices -->
<?php if (!empty($overdue_list)): ?>
<div class="bg-white rounded-xl border border-red-100 shadow-sm overflow-hidden mb-5">
  <div class="px-5 py-4 border-b border-red-100 bg-red-50 flex items-center gap-2">
    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
    <h2 class="font-semibold text-red-800">Overdue Invoices</h2>
    <span class="ml-auto text-xs font-semibold text-red-700 bg-red-100 px-2 py-0.5 rounded-full"><?= count($overdue_list) ?></span>
  </div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 border-b border-gray-100">
        <tr>
          <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Invoice</th>
          <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Client</th>
          <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Due</th>
          <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Days Late</th>
          <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Balance</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        <?php foreach ($overdue_list as $ov):
          $days_late = (int)(new DateTimeImmutable())->diff(new DateTimeImmutable($ov['due_date']))->days;
        ?>
        <tr>
          <td class="px-5 py-3 font-mono text-xs text-gray-700"><?= htmlspecialchars($ov['invoice_number']) ?></td>
          <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($ov['client_name']) ?></td>
          <td class="px-4 py-3 text-center text-red-600 text-xs"><?= date('d M Y', strtotime($ov['due_date'])) ?></td>
          <td class="px-4 py-3 text-center">
            <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $days_late>30?'bg-red-100 text-red-700':($days_late>7?'bg-orange-100 text-orange-700':'bg-yellow-100 text-yellow-700') ?>">
              <?= $days_late ?>d
            </span>
          </td>
          <td class="px-5 py-3 text-right font-bold text-red-600"><?= fmt_money($ov['balance']) ?></td>
          <td class="px-4 py-3 text-right">
            <a href="/crm/invoices/view.php?id=<?= $ov['id'] ?>" class="text-xs text-amber-600 hover:text-amber-700 font-medium">View →</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Recent payments -->
<?php if (!empty($recent_payments)): ?>
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="px-5 py-4 border-b border-gray-100"><h2 class="font-semibold text-gray-800">Recent Payments</h2><p class="text-xs text-gray-400 mt-0.5"><?= $label ?></p></div>
  <div class="overflow-x-auto">
    <table class="w-full text-sm">
      <thead class="bg-gray-50 border-b border-gray-100">
        <tr>
          <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Date</th>
          <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Client</th>
          <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Invoice</th>
          <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Method</th>
          <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Amount</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-50">
        <?php foreach ($recent_payments as $pay): ?>
        <tr>
          <td class="px-5 py-3 text-gray-500 text-xs"><?= date('d M Y', strtotime($pay['payment_date'])) ?></td>
          <td class="px-4 py-3 font-medium text-gray-800"><?= htmlspecialchars($pay['client_name']) ?></td>
          <td class="px-4 py-3"><a href="/crm/invoices/view.php?id=<?= $pay['invoice_id'] ?>" class="font-mono text-xs text-amber-600 hover:text-amber-700"><?= htmlspecialchars($pay['invoice_number']) ?></a></td>
          <td class="px-4 py-3 text-gray-500 capitalize"><?= str_replace('_',' ',$pay['method']) ?><?= $pay['reference']?' <span class="text-gray-300">·</span> <span class="text-xs text-gray-400">'.htmlspecialchars($pay['reference']).'</span>':'' ?></td>
          <td class="px-5 py-3 text-right font-semibold text-green-700"><?= fmt_money($pay['amount']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if (!empty($chart_data) && max($chart_data) > 0): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
(function() {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= $chart_labels_json ?>,
            datasets: [{
                label: 'Revenue (UGX)',
                data: <?= $chart_data_json ?>,
                backgroundColor: 'rgba(245,158,11,0.7)',
                borderColor: '#f59e0b',
                borderWidth: 1,
                borderRadius: 4,
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => 'UGX ' + ctx.parsed.y.toLocaleString()
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: v => 'UGX ' + (v >= 1000000 ? (v/1000000).toFixed(1)+'M' : v >= 1000 ? (v/1000).toFixed(0)+'K' : v)
                    },
                    grid: { color: '#f1f5f9' }
                },
                x: { grid: { display: false } }
            }
        }
    });
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
