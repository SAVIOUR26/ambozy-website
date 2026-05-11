<?php
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /crm/suppliers/'); exit; }

$supplier  = null;
$purchases = [];
$total_owed = 0;

if ($pdo) { try {
    $supplier = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $supplier->execute([$id]);
    $supplier = $supplier->fetch();

    if (!$supplier) { header('Location: /crm/suppliers/'); exit; }

    $purchases = $pdo->prepare(
        "SELECT p.*, IFNULL(p.total - p.amount_paid, 0) AS balance
         FROM purchases p WHERE p.supplier_id = ? ORDER BY p.purchase_date DESC LIMIT 30"
    );
    $purchases->execute([$id]);
    $purchases = $purchases->fetchAll();

    $row = $pdo->prepare("SELECT IFNULL(SUM(total - amount_paid),0) FROM purchases WHERE supplier_id = ? AND status NOT IN ('paid','cancelled')");
    $row->execute([$id]);
    $total_owed = (float)$row->fetchColumn();
} catch (PDOException $e) { error_log('Supplier view: ' . $e->getMessage()); } }

$page_title = $supplier['name'] ?? 'Supplier';
$active_nav = 'suppliers';
$header_actions = '<a href="/crm/purchases/create?supplier_id=' . $id . '"
   class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
  New Purchase
</a>';
require_once __DIR__ . '/../partials/header.php';
?>

<div class="mb-5">
  <a href="/crm/suppliers/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Back to Suppliers
  </a>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5 mb-6">
  <!-- Profile card -->
  <div class="xl:col-span-2 bg-white rounded-xl border border-gray-100 shadow-sm p-6">
    <div class="flex items-start gap-4">
      <div class="w-14 h-14 rounded-2xl bg-violet-100 text-violet-700 flex items-center justify-center text-xl font-bold shrink-0">
        <?= strtoupper(substr($supplier['name'], 0, 1)) ?>
      </div>
      <div class="flex-1">
        <div class="flex items-center gap-3 flex-wrap">
          <h2 class="text-xl font-semibold text-gray-900"><?= htmlspecialchars($supplier['name']) ?></h2>
          <span class="text-xs px-2 py-0.5 rounded-full font-medium <?= $supplier['status']==='active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' ?>">
            <?= ucfirst($supplier['status']) ?>
          </span>
          <span class="text-xs text-gray-400 font-mono"><?= htmlspecialchars($supplier['code']) ?></span>
        </div>
        <?php if ($supplier['contact_name']): ?>
          <p class="text-sm text-gray-600 mt-1">Contact: <?= htmlspecialchars($supplier['contact_name']) ?></p>
        <?php endif; ?>
        <div class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-sm text-gray-500">
          <?php if ($supplier['email']): ?>
            <span>✉ <?= htmlspecialchars($supplier['email']) ?></span>
          <?php endif; ?>
          <?php if ($supplier['phone']): ?>
            <span>📞 <?= htmlspecialchars($supplier['phone']) ?></span>
          <?php endif; ?>
          <?php if ($supplier['address'] || $supplier['city']): ?>
            <span>📍 <?= htmlspecialchars(trim(($supplier['address'] ?? '') . ' ' . ($supplier['city'] ?? ''))) ?></span>
          <?php endif; ?>
        </div>
        <?php if ($supplier['notes']): ?>
          <p class="text-sm text-gray-500 mt-3 bg-gray-50 rounded-lg p-3"><?= nl2br(htmlspecialchars($supplier['notes'])) ?></p>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Stats -->
  <div class="space-y-3">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
      <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Balance Owed</p>
      <p class="text-2xl font-bold <?= $total_owed > 0 ? 'text-red-600' : 'text-gray-900' ?>">
        <?= fmt_money($total_owed) ?>
      </p>
    </div>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
      <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Credit Limit</p>
      <p class="text-2xl font-bold text-gray-900"><?= $supplier['credit_limit'] > 0 ? fmt_money($supplier['credit_limit']) : '—' ?></p>
      <?php if ($supplier['credit_days']): ?>
        <p class="text-xs text-gray-400 mt-1">Terms: <?= $supplier['credit_days'] ?> days</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<!-- Purchase history -->
<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
  <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
    <h3 class="font-semibold text-gray-800">Purchase History</h3>
    <a href="/crm/purchases/create?supplier_id=<?= $id ?>"
       class="text-xs text-amber-600 hover:text-amber-700 font-medium">+ New Purchase</a>
  </div>
  <?php if (empty($purchases)): ?>
    <p class="text-center text-gray-400 py-10 text-sm">No purchases recorded yet.</p>
  <?php else: ?>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Purchase #</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Total</th>
            <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Balance</th>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach ($purchases as $p): ?>
            <tr class="hover:bg-gray-50 transition-colors">
              <td class="px-5 py-3.5 font-mono text-xs font-medium text-gray-900"><?= htmlspecialchars($p['purchase_number']) ?></td>
              <td class="px-4 py-3.5 text-gray-600"><?= date('d M Y', strtotime($p['purchase_date'])) ?></td>
              <td class="px-4 py-3.5">
                <span class="text-xs px-2 py-0.5 rounded-full font-medium <?= $p['payment_type']==='credit' ? 'bg-orange-100 text-orange-700' : 'bg-blue-100 text-blue-700' ?>">
                  <?= ucfirst($p['payment_type']) ?>
                </span>
              </td>
              <td class="px-4 py-3.5 text-right font-medium text-gray-900"><?= fmt_money($p['total']) ?></td>
              <td class="px-4 py-3.5 text-right">
                <?php if ($p['balance'] > 0): ?>
                  <span class="font-medium text-red-600"><?= fmt_money($p['balance']) ?></span>
                <?php else: ?>
                  <span class="text-gray-400">—</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3.5">
                <span class="inline-block text-xs px-2 py-0.5 rounded-full font-medium <?= purchase_badge($p['status']) ?>">
                  <?= ucfirst($p['status']) ?>
                </span>
              </td>
              <td class="px-4 py-3.5 text-right">
                <a href="/crm/purchases/view?id=<?= $p['id'] ?>" class="text-gray-400 hover:text-amber-600 transition-colors">
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
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
