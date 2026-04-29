<?php
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /crm/quotes/'); exit; }
$page_title = 'Send Quotation';
$active_nav = 'quotes';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../../includes/crm_email.php';

$quote = null; $items = [];
if ($pdo) {
    $s = $pdo->prepare("SELECT q.*, c.name client_name, c.email client_email, c.phone client_phone, c.company client_company FROM quotations q JOIN clients c ON q.client_id=c.id WHERE q.id=?");
    $s->execute([$id]); $quote = $s->fetch();
    if ($quote) { $si = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id=? ORDER BY sort_order"); $si->execute([$id]); $items=$si->fetchAll(); }
}
if (!$quote) { flash('error','Quote not found.'); redirect('/crm/quotes/'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $to_email = clean($_POST['to_email'] ?? '');
    $to_name  = clean($_POST['to_name']  ?? '');

    if (!filter_var($to_email, FILTER_VALIDATE_EMAIL)) {
        flash('error','Please enter a valid recipient email address.');
        redirect("/crm/quotes/send.php?id=$id");
    }

    // Format totals for email
    $q_data = $quote;
    $q_data['subtotal'] = number_format($quote['subtotal'], 0);
    $q_data['total']    = number_format($quote['total'], 0);
    $client_data = ['name' => $to_name ?: $quote['client_name']];

    $html = email_quotation($q_data, $client_data, $items);
    $subj = "Quotation {$quote['quote_number']} from Ambozy Graphics Solutions";

    $ok = crm_send_email($pdo, 'quotation', $to_email, $to_name, $subj, $html, 'quotation', $id);

    if ($ok) {
        $pdo->prepare("UPDATE quotations SET status='sent', sent_at=NOW() WHERE id=? AND status='draft'")->execute([$id]);
        log_activity($pdo,'quote_sent',"Quote {$quote['quote_number']} sent to {$to_email}.",'quotation',$id);
        flash('success', "Quote emailed to {$to_email} successfully.");
        redirect("/crm/quotes/view.php?id=$id");
    } else {
        flash('error', 'Email could not be sent. Please check server mail configuration.');
        redirect("/crm/quotes/send.php?id=$id");
    }
}
?>

<div class="max-w-lg">
  <div class="mb-5">
    <a href="/crm/quotes/view.php?id=<?= $id ?>" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Back to Quote
    </a>
  </div>

  <!-- Quote summary -->
  <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-5">
    <div class="flex items-center justify-between">
      <div>
        <p class="font-semibold text-amber-900"><?= htmlspecialchars($quote['quote_number']) ?></p>
        <p class="text-sm text-amber-700"><?= htmlspecialchars($quote['title']) ?></p>
      </div>
      <p class="font-bold text-amber-800 text-lg"><?= fmt_money($quote['total']) ?></p>
    </div>
  </div>

  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800">Send to Client</h2>
      <p class="text-sm text-gray-400 mt-0.5">An HTML email with the full itemised quote will be sent.</p>
    </div>
    <form method="POST" class="px-6 py-5 space-y-5">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Recipient Name</label>
        <input type="text" name="to_name" value="<?= htmlspecialchars($quote['client_name']) ?>"
               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Recipient Email <span class="text-red-500">*</span></label>
        <input type="email" name="to_email" value="<?= htmlspecialchars($quote['client_email'] ?? '') ?>" required
               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
      </div>
      <div class="bg-gray-50 rounded-lg p-4 text-sm text-gray-500">
        <p class="font-medium text-gray-700 mb-1">Email will include:</p>
        <ul class="space-y-1 list-disc list-inside text-xs">
          <li>Full itemised quotation table</li>
          <li>Grand total with any discount/tax applied</li>
          <li>Notes and terms if set</li>
          <li>WhatsApp button for quick acceptance</li>
          <li>PDF link for printing</li>
        </ul>
      </div>
      <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-blue-500 hover:bg-blue-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors">
          ✉ Send Quote Now
        </button>
        <a href="/crm/quotes/view.php?id=<?= $id ?>" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
