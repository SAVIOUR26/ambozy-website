<?php
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /crm/quotes/'); exit; }
$page_title = 'Quotation';
$active_nav = 'quotes';
require_once __DIR__ . '/../partials/header.php';

$quote = $client = null; $items = [];
if ($pdo) {
    $s = $pdo->prepare("SELECT q.*, c.name client_name, c.email client_email, c.phone client_phone, c.company client_company FROM quotations q JOIN clients c ON q.client_id=c.id WHERE q.id=?");
    $s->execute([$id]); $quote = $s->fetch();
    if ($quote) {
        $si = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id=? ORDER BY sort_order"); $si->execute([$id]); $items = $si->fetchAll();
    }
}
if (!$quote) { flash('error','Quote not found.'); redirect('/crm/quotes/'); }
$page_title = $quote['quote_number'];

// Handle status change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $act = $_POST['action'] ?? '';
    if ($act === 'change_status') {
        $ns = $_POST['new_status'] ?? '';
        $valid = ['draft','sent','accepted','rejected','expired'];
        if (in_array($ns, $valid)) {
            $sent_at = ($ns === 'sent' && $quote['status'] !== 'sent') ? ', sent_at=NOW()' : '';
            $pdo->prepare("UPDATE quotations SET status=?$sent_at WHERE id=?")->execute([$ns, $id]);
            log_activity($pdo,'quote_status',"Quote {$quote['quote_number']} → $ns.",'quotation',$id);
            // If accepted, offer to create order
            flash('success', "Status updated to $ns.");
            redirect("/crm/quotes/view.php?id=$id");
        }
    }
    if ($act === 'create_order') {
        redirect("/crm/orders/create.php?quote_id=$id");
    }
    if ($act === 'create_invoice') {
        redirect("/crm/invoices/create.php?quote_id=$id");
    }
}
?>

<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
  <a href="/crm/quotes/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Quotations
  </a>
  <div class="flex items-center gap-2 flex-wrap">
    <a href="/crm/quotes/pdf.php?id=<?= $id ?>" target="_blank"
       class="text-sm bg-white border border-gray-200 hover:border-indigo-400 text-gray-700 px-3 py-2 rounded-lg transition-colors">
      🖨 Print / PDF
    </a>
    <?php if (!in_array($quote['status'], ['accepted','invoiced'])): ?>
      <a href="/crm/quotes/edit.php?id=<?= $id ?>"
         class="text-sm bg-white border border-gray-200 hover:border-amber-400 text-gray-700 px-3 py-2 rounded-lg transition-colors">
        ✎ Edit
      </a>
    <?php endif; ?>
    <?php if ($quote['status'] === 'draft'): ?>
      <a href="/crm/quotes/send.php?id=<?= $id ?>"
         class="text-sm bg-blue-500 hover:bg-blue-400 text-white px-3 py-2 rounded-lg font-medium transition-colors">
        ✉ Send to Client
      </a>
    <?php endif; ?>
    <?php if (in_array($quote['status'], ['sent','accepted'])): ?>
      <form method="POST" class="inline">
        <input type="hidden" name="action" value="create_order">
        <button type="submit" class="text-sm bg-purple-500 hover:bg-purple-400 text-white px-3 py-2 rounded-lg font-medium">
          + Create Order
        </button>
      </form>
      <form method="POST" class="inline">
        <input type="hidden" name="action" value="create_invoice">
        <button type="submit" class="text-sm bg-green-500 hover:bg-green-400 text-white px-3 py-2 rounded-lg font-medium">
          + Invoice
        </button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

  <!-- Quote detail -->
  <div class="xl:col-span-2 space-y-5">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">

      <!-- Header -->
      <div class="p-6 border-b border-gray-100 flex items-start justify-between gap-4 flex-wrap">
        <div>
          <p class="text-xs text-gray-400 font-medium uppercase tracking-wider mb-1">Quotation</p>
          <h2 class="text-2xl font-bold text-gray-900 font-mono"><?= htmlspecialchars($quote['quote_number']) ?></h2>
          <p class="text-gray-600 mt-1"><?= htmlspecialchars($quote['title']) ?></p>
        </div>
        <span class="text-sm px-3 py-1 rounded-full font-semibold <?= quote_badge($quote['status']) ?>">
          <?= ucfirst($quote['status']) ?>
        </span>
      </div>

      <!-- Meta row -->
      <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-y md:divide-y-0 divide-gray-100 border-b border-gray-100">
        <?php
        $meta = [
            'Client'      => htmlspecialchars($quote['client_name']),
            'Created'     => date('d M Y', strtotime($quote['created_at'])),
            'Valid Until' => $quote['valid_until'] ? date('d M Y', strtotime($quote['valid_until'])) : '—',
            'Sent'        => $quote['sent_at'] ? date('d M Y', strtotime($quote['sent_at'])) : '—',
        ];
        foreach ($meta as $label => $val):
        ?>
        <div class="px-5 py-3.5">
          <p class="text-xs text-gray-400 font-medium"><?= $label ?></p>
          <p class="text-sm font-medium text-gray-800 mt-0.5"><?= $val ?></p>
        </div>
        <?php endforeach; ?>
      </div>

      <!-- Line items -->
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100">
            <tr>
              <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">#</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Description</th>
              <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Qty</th>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Unit</th>
              <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Unit Price</th>
              <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Total</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <?php foreach ($items as $i => $it): ?>
            <tr>
              <td class="px-5 py-3.5 text-gray-400"><?= $i+1 ?></td>
              <td class="px-4 py-3.5 text-gray-800 font-medium"><?= htmlspecialchars($it['description']) ?></td>
              <td class="px-4 py-3.5 text-center text-gray-600"><?= $it['quantity']+0 ?></td>
              <td class="px-4 py-3.5 text-gray-500"><?= htmlspecialchars($it['unit']) ?></td>
              <td class="px-4 py-3.5 text-right text-gray-600"><?= fmt_money($it['unit_price']) ?></td>
              <td class="px-5 py-3.5 text-right font-semibold text-gray-800"><?= fmt_money($it['total']) ?></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Totals -->
      <div class="flex justify-end p-5 border-t border-gray-100">
        <div class="w-64 space-y-2 text-sm">
          <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span class="font-medium"><?= fmt_money($quote['subtotal']) ?></span></div>
          <?php if ($quote['discount_percent'] > 0): $disc=round($quote['subtotal']*$quote['discount_percent']/100); ?>
            <div class="flex justify-between text-red-500"><span>Discount (<?= $quote['discount_percent'] ?>%)</span><span>- <?= fmt_money($disc) ?></span></div>
          <?php endif; ?>
          <?php if ($quote['tax_percent'] > 0): $taxbase=$quote['subtotal']*(1-$quote['discount_percent']/100); $taxamt=round($taxbase*$quote['tax_percent']/100); ?>
            <div class="flex justify-between"><span class="text-gray-500">Tax (<?= $quote['tax_percent'] ?>%)</span><span class="font-medium"><?= fmt_money($taxamt) ?></span></div>
          <?php endif; ?>
          <div class="flex justify-between border-t border-gray-200 pt-2">
            <span class="font-bold text-gray-800">Total</span>
            <span class="font-bold text-xl text-amber-600"><?= fmt_money($quote['total']) ?></span>
          </div>
        </div>
      </div>

      <?php if ($quote['notes'] || $quote['terms']): ?>
        <div class="px-5 pb-5 space-y-3 border-t border-gray-100 pt-5">
          <?php if ($quote['notes']): ?>
            <div><p class="text-xs font-semibold text-gray-400 uppercase mb-1">Notes</p><p class="text-sm text-gray-600"><?= nl2br(htmlspecialchars($quote['notes'])) ?></p></div>
          <?php endif; ?>
          <?php if ($quote['terms']): ?>
            <div><p class="text-xs font-semibold text-gray-400 uppercase mb-1">Terms &amp; Conditions</p><p class="text-sm text-gray-500"><?= nl2br(htmlspecialchars($quote['terms'])) ?></p></div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right: status + client -->
  <div class="space-y-5">

    <!-- Update status -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
      <h3 class="font-semibold text-gray-800 mb-4">Update Status</h3>
      <form method="POST" class="space-y-3">
        <input type="hidden" name="action" value="change_status">
        <div class="grid grid-cols-2 gap-2">
          <?php foreach (['draft','sent','accepted','rejected','expired'] as $s): ?>
            <label class="cursor-pointer">
              <input type="radio" name="new_status" value="<?= $s ?>" class="sr-only peer" <?= $quote['status']===$s?'checked':'' ?>>
              <div class="text-center py-2 px-1 rounded-lg border-2 text-xs font-medium transition-colors
                          <?= $quote['status']===$s ? 'border-amber-500 bg-amber-50 text-amber-700' : 'border-gray-100 text-gray-500 hover:border-amber-300' ?>
                          peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-700">
                <?= ucfirst($s) ?>
              </div>
            </label>
          <?php endforeach; ?>
        </div>
        <button type="submit" class="w-full bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold py-2 rounded-lg transition-colors">
          Update
        </button>
      </form>
    </div>

    <!-- Client card -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
      <h3 class="font-semibold text-gray-800 mb-3">Bill To</h3>
      <div class="space-y-1.5 text-sm">
        <p class="font-semibold text-gray-800"><?= htmlspecialchars($quote['client_name']) ?></p>
        <?php if ($quote['client_company']): ?><p class="text-gray-500"><?= htmlspecialchars($quote['client_company']) ?></p><?php endif; ?>
        <?php if ($quote['client_email']): ?><p class="text-amber-600"><?= htmlspecialchars($quote['client_email']) ?></p><?php endif; ?>
        <?php if ($quote['client_phone']): ?><p class="text-gray-500"><?= htmlspecialchars($quote['client_phone']) ?></p><?php endif; ?>
      </div>
      <a href="/crm/clients/view.php?id=<?= $quote['client_id'] ?>"
         class="mt-3 inline-block text-xs text-amber-600 hover:text-amber-700 font-medium">
        View client profile →
      </a>
    </div>
  </div>

</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
