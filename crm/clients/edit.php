<?php
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /crm/clients/'); exit; }

$page_title = 'Edit Client';
$active_nav = 'clients';
require_once __DIR__ . '/../partials/header.php';

$errors = [];
$client = null;

if ($pdo) {
    $s = $pdo->prepare("SELECT * FROM clients WHERE id = ?");
    $s->execute([$id]);
    $client = $s->fetch();
}
if (!$client) { flash('error','Client not found.'); redirect('/crm/clients/'); }

$f = $client;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    foreach (['name','email','phone','company','address','city','type','status','source','notes'] as $k) {
        $f[$k] = clean($_POST[$k] ?? '');
    }
    if (!$f['name']) $errors['name'] = 'Name is required.';
    if ($f['email'] && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email.';

    if (empty($errors)) {
        $pdo->prepare(
            "UPDATE clients SET name=?,email=?,phone=?,company=?,address=?,city=?,type=?,status=?,source=?,notes=? WHERE id=?"
        )->execute([
            $f['name'], $f['email']?:null, $f['phone']?:null, $f['company']?:null,
            $f['address']?:null, $f['city']?:null, $f['type'], $f['status'], $f['source'],
            $f['notes']?:null, $id,
        ]);
        log_activity($pdo,'client_updated',"Client {$client['code']} updated.",'client',$id);
        flash('success','Client updated successfully.');
        redirect("/crm/clients/view?id=$id");
    }
}
?>

<div class="max-w-2xl">
  <div class="mb-5">
    <a href="/crm/clients/view?id=<?= $id ?>" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Back to <?= htmlspecialchars($client['name']) ?>
    </a>
  </div>

  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
      <div>
        <h2 class="font-semibold text-gray-800">Edit Client</h2>
        <p class="text-sm text-gray-400 font-mono mt-0.5"><?= htmlspecialchars($client['code']) ?></p>
      </div>
    </div>

    <form method="POST" class="px-6 py-5 space-y-5">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Full Name <span class="text-red-500">*</span></label>
          <input type="text" name="name" value="<?= htmlspecialchars($f['name']) ?>" required
                 class="w-full border <?= isset($errors['name'])?'border-red-400':'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
          <?php if(isset($errors['name'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['name'] ?></p><?php endif; ?>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Company / Organisation</label>
          <input type="text" name="company" value="<?= htmlspecialchars($f['company']??'') ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
          <input type="email" name="email" value="<?= htmlspecialchars($f['email']??'') ?>"
                 class="w-full border <?= isset($errors['email'])?'border-red-400':'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
          <?php if(isset($errors['email'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['email'] ?></p><?php endif; ?>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone Number</label>
          <input type="tel" name="phone" value="<?= htmlspecialchars($f['phone']??'') ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div class="sm:col-span-2">
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Address</label>
          <input type="text" name="address" value="<?= htmlspecialchars($f['address']??'') ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">City</label>
          <input type="text" name="city" value="<?= htmlspecialchars($f['city']??'') ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Type</label>
          <select name="type" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <option value="individual" <?= $f['type']==='individual'?'selected':'' ?>>Individual</option>
            <option value="business"   <?= $f['type']==='business'  ?'selected':'' ?>>Business</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
          <select name="status" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <option value="active"   <?= $f['status']==='active'  ?'selected':'' ?>>Active</option>
            <option value="inactive" <?= $f['status']==='inactive'?'selected':'' ?>>Inactive</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Source</label>
          <select name="source" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <?php foreach (['manual'=>'Manual','inquiry'=>'Website','referral'=>'Referral','walk-in'=>'Walk-in','online'=>'Online'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= $f['source']===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
        <textarea name="notes" rows="3"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 resize-none"><?= htmlspecialchars($f['notes']??'') ?></textarea>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button type="submit"
                class="bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors">
          Save Changes
        </button>
        <a href="/crm/clients/view?id=<?= $id ?>" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
