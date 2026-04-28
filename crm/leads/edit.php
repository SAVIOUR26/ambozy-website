<?php
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /crm/leads/'); exit; }

$page_title = 'Edit Lead';
$active_nav = 'leads';
require_once __DIR__ . '/../partials/header.php';

$lead = null;
if ($pdo) {
    $s = $pdo->prepare("SELECT * FROM leads WHERE id=?");
    $s->execute([$id]);
    $lead = $s->fetch();
}
if (!$lead) { flash('error','Lead not found.'); redirect('/crm/leads/'); }

$f = $lead;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    foreach (['name','email','phone','company','service_interest','budget','message','source','status','notes'] as $k) {
        $f[$k] = clean($_POST[$k] ?? '');
    }
    if (!$f['name']) $errors['name'] = 'Name is required.';
    if ($f['email'] && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email.';

    if (empty($errors)) {
        $pdo->prepare(
            "UPDATE leads SET name=?,email=?,phone=?,company=?,service_interest=?,budget=?,message=?,source=?,status=?,notes=? WHERE id=?"
        )->execute([
            $f['name'], $f['email']?:null, $f['phone']?:null, $f['company']?:null,
            $f['service_interest']?:null, $f['budget']?:null, $f['message']?:null,
            $f['source'], $f['status'], $f['notes']?:null, $id,
        ]);
        log_activity($pdo,'lead_updated',"Lead {$lead['ref']} updated.",'lead',$id);
        flash('success','Lead updated.');
        redirect("/crm/leads/view.php?id=$id");
    }
}

$services = ['Branded Merchandise','Branded Giveaways','Books & Magazines','Stationery','Marketing Materials','Signage & Signs','Point of Sale','Packaging Solutions','Awards & Plaques','Outdoor Advertising'];
$budgets  = ['Under 100,000 UGX','100,000 – 500,000 UGX','500,000 – 1,000,000 UGX','1M – 5M UGX','Above 5M UGX'];
?>

<div class="max-w-2xl">
  <div class="mb-5">
    <a href="/crm/leads/view.php?id=<?= $id ?>" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Back to Lead
    </a>
  </div>

  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800">Edit Lead</h2>
      <p class="text-sm font-mono text-gray-400 mt-0.5"><?= htmlspecialchars($lead['ref']) ?></p>
    </div>

    <form method="POST" class="px-6 py-5 space-y-5">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Name <span class="text-red-500">*</span></label>
          <input type="text" name="name" value="<?= htmlspecialchars($f['name']) ?>" required
                 class="w-full border <?= isset($errors['name'])?'border-red-400':'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
          <?php if(isset($errors['name'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['name'] ?></p><?php endif; ?>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Company</label>
          <input type="text" name="company" value="<?= htmlspecialchars($f['company']??'') ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Email</label>
          <input type="email" name="email" value="<?= htmlspecialchars($f['email']??'') ?>"
                 class="w-full border <?= isset($errors['email'])?'border-red-400':'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
          <?php if(isset($errors['email'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['email'] ?></p><?php endif; ?>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone</label>
          <input type="tel" name="phone" value="<?= htmlspecialchars($f['phone']??'') ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Service Interest</label>
          <select name="service_interest" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <option value="">— None —</option>
            <?php foreach ($services as $svc): ?>
              <option value="<?= htmlspecialchars($svc) ?>" <?= ($f['service_interest']??'')===$svc?'selected':'' ?>><?= htmlspecialchars($svc) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Budget</label>
          <select name="budget" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <option value="">— Not specified —</option>
            <?php foreach ($budgets as $b): ?>
              <option value="<?= htmlspecialchars($b) ?>" <?= ($f['budget']??'')===$b?'selected':'' ?>><?= htmlspecialchars($b) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Source</label>
          <select name="source" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <?php foreach (['website'=>'Website','walk-in'=>'Walk-in','referral'=>'Referral','phone'=>'Phone','email'=>'Email','social'=>'Social'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= ($f['source']??'')===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
          <select name="status" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <?php foreach (['new','contacted','qualified','quoted','won','lost'] as $v): ?>
              <option value="<?= $v ?>" <?= ($f['status']??'')===$v?'selected':'' ?>><?= ucfirst($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Message / Project Details</label>
        <textarea name="message" rows="3"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 resize-none"><?= htmlspecialchars($f['message']??'') ?></textarea>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Internal Notes</label>
        <textarea name="notes" rows="2"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 resize-none"><?= htmlspecialchars($f['notes']??'') ?></textarea>
      </div>

      <div class="flex items-center gap-3 pt-2">
        <button type="submit"
                class="bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors">
          Save Changes
        </button>
        <a href="/crm/leads/view.php?id=<?= $id ?>" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
