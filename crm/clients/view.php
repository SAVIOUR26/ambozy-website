<?php
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /crm/clients/'); exit; }

$page_title = 'Client Profile';
$active_nav = 'clients';
require_once __DIR__ . '/../partials/header.php';

$client = null;
$leads = $quotes = $orders = $invoices = $activities = [];

if ($pdo) {
    $client = $pdo->prepare("SELECT * FROM clients WHERE id = ?")->execute([$id])
              ? $pdo->prepare("SELECT * FROM clients WHERE id = ?")->execute([$id]) ? null : null : null;

    // cleaner fetch
    $s = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $s->execute([$id]);
    $client = $s->fetch();

    if (!$client) { flash('error','Client not found.'); redirect('/crm/clients/'); }

    $page_title = htmlspecialchars($client['name']);

    $leads = $pdo->prepare("SELECT * FROM leads WHERE client_id = ? ORDER BY created_at DESC LIMIT 10");
    $leads->execute([$id]); $leads = $leads->fetchAll();

    $quotes = $pdo->prepare("SELECT * FROM quotations WHERE client_id = ? ORDER BY created_at DESC LIMIT 10");
    $quotes->execute([$id]); $quotes = $quotes->fetchAll();

    $orders = $pdo->prepare("SELECT * FROM orders WHERE client_id = ? ORDER BY created_at DESC LIMIT 10");
    $orders->execute([$id]); $orders = $orders->fetchAll();

    $invoices = $pdo->prepare("SELECT * FROM invoices WHERE client_id = ? ORDER BY created_at DESC LIMIT 10");
    $invoices->execute([$id]); $invoices = $invoices->fetchAll();

    $activities = $pdo->prepare(
        "SELECT * FROM activities WHERE related_type='client' AND related_id=? ORDER BY created_at DESC LIMIT 20"
    );
    $activities->execute([$id]); $activities = $activities->fetchAll();

    // Totals
    $totals = $pdo->prepare(
        "SELECT
           IFNULL(SUM(total),0)        AS billed,
           IFNULL(SUM(amount_paid),0)  AS paid,
           IFNULL(SUM(total-amount_paid),0) AS balance
         FROM invoices WHERE client_id = ? AND status NOT IN ('cancelled','draft')"
    );
    $totals->execute([$id]);
    $totals = $totals->fetch();
}
?>

<!-- Back + Actions -->
<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
  <a href="/crm/clients/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Clients
  </a>
  <div class="flex items-center gap-2">
    <a href="/crm/quotes/create?client_id=<?= $id ?>"
       class="text-sm bg-white border border-gray-200 hover:border-amber-400 text-gray-700 px-3 py-2 rounded-lg transition-colors">
      + Quote
    </a>
    <a href="/crm/orders/create?client_id=<?= $id ?>"
       class="text-sm bg-white border border-gray-200 hover:border-amber-400 text-gray-700 px-3 py-2 rounded-lg transition-colors">
      + Order
    </a>
    <a href="/crm/invoices/create?client_id=<?= $id ?>"
       class="text-sm bg-white border border-gray-200 hover:border-amber-400 text-gray-700 px-3 py-2 rounded-lg transition-colors">
      + Invoice
    </a>
    <a href="/crm/clients/edit?id=<?= $id ?>"
       class="text-sm bg-amber-500 hover:bg-amber-400 text-white px-3 py-2 rounded-lg transition-colors font-medium">
      Edit
    </a>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

  <!-- Left: Profile card -->
  <div class="space-y-4">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
      <div class="flex items-center gap-4 mb-5">
        <div class="w-14 h-14 rounded-xl bg-amber-100 text-amber-700 flex items-center justify-center text-2xl font-bold shrink-0">
          <?= strtoupper(substr($client['name'], 0, 1)) ?>
        </div>
        <div>
          <h2 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($client['name']) ?></h2>
          <?php if ($client['company']): ?>
            <p class="text-sm text-gray-500"><?= htmlspecialchars($client['company']) ?></p>
          <?php endif; ?>
          <span class="inline-block mt-1 text-xs px-2 py-0.5 rounded-full font-medium <?= client_badge($client['status']) ?>">
            <?= ucfirst($client['status']) ?>
          </span>
        </div>
      </div>

      <dl class="space-y-3 text-sm">
        <div class="flex items-start gap-3">
          <dt class="text-gray-400 w-20 shrink-0">Code</dt>
          <dd class="font-mono text-gray-700 font-medium"><?= htmlspecialchars($client['code']) ?></dd>
        </div>
        <?php if ($client['email']): ?>
          <div class="flex items-start gap-3">
            <dt class="text-gray-400 w-20 shrink-0">Email</dt>
            <dd><a href="mailto:<?= htmlspecialchars($client['email']) ?>"
                   class="text-amber-600 hover:underline break-all"><?= htmlspecialchars($client['email']) ?></a></dd>
          </div>
        <?php endif; ?>
        <?php if ($client['phone']): ?>
          <div class="flex items-start gap-3">
            <dt class="text-gray-400 w-20 shrink-0">Phone</dt>
            <dd><a href="tel:<?= htmlspecialchars($client['phone']) ?>"
                   class="text-gray-700 hover:text-amber-600"><?= htmlspecialchars($client['phone']) ?></a></dd>
          </div>
        <?php endif; ?>
        <?php if ($client['address'] || $client['city']): ?>
          <div class="flex items-start gap-3">
            <dt class="text-gray-400 w-20 shrink-0">Location</dt>
            <dd class="text-gray-700">
              <?= htmlspecialchars(implode(', ', array_filter([$client['address'], $client['city']]))) ?>
            </dd>
          </div>
        <?php endif; ?>
        <div class="flex items-start gap-3">
          <dt class="text-gray-400 w-20 shrink-0">Type</dt>
          <dd class="text-gray-700 capitalize"><?= $client['type'] ?></dd>
        </div>
        <div class="flex items-start gap-3">
          <dt class="text-gray-400 w-20 shrink-0">Source</dt>
          <dd class="text-gray-700 capitalize"><?= str_replace('-',' ',$client['source']) ?></dd>
        </div>
        <div class="flex items-start gap-3">
          <dt class="text-gray-400 w-20 shrink-0">Since</dt>
          <dd class="text-gray-700"><?= date('d M Y', strtotime($client['created_at'])) ?></dd>
        </div>
      </dl>

      <?php if ($client['notes']): ?>
        <div class="mt-4 pt-4 border-t border-gray-100">
          <p class="text-xs text-gray-400 font-medium mb-1">Notes</p>
          <p class="text-sm text-gray-600"><?= nl2br(htmlspecialchars($client['notes'])) ?></p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Financial summary -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
      <h3 class="font-semibold text-gray-700 text-sm mb-4">Financials</h3>
      <div class="space-y-3">
        <div class="flex justify-between text-sm">
          <span class="text-gray-500">Total Billed</span>
          <span class="font-medium text-gray-800"><?= fmt_money($totals['billed'] ?? 0) ?></span>
        </div>
        <div class="flex justify-between text-sm">
          <span class="text-gray-500">Total Paid</span>
          <span class="font-medium text-green-600"><?= fmt_money($totals['paid'] ?? 0) ?></span>
        </div>
        <div class="flex justify-between text-sm border-t border-gray-100 pt-3">
          <span class="text-gray-700 font-medium">Outstanding</span>
          <span class="font-bold <?= ($totals['balance']??0) > 0 ? 'text-red-600' : 'text-gray-400' ?>">
            <?= fmt_money($totals['balance'] ?? 0) ?>
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Right: Tabs -->
  <div class="xl:col-span-2" x-data="{ tab: 'orders' }">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <!-- Tab nav -->
      <div class="border-b border-gray-100 px-5 flex gap-1 overflow-x-auto">
        <?php
        $tabs = ['orders'=>'Orders ('.count($orders).')','quotes'=>'Quotes ('.count($quotes).')','invoices'=>'Invoices ('.count($invoices).')','leads'=>'Leads ('.count($leads).')','activity'=>'Activity'];
        foreach ($tabs as $tk => $tl):
        ?>
          <button @click="tab='<?= $tk ?>'"
                  :class="tab==='<?= $tk ?>' ? 'border-amber-500 text-amber-600' : 'border-transparent text-gray-400 hover:text-gray-600'"
                  class="py-3 px-1 text-sm font-medium border-b-2 transition-colors whitespace-nowrap mr-4">
            <?= $tl ?>
          </button>
        <?php endforeach; ?>
      </div>

      <!-- Orders tab -->
      <div x-show="tab==='orders'" class="divide-y divide-gray-50">
        <?php if (empty($orders)): ?>
          <p class="text-center text-gray-400 text-sm py-10">No orders yet.</p>
        <?php else: foreach ($orders as $o): ?>
          <a href="/crm/orders/view?id=<?= $o['id'] ?>"
             class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50 transition-colors">
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($o['order_number']) ?> — <?= htmlspecialchars($o['title']) ?></p>
              <p class="text-xs text-gray-400">Due: <?= $o['due_date'] ? date('d M Y',strtotime($o['due_date'])) : '—' ?></p>
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full font-medium <?= order_badge($o['status']) ?>">
              <?= ucfirst(str_replace('_',' ',$o['status'])) ?>
            </span>
          </a>
        <?php endforeach; endif; ?>
      </div>

      <!-- Quotes tab -->
      <div x-show="tab==='quotes'" x-cloak class="divide-y divide-gray-50">
        <?php if (empty($quotes)): ?>
          <p class="text-center text-gray-400 text-sm py-10">No quotations yet.</p>
        <?php else: foreach ($quotes as $q): ?>
          <a href="/crm/quotes/view?id=<?= $q['id'] ?>"
             class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50 transition-colors">
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($q['quote_number']) ?> — <?= htmlspecialchars($q['title']) ?></p>
              <p class="text-xs text-gray-400"><?= date('d M Y',strtotime($q['created_at'])) ?></p>
            </div>
            <div class="text-right">
              <span class="text-xs px-2 py-0.5 rounded-full font-medium <?= quote_badge($q['status']) ?>">
                <?= ucfirst($q['status']) ?>
              </span>
              <p class="text-xs font-medium text-gray-700 mt-0.5"><?= fmt_money($q['total']) ?></p>
            </div>
          </a>
        <?php endforeach; endif; ?>
      </div>

      <!-- Invoices tab -->
      <div x-show="tab==='invoices'" x-cloak class="divide-y divide-gray-50">
        <?php if (empty($invoices)): ?>
          <p class="text-center text-gray-400 text-sm py-10">No invoices yet.</p>
        <?php else: foreach ($invoices as $inv): ?>
          <a href="/crm/invoices/view?id=<?= $inv['id'] ?>"
             class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50 transition-colors">
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($inv['invoice_number']) ?></p>
              <p class="text-xs text-gray-400">Due: <?= $inv['due_date'] ? date('d M Y',strtotime($inv['due_date'])) : '—' ?></p>
            </div>
            <div class="text-right">
              <span class="text-xs px-2 py-0.5 rounded-full font-medium <?= invoice_badge($inv['status']) ?>">
                <?= ucfirst($inv['status']) ?>
              </span>
              <p class="text-xs font-semibold text-gray-700 mt-0.5"><?= fmt_money($inv['total']) ?></p>
            </div>
          </a>
        <?php endforeach; endif; ?>
      </div>

      <!-- Leads tab -->
      <div x-show="tab==='leads'" x-cloak class="divide-y divide-gray-50">
        <?php if (empty($leads)): ?>
          <p class="text-center text-gray-400 text-sm py-10">No linked leads.</p>
        <?php else: foreach ($leads as $l): ?>
          <a href="/crm/leads/view?id=<?= $l['id'] ?>"
             class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50 transition-colors">
            <div class="flex-1">
              <p class="text-sm font-medium text-gray-900"><?= htmlspecialchars($l['ref']) ?> — <?= htmlspecialchars($l['service_interest'] ?? 'General') ?></p>
              <p class="text-xs text-gray-400"><?= date('d M Y',strtotime($l['created_at'])) ?></p>
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full font-medium <?= lead_badge($l['status']) ?>">
              <?= ucfirst($l['status']) ?>
            </span>
          </a>
        <?php endforeach; endif; ?>
      </div>

      <!-- Activity tab -->
      <div x-show="tab==='activity'" x-cloak class="divide-y divide-gray-50">
        <?php if (empty($activities)): ?>
          <p class="text-center text-gray-400 text-sm py-10">No activity recorded yet.</p>
        <?php else: foreach ($activities as $a): ?>
          <div class="flex items-start gap-3 px-5 py-3.5">
            <div class="w-2 h-2 rounded-full bg-amber-400 mt-1.5 shrink-0"></div>
            <div>
              <p class="text-sm text-gray-700"><?= htmlspecialchars($a['description']) ?></p>
              <p class="text-xs text-gray-400 mt-0.5"><?= date('d M Y H:i', strtotime($a['created_at'])) ?></p>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
