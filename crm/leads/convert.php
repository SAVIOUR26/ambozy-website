<?php
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /crm/leads/'); exit; }

$page_title = 'Convert Lead to Client';
$active_nav = 'leads';
require_once __DIR__ . '/../partials/header.php';

$lead = null;
if ($pdo) {
    $s = $pdo->prepare("SELECT * FROM leads WHERE id=?");
    $s->execute([$id]);
    $lead = $s->fetch();
}
if (!$lead) { flash('error','Lead not found.'); redirect('/crm/leads/'); }
if ($lead['client_id']) {
    flash('error','This lead has already been converted.');
    redirect("/crm/leads/view?id=$id");
}

$errors = [];
$f = [
    'name'    => $lead['name'],
    'email'   => $lead['email'] ?? '',
    'phone'   => $lead['phone'] ?? '',
    'company' => $lead['company'] ?? '',
    'address' => '',
    'city'    => '',
    'type'    => $lead['company'] ? 'business' : 'individual',
    'notes'   => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    foreach ($f as $k => $_) { $f[$k] = clean($_POST[$k] ?? ''); }
    if (!$f['name']) $errors['name'] = 'Name is required.';
    if ($f['email'] && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Invalid email.';

    if (empty($errors)) {
        // Create client
        $code = next_doc_number($pdo, 'CLI');
        $pdo->prepare(
            "INSERT INTO clients (code,name,email,phone,company,address,city,type,source,notes,created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)"
        )->execute([
            $code, $f['name'], $f['email']?:null, $f['phone']?:null, $f['company']?:null,
            $f['address']?:null, $f['city']?:null, $f['type'], 'inquiry',
            $f['notes']?:null, $_SESSION['admin_id']??null,
        ]);
        $client_id = (int)$pdo->lastInsertId();

        // Link lead → client, mark won
        $pdo->prepare(
            "UPDATE leads SET client_id=?, status='won', converted_at=NOW() WHERE id=?"
        )->execute([$client_id, $id]);

        log_activity($pdo,'lead_converted',"Lead {$lead['ref']} converted to client $code.",'lead',$id);
        log_activity($pdo,'client_created', "Client $code created from lead {$lead['ref']}.",'client',$client_id);

        flash('success',"Lead converted. Client {$code} created.");
        redirect("/crm/clients/view?id=$client_id");
    }
}
?>

<div class="max-w-2xl">
  <div class="mb-5">
    <a href="/crm/leads/view?id=<?= $id ?>" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Back to Lead
    </a>
  </div>

  <!-- Lead summary -->
  <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-5 flex items-start gap-4">
    <div class="w-10 h-10 rounded-full bg-amber-200 text-amber-700 flex items-center justify-center font-bold shrink-0">
      <?= strtoupper(substr($lead['name'],0,1)) ?>
    </div>
    <div>
      <p class="font-semibold text-amber-900"><?= htmlspecialchars($lead['name']) ?></p>
      <p class="text-sm text-amber-700"><?= htmlspecialchars($lead['ref']) ?>
        <?php if ($lead['service_interest']): ?> · <?= htmlspecialchars($lead['service_interest']) ?><?php endif; ?>
      </p>
    </div>
    <div class="ml-auto text-xs text-amber-600 font-medium">Converting to client →</div>
  </div>

  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800">New Client Details</h2>
      <p class="text-sm text-gray-400 mt-0.5">Pre-filled from the lead. Review and confirm.</p>
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
          <input type="text" name="company" value="<?= htmlspecialchars($f['company']) ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Email Address</label>
          <input type="email" name="email" value="<?= htmlspecialchars($f['email']) ?>"
                 class="w-full border <?= isset($errors['email'])?'border-red-400':'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
          <?php if(isset($errors['email'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['email'] ?></p><?php endif; ?>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Phone Number</label>
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

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Client Type</label>
        <div class="flex gap-4">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" name="type" value="individual" <?= $f['type']==='individual'?'checked':'' ?>
                   class="text-amber-500 focus:ring-amber-400">
            <span class="text-sm text-gray-700">Individual</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" name="type" value="business" <?= $f['type']==='business'?'checked':'' ?>
                   class="text-amber-500 focus:ring-amber-400">
            <span class="text-sm text-gray-700">Business / Organisation</span>
          </label>
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
        <textarea name="notes" rows="2"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 resize-none"><?= htmlspecialchars($f['notes']) ?></textarea>
      </div>

      <div class="flex items-center gap-3 pt-2 border-t border-gray-100">
        <button type="submit"
                class="bg-green-500 hover:bg-green-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors">
          Convert &amp; Create Client
        </button>
        <a href="/crm/leads/view?id=<?= $id ?>" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
