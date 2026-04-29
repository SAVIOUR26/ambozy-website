<?php
$page_title = 'New Client';
$active_nav = 'clients';
require_once __DIR__ . '/../partials/header.php';

$errors = [];
$f = ['name'=>'','email'=>'','phone'=>'','company'=>'','address'=>'','city'=>'','type'=>'individual','source'=>'manual','notes'=>''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    foreach ($f as $k => $_) {
        $f[$k] = clean($_POST[$k] ?? '');
    }

    if (!$f['name']) $errors['name'] = 'Name is required.';
    if ($f['email'] && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email address.';

    if (empty($errors)) {
        $code = next_doc_number($pdo, 'CLI');
        $stmt = $pdo->prepare(
            "INSERT INTO clients (code, name, email, phone, company, address, city, type, source, notes, created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)"
        );
        $stmt->execute([
            $code, $f['name'], $f['email'] ?: null, $f['phone'] ?: null,
            $f['company'] ?: null, $f['address'] ?: null, $f['city'] ?: null,
            $f['type'], $f['source'], $f['notes'] ?: null,
            $_SESSION['admin_id'] ?? null,
        ]);
        $id = (int)$pdo->lastInsertId();
        log_activity($pdo, 'client_created', "Client {$code} — {$f['name']} created.", 'client', $id);
        flash('success', "Client {$code} created successfully.");
        redirect("/crm/clients/view.php?id=$id");
    }
}
?>

<div class="max-w-2xl">
  <div class="mb-5">
    <a href="/crm/clients/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Back to Clients
    </a>
  </div>

  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800">Client Information</h2>
      <p class="text-sm text-gray-400 mt-0.5">A client code will be auto-generated (e.g. CLI-2026-0001)</p>
    </div>

    <form method="POST" class="px-6 py-5 space-y-5">

      <!-- Name + Company -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">
            Full Name <span class="text-red-500">*</span>
          </label>
          <input type="text" name="name" value="<?= htmlspecialchars($f['name']) ?>" required
                 class="w-full border <?= isset($errors['name']) ? 'border-red-400' : 'border-gray-200' ?>
                        rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
          <?php if (isset($errors['name'])): ?>
            <p class="text-red-500 text-xs mt-1"><?= $errors['name'] ?></p>
          <?php endif; ?>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Company / Organisation</label>
          <input type="text" name="company" value="<?= htmlspecialchars($f['company']) ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
      </div>

      <!-- Email + Phone -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
          <input type="email" name="email" value="<?= htmlspecialchars($f['email']) ?>"
                 class="w-full border <?= isset($errors['email']) ? 'border-red-400' : 'border-gray-200' ?>
                        rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
          <?php if (isset($errors['email'])): ?>
            <p class="text-red-500 text-xs mt-1"><?= $errors['email'] ?></p>
          <?php endif; ?>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone Number</label>
          <input type="tel" name="phone" value="<?= htmlspecialchars($f['phone']) ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
      </div>

      <!-- Address + City -->
      <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="sm:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Address</label>
          <input type="text" name="address" value="<?= htmlspecialchars($f['address']) ?>"
                 placeholder="Plot / Street"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">City</label>
          <input type="text" name="city" value="<?= htmlspecialchars($f['city']) ?>"
                 placeholder="Kampala"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
      </div>

      <!-- Type + Source -->
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Client Type</label>
          <select name="type" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <option value="individual" <?= $f['type']==='individual'?'selected':'' ?>>Individual</option>
            <option value="business"   <?= $f['type']==='business'  ?'selected':'' ?>>Business / Organisation</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Source / How they found us</label>
          <select name="source" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <?php foreach (['manual'=>'Manual Entry','inquiry'=>'Website Inquiry','referral'=>'Referral','walk-in'=>'Walk-in','online'=>'Online'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= $f['source']===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- Notes -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
        <textarea name="notes" rows="3"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400
                         resize-none"><?= htmlspecialchars($f['notes']) ?></textarea>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button type="submit"
                class="bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors">
          Save Client
        </button>
        <a href="/crm/clients/" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
