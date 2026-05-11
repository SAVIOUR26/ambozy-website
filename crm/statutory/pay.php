<?php
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /crm/statutory/'); exit; }

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/crm_helpers.php';
require_login();

$ob = null;
if ($pdo) { try {
    $stmt = $pdo->prepare("SELECT * FROM statutory_obligations WHERE id = ?");
    $stmt->execute([$id]);
    $ob = $stmt->fetch();
} catch (PDOException $e) {} }

if (!$ob) { flash('error', 'Obligation not found.'); redirect('/crm/statutory/'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $amount_paid = (float) str_replace(',', '', $_POST['amount_paid'] ?? '0');
    $paid_date   = clean($_POST['paid_date'] ?? date('Y-m-d'));
    $reference   = clean($_POST['reference'] ?? '');

    if ($amount_paid > 0) {
        $new_paid   = $ob['amount_paid'] + $amount_paid;
        $new_status = $new_paid >= $ob['amount_due'] ? 'paid' : 'partial';
        $pdo->prepare(
            "UPDATE statutory_obligations SET amount_paid=?, status=?, paid_date=?, reference=COALESCE(NULLIF(?,''), reference) WHERE id=?"
        )->execute([$new_paid, $new_status, $new_status === 'paid' ? $paid_date : $ob['paid_date'], $reference, $id]);
        log_activity($pdo, 'statutory_paid', "Payment of " . fmt_money($amount_paid) . " on statutory obligation ID {$id}.", 'statutory', $id);
        flash('success', 'Payment recorded.');
    }
    redirect('/crm/statutory/');
}

$page_title = 'Record Payment';
$active_nav = 'statutory';
require_once __DIR__ . '/../partials/header.php';

$month_names = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
$type_labels = ['paye'=>'PAYE','vat'=>'VAT','withholding_tax'=>'Withholding Tax','nssf'=>'NSSF','local_service_tax'=>'LST','other'=>'Other'];
$balance     = $ob['amount_due'] - $ob['amount_paid'];
?>

<div class="max-w-md">
  <div class="mb-5">
    <a href="/crm/statutory/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Back to Obligations
    </a>
  </div>

  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800">Record Payment</h2>
      <p class="text-sm text-gray-500 mt-1">
        <?= $type_labels[$ob['type']] ?? strtoupper($ob['type']) ?> —
        <?= $month_names[$ob['period_month']] ?> <?= $ob['period_year'] ?>
      </p>
    </div>

    <div class="px-6 py-4 bg-amber-50 border-b border-amber-100">
      <div class="flex justify-between text-sm">
        <span class="text-gray-600">Amount Due</span>
        <span class="font-semibold"><?= fmt_money($ob['amount_due']) ?></span>
      </div>
      <div class="flex justify-between text-sm mt-1">
        <span class="text-gray-600">Already Paid</span>
        <span class="text-green-600 font-medium"><?= fmt_money($ob['amount_paid']) ?></span>
      </div>
      <div class="flex justify-between text-sm mt-1 border-t border-amber-200 pt-2">
        <span class="font-semibold text-red-700">Balance</span>
        <span class="font-bold text-red-700"><?= fmt_money($balance) ?></span>
      </div>
    </div>

    <form method="POST" class="px-6 py-5 space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount to Pay (UGX)</label>
        <input type="number" name="amount_paid" value="<?= $balance ?>" min="1" step="100" required
               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Payment Date</label>
        <input type="date" name="paid_date" value="<?= date('Y-m-d') ?>" required
               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
      </div>
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">PRN / Reference</label>
        <input type="text" name="reference" value="<?= htmlspecialchars($ob['reference'] ?? '') ?>" placeholder="URA PRN number"
               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
      </div>
      <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors">Record Payment</button>
        <a href="/crm/statutory/" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
