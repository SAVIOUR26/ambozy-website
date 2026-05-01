<?php
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /crm/leads/'); exit; }

$page_title = 'Lead Detail';
$active_nav = 'leads';
require_once __DIR__ . '/../partials/header.php';

$lead = null;
if ($pdo) {
    $s = $pdo->prepare("SELECT l.*, c.name AS client_name, c.id AS cid FROM leads l LEFT JOIN clients c ON l.client_id=c.id WHERE l.id=?");
    $s->execute([$id]);
    $lead = $s->fetch();
}
if (!$lead) { flash('error','Lead not found.'); redirect('/crm/leads/'); }

$page_title = htmlspecialchars($lead['name']);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $pdo) {
    $new_status = $_POST['new_status'] ?? '';
    $note       = clean($_POST['note'] ?? '');
    $valid      = ['new','contacted','qualified','quoted','won','lost'];

    if (in_array($new_status, $valid, true)) {
        $pdo->prepare("UPDATE leads SET status=? WHERE id=?")->execute([$new_status,$id]);
        if ($note) {
            $existing = $lead['notes'] ?? '';
            $appended = trim("$existing\n[".date('d M Y')."] $note");
            $pdo->prepare("UPDATE leads SET notes=? WHERE id=?")->execute([$appended,$id]);
        }
        log_activity($pdo,'lead_status',"Lead {$lead['ref']} status → $new_status.",'lead',$id);
        flash('success','Lead status updated.');
        redirect("/crm/leads/view?id=$id");
    }
}

$activities = [];
if ($pdo) {
    $a = $pdo->prepare("SELECT * FROM activities WHERE related_type='lead' AND related_id=? ORDER BY created_at DESC LIMIT 15");
    $a->execute([$id]);
    $activities = $a->fetchAll();
}
?>

<!-- Back + actions -->
<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
  <a href="/crm/leads/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Leads
  </a>
  <div class="flex items-center gap-2">
    <?php if (!$lead['client_id']): ?>
      <a href="/crm/leads/convert?id=<?= $id ?>"
         class="text-sm bg-green-500 hover:bg-green-400 text-white px-3 py-2 rounded-lg transition-colors font-medium">
        Convert to Client
      </a>
    <?php else: ?>
      <a href="/crm/clients/view?id=<?= $lead['cid'] ?>"
         class="text-sm bg-white border border-gray-200 text-gray-700 px-3 py-2 rounded-lg transition-colors">
        View Client →
      </a>
    <?php endif; ?>
    <a href="/crm/leads/edit?id=<?= $id ?>"
       class="text-sm bg-amber-500 hover:bg-amber-400 text-white px-3 py-2 rounded-lg transition-colors font-medium">
      Edit
    </a>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

  <!-- Lead detail card -->
  <div class="space-y-4">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
      <div class="flex items-center gap-4 mb-5">
        <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-xl font-bold shrink-0">
          <?= strtoupper(substr($lead['name'],0,1)) ?>
        </div>
        <div>
          <h2 class="text-base font-bold text-gray-900"><?= htmlspecialchars($lead['name']) ?></h2>
          <?php if ($lead['company']): ?>
            <p class="text-sm text-gray-500"><?= htmlspecialchars($lead['company']) ?></p>
          <?php endif; ?>
        </div>
      </div>

      <dl class="space-y-3 text-sm">
        <div class="flex gap-3"><dt class="text-gray-400 w-24 shrink-0">Ref</dt><dd class="font-mono font-medium text-gray-700"><?= htmlspecialchars($lead['ref']) ?></dd></div>
        <div class="flex gap-3"><dt class="text-gray-400 w-24 shrink-0">Status</dt>
          <dd><span class="text-xs px-2 py-0.5 rounded-full font-medium <?= lead_badge($lead['status']) ?>"><?= ucfirst($lead['status']) ?></span></dd>
        </div>
        <?php if ($lead['email']): ?>
          <div class="flex gap-3"><dt class="text-gray-400 w-24 shrink-0">Email</dt>
            <dd><a href="mailto:<?= htmlspecialchars($lead['email']) ?>" class="text-amber-600 hover:underline break-all"><?= htmlspecialchars($lead['email']) ?></a></dd>
          </div>
        <?php endif; ?>
        <?php if ($lead['phone']): ?>
          <div class="flex gap-3"><dt class="text-gray-400 w-24 shrink-0">Phone</dt>
            <dd><a href="tel:<?= htmlspecialchars($lead['phone']) ?>" class="text-gray-700"><?= htmlspecialchars($lead['phone']) ?></a></dd>
          </div>
        <?php endif; ?>
        <?php if ($lead['service_interest']): ?>
          <div class="flex gap-3"><dt class="text-gray-400 w-24 shrink-0">Service</dt><dd class="text-gray-700"><?= htmlspecialchars($lead['service_interest']) ?></dd></div>
        <?php endif; ?>
        <?php if ($lead['budget']): ?>
          <div class="flex gap-3"><dt class="text-gray-400 w-24 shrink-0">Budget</dt><dd class="text-gray-700"><?= htmlspecialchars($lead['budget']) ?></dd></div>
        <?php endif; ?>
        <div class="flex gap-3"><dt class="text-gray-400 w-24 shrink-0">Source</dt><dd class="text-gray-700 capitalize"><?= $lead['source'] ?></dd></div>
        <div class="flex gap-3"><dt class="text-gray-400 w-24 shrink-0">Received</dt><dd class="text-gray-700"><?= date('d M Y', strtotime($lead['created_at'])) ?></dd></div>
        <?php if ($lead['client_name']): ?>
          <div class="flex gap-3 pt-2 border-t border-gray-100">
            <dt class="text-gray-400 w-24 shrink-0">Client</dt>
            <dd><a href="/crm/clients/view?id=<?= $lead['cid'] ?>" class="text-amber-600 hover:underline"><?= htmlspecialchars($lead['client_name']) ?></a></dd>
          </div>
        <?php endif; ?>
      </dl>

      <?php if ($lead['message']): ?>
        <div class="mt-4 pt-4 border-t border-gray-100">
          <p class="text-xs text-gray-400 font-medium mb-1">Message</p>
          <p class="text-sm text-gray-600 leading-relaxed"><?= nl2br(htmlspecialchars($lead['message'])) ?></p>
        </div>
      <?php endif; ?>

      <?php if ($lead['notes']): ?>
        <div class="mt-4 pt-4 border-t border-gray-100">
          <p class="text-xs text-gray-400 font-medium mb-1">Notes</p>
          <p class="text-sm text-gray-600 leading-relaxed"><?= nl2br(htmlspecialchars($lead['notes'])) ?></p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Right: Update status + activity -->
  <div class="xl:col-span-2 space-y-5">

    <!-- Update status -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
      <h3 class="font-semibold text-gray-800 mb-4">Update Status</h3>

      <!-- Pipeline progress bar -->
      <?php
      $pipeline = ['new','contacted','qualified','quoted','won'];
      $current_idx = array_search($lead['status'], $pipeline);
      ?>
      <?php if ($lead['status'] !== 'lost'): ?>
        <div class="flex items-center gap-1 mb-5 overflow-x-auto pb-1">
          <?php foreach ($pipeline as $idx => $step): ?>
            <div class="flex items-center gap-1 shrink-0">
              <div class="flex flex-col items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                            <?= $idx <= ($current_idx !== false ? $current_idx : -1)
                                ? 'bg-amber-500 text-white'
                                : 'bg-gray-100 text-gray-400' ?>">
                  <?= $idx + 1 ?>
                </div>
                <span class="text-xs text-gray-400 mt-1 capitalize"><?= $step ?></span>
              </div>
              <?php if ($idx < count($pipeline)-1): ?>
                <div class="w-8 h-0.5 mb-4 <?= $idx < ($current_idx !== false ? $current_idx : -1) ? 'bg-amber-400' : 'bg-gray-200' ?>"></div>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="mb-5 p-3 bg-red-50 border border-red-100 rounded-lg text-sm text-red-600">This lead was marked as lost.</div>
      <?php endif; ?>

      <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="update_status">
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2">
          <?php foreach (['new','contacted','qualified','quoted','won','lost'] as $s): ?>
            <label class="cursor-pointer">
              <input type="radio" name="new_status" value="<?= $s ?>" class="sr-only peer"
                     <?= $lead['status']===$s?'checked':'' ?>>
              <div class="text-center py-2 px-1 rounded-lg border-2 text-xs font-medium transition-colors
                          <?= $lead['status']===$s
                              ? 'border-amber-500 bg-amber-50 text-amber-700'
                              : 'border-gray-100 text-gray-500 hover:border-amber-300' ?>
                          peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-700">
                <?= ucfirst($s) ?>
              </div>
            </label>
          <?php endforeach; ?>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Add note (optional)</label>
          <textarea name="note" rows="2" placeholder="What happened? Next steps?"
                    class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 resize-none"></textarea>
        </div>
        <button type="submit"
                class="bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold px-5 py-2 rounded-lg transition-colors">
          Update Status
        </button>
      </form>
    </div>

    <!-- Activity -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100">
        <h3 class="font-semibold text-gray-800">Activity</h3>
      </div>
      <?php if (empty($activities)): ?>
        <p class="text-center text-gray-400 text-sm py-8">No activity yet.</p>
      <?php else: ?>
        <div class="divide-y divide-gray-50">
          <?php foreach ($activities as $a): ?>
            <div class="flex items-start gap-3 px-5 py-3.5">
              <div class="w-2 h-2 rounded-full bg-amber-400 mt-1.5 shrink-0"></div>
              <div>
                <p class="text-sm text-gray-700"><?= htmlspecialchars($a['description']) ?></p>
                <p class="text-xs text-gray-400 mt-0.5"><?= date('d M Y H:i', strtotime($a['created_at'])) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
