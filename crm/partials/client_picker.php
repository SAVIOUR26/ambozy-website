<?php
/**
 * Client selector with a "New / one-time client" option.
 * Expects: $clients (id, name, company), $errors, and optionally $client_selected (id).
 * Pair it with client_choice() / create_one_time_client() from includes/crm_helpers.php.
 */
$_cp_sel   = $_POST['client_id'] ?? ($client_selected ?? '');
$_cp_class = 'w-full border rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400';
$_cp_in    = 'w-full border border-gray-200 bg-white rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400';
?>
<div x-data="{ sel: <?= htmlspecialchars(json_encode((string)$_cp_sel), ENT_QUOTES) ?> }">
  <select name="client_id" x-model="sel" required class="<?= $_cp_class ?> <?= isset($errors['client_id']) ? 'border-red-400' : 'border-gray-200' ?>">
    <option value="">— Select client —</option>
    <option value="new">＋ New / one-time client (type a name)</option>
    <?php foreach ($clients as $_cl): ?>
      <option value="<?= $_cl['id'] ?>"><?= htmlspecialchars($_cl['name']) ?><?= !empty($_cl['company']) ? ' — ' . htmlspecialchars($_cl['company']) : '' ?></option>
    <?php endforeach; ?>
  </select>
  <?php if (isset($errors['client_id'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['client_id'] ?></p><?php endif; ?>

  <div x-show="sel === 'new'" x-cloak class="mt-3 bg-amber-50 border border-amber-200 rounded-lg p-4 space-y-3">
    <p class="text-xs text-amber-800">Not in the system yet? Type the client's details — they are saved automatically in <strong>Clients</strong> so you can reuse them later.</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
      <div class="sm:col-span-2">
        <label class="block text-xs font-medium text-gray-600 mb-1">Client name <span class="text-red-500">*</span></label>
        <input type="text" name="new_client_name" :required="sel === 'new'" value="<?= htmlspecialchars($_POST['new_client_name'] ?? '') ?>" placeholder="e.g. John Mukasa" class="<?= $_cp_in ?>">
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Phone <span class="text-gray-400">(optional)</span></label>
        <input type="tel" name="new_client_phone" value="<?= htmlspecialchars($_POST['new_client_phone'] ?? '') ?>" class="<?= $_cp_in ?>">
      </div>
      <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Email <span class="text-gray-400">(optional)</span></label>
        <input type="email" name="new_client_email" value="<?= htmlspecialchars($_POST['new_client_email'] ?? '') ?>" class="<?= $_cp_in ?>">
      </div>
      <div class="sm:col-span-2">
        <label class="block text-xs font-medium text-gray-600 mb-1">Company <span class="text-gray-400">(optional)</span></label>
        <input type="text" name="new_client_company" value="<?= htmlspecialchars($_POST['new_client_company'] ?? '') ?>" class="<?= $_cp_in ?>">
      </div>
    </div>
  </div>
</div>
