<?php
$page_title = 'New Supplier';
$active_nav = 'suppliers';
require_once __DIR__ . '/../partials/header.php';

$errors = [];
$f = ['name'=>'','contact_name'=>'','email'=>'','phone'=>'','address'=>'','city'=>'','credit_limit'=>'0','credit_days'=>'30','notes'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    foreach ($f as $k => $_) {
        $f[$k] = clean($_POST[$k] ?? '');
    }
    if (!$f['name']) $errors['name'] = 'Supplier name is required.';
    if ($f['email'] && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email address.';
    $credit_limit = (float) str_replace(',', '', $f['credit_limit']);
    $credit_days  = max(0, (int) $f['credit_days']);

    if (empty($errors)) {
        $code = next_doc_number($pdo, 'SUP');
        $stmt = $pdo->prepare(
            "INSERT INTO suppliers (code, name, contact_name, email, phone, address, city, credit_limit, credit_days, notes, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $code, $f['name'], $f['contact_name'] ?: null, $f['email'] ?: null,
            $f['phone'] ?: null, $f['address'] ?: null, $f['city'] ?: null,
            $credit_limit, $credit_days, $f['notes'] ?: null,
            $_SESSION['admin_id'] ?? null,
        ]);
        $id = (int)$pdo->lastInsertId();
        log_activity($pdo, 'supplier_created', "Supplier {$code} — {$f['name']} added.", 'supplier', $id);
        flash('success', "Supplier {$code} created successfully.");
        redirect("/crm/suppliers/view?id=$id");
    }
}
?>

<div class="max-w-2xl">
  <div class="mb-5">
    <a href="/crm/suppliers/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Back to Suppliers
    </a>
  </div>

  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800">Supplier Information</h2>
      <p class="text-sm text-gray-400 mt-0.5">A supplier code will be auto-generated (e.g. SUP-2026-0001)</p>
    </div>

    <form method="POST" class="px-6 py-5 space-y-5">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Supplier / Company Name <span class="text-red-500">*</span></label>
          <input type="text" name="name" value="<?= htmlspecialchars($f['name']) ?>" required
                 class="w-full border <?= isset($errors['name']) ? 'border-red-400' : 'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
          <?php if (isset($errors['name'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['name'] ?></p><?php endif; ?>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Contact Person</label>
          <input type="text" name="contact_name" value="<?= htmlspecialchars($f['contact_name']) ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($f['email']) ?>"
                 class="w-full border <?= isset($errors['email']) ? 'border-red-400' : 'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
          <?php if (isset($errors['email'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['email'] ?></p><?php endif; ?>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
          <input type="tel" name="phone" value="<?= htmlspecialchars($f['phone']) ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="sm:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Address</label>
          <input type="text" name="address" value="<?= htmlspecialchars($f['address']) ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">City</label>
          <input type="text" name="city" value="<?= htmlspecialchars($f['city']) ?>" placeholder="Kampala"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Credit Limit (UGX)</label>
          <input type="number" name="credit_limit" value="<?= htmlspecialchars($f['credit_limit']) ?>"
                 min="0" step="1000" placeholder="0"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
          <p class="text-xs text-gray-400 mt-1">0 = cash only / no credit</p>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Payment Terms (days)</label>
          <input type="number" name="credit_days" value="<?= htmlspecialchars($f['credit_days']) ?>"
                 min="0" max="365" placeholder="30"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
        <textarea name="notes" rows="3"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 resize-none"><?= htmlspecialchars($f['notes']) ?></textarea>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors">Save Supplier</button>
        <a href="/crm/suppliers/" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
