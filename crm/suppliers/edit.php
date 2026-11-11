<?php
require_once __DIR__ . '/../partials/bootstrap.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { redirect('/crm/suppliers/'); }

$sup = null;
if ($pdo) {
    $q = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
    $q->execute([$id]);
    $sup = $q->fetch();
}
if (!$sup) { flash('error', 'Supplier not found.'); redirect('/crm/suppliers/'); }

$errors = [];
$f = [
    'name'         => unclean($sup['name']),
    'contact_name' => unclean($sup['contact_name']),
    'email'        => (string)$sup['email'],
    'phone'        => unclean($sup['phone']),
    'address'      => unclean($sup['address']),
    'city'         => unclean($sup['city']),
    'credit_limit' => $sup['credit_limit'] + 0,
    'credit_days'  => (string)$sup['credit_days'],
    'status'       => $sup['status'],
    'notes'        => unclean($sup['notes']),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    foreach ($f as $k => $_) { $f[$k] = trim($_POST[$k] ?? ''); }
    if (!in_array($f['status'], ['active','inactive'], true)) $f['status'] = $sup['status'];
    if (!$f['name']) $errors['name'] = 'Supplier name is required.';
    if ($f['email'] && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email address.';
    $credit_limit = (float) str_replace(',', '', $f['credit_limit']);
    $credit_days  = max(0, (int) $f['credit_days']);

    if (empty($errors)) {
        $pdo->prepare(
            "UPDATE suppliers SET name=?, contact_name=?, email=?, phone=?, address=?, city=?, credit_limit=?, credit_days=?, status=?, notes=? WHERE id=?"
        )->execute([
            clean($f['name']), clean($f['contact_name']) ?: null, $f['email'] ?: null,
            clean($f['phone']) ?: null, clean($f['address']) ?: null, clean($f['city']) ?: null,
            $credit_limit, $credit_days, $f['status'], clean($f['notes']) ?: null, $id,
        ]);
        log_activity($pdo, 'supplier_updated', "Supplier {$sup['code']} — {$f['name']} updated.", 'supplier', $id);
        flash('success', "Supplier {$sup['code']} updated.");
        redirect("/crm/suppliers/view?id=$id");
    }
}

$page_title = 'Edit Supplier';
$active_nav = 'suppliers';
require_once __DIR__ . '/../partials/header.php';
?>

<div class="max-w-2xl">
  <div class="mb-5">
    <a href="/crm/suppliers/view?id=<?= $id ?>" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Back to Supplier
    </a>
  </div>

  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800">Edit Supplier <span class="font-mono text-sm text-gray-400"><?= htmlspecialchars($sup['code']) ?></span></h2>
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
                 min="0" step="any" placeholder="0"
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
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
        <select name="status" class="w-full sm:w-1/2 border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
          <option value="active"   <?= $f['status']==='active'  ?'selected':'' ?>>Active</option>
          <option value="inactive" <?= $f['status']==='inactive'?'selected':'' ?>>Inactive (hidden from new purchases)</option>
        </select>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
        <textarea name="notes" rows="3"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 resize-none"><?= htmlspecialchars($f['notes']) ?></textarea>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors">Save Changes</button>
        <a href="/crm/suppliers/view?id=<?= $id ?>" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
