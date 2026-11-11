<?php
/**
 * Shared delivery-note form. Expects:
 *   $f (fields), $rows (items), $clients, $errors, $submit_label, $cancel_url,
 *   $is_edit (bool), $source_html (optional HTML banner)
 */
$rows_json = json_encode(array_map(fn($r) => [
    'description' => $r['description'],
    'quantity'    => (float)$r['quantity'],
    'unit'        => ($r['unit'] ?? 'piece') ?: 'piece',
], $rows), JSON_HEX_TAG | JSON_HEX_AMP);
$input = 'w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400';
?>
<div class="max-w-4xl">
  <div class="mb-5"><a href="<?= $cancel_url ?>" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back</a></div>

  <?php if (!empty($source_html)): ?>
    <div class="bg-amber-50 border border-amber-200 rounded-xl px-5 py-3 mb-5 text-sm text-amber-800"><?= $source_html ?></div>
  <?php endif; ?>
  <?php if (isset($errors['general'])): ?><p class="mb-4 text-sm text-red-600 bg-red-50 border border-red-100 rounded-lg px-4 py-3"><?= $errors['general'] ?></p><?php endif; ?>

  <form method="POST" x-data="deliveryBuilder()">
    <input type="hidden" name="source_quote"   value="<?= (int)($f['quotation_id'] ?? 0) ?>">
    <input type="hidden" name="source_invoice" value="<?= (int)($f['invoice_id'] ?? 0) ?>">
    <?= csrf_field() ?>

    <div class="space-y-5">
      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
        <h2 class="font-semibold text-gray-800">Delivery Details</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Client <span class="text-red-500">*</span></label>
            <select name="client_id" required class="<?= $input ?> <?= isset($errors['client_id']) ? 'border-red-400' : '' ?>">
              <option value="">— Select client —</option>
              <?php foreach ($clients as $cl): ?><option value="<?= $cl['id'] ?>" <?= (int)$f['client_id'] === (int)$cl['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cl['name']) ?><?= $cl['company'] ? ' — ' . htmlspecialchars($cl['company']) : '' ?></option><?php endforeach; ?>
            </select>
            <?php if (isset($errors['client_id'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['client_id'] ?></p><?php endif; ?>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Delivery Date <span class="text-red-500">*</span></label>
            <input type="date" name="delivery_date" value="<?= htmlspecialchars($f['delivery_date']) ?>" class="<?= $input ?>">
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Description / Subject <span class="text-red-500">*</span></label>
          <input type="text" name="title" value="<?= htmlspecialchars($f['title']) ?>" placeholder="What is being delivered?" class="<?= $input ?> <?= isset($errors['title']) ? 'border-red-400' : '' ?>">
          <?php if (isset($errors['title'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['title'] ?></p><?php endif; ?>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Delivered To (contact person)</label>
            <input type="text" name="delivered_to" value="<?= htmlspecialchars($f['delivered_to']) ?>" class="<?= $input ?>">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Delivery Address</label>
            <input type="text" name="delivery_address" value="<?= htmlspecialchars($f['delivery_address']) ?>" class="<?= $input ?>">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Driver / Delivered By</label>
            <input type="text" name="driver_name" value="<?= htmlspecialchars($f['driver_name']) ?>" class="<?= $input ?>">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Vehicle No.</label>
            <input type="text" name="vehicle_no" value="<?= htmlspecialchars($f['vehicle_no']) ?>" class="<?= $input ?>">
          </div>
        </div>
        <?php if ($is_edit): ?>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Status</label>
              <select name="status" class="<?= $input ?>">
                <?php foreach (['draft','dispatched','delivered','cancelled'] as $s): ?><option value="<?= $s ?>" <?= $f['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option><?php endforeach; ?>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Received By</label>
              <input type="text" name="received_by" value="<?= htmlspecialchars($f['received_by']) ?>" class="<?= $input ?>">
            </div>
          </div>
        <?php endif; ?>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-100"><h2 class="font-semibold text-gray-800">Items Delivered</h2></div>
        <?php if (isset($errors['items'])): ?><p class="text-red-500 text-xs px-5 pt-3"><?= $errors['items'] ?></p><?php endif; ?>
        <div class="overflow-x-auto"><table class="w-full text-sm">
          <thead class="bg-gray-50 border-b border-gray-100"><tr>
            <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Description</th>
            <th class="text-center px-3 py-3 text-xs font-semibold text-gray-500 uppercase w-24">Qty</th>
            <th class="text-left px-3 py-3 text-xs font-semibold text-gray-500 uppercase w-28">Unit</th>
            <th class="w-10"></th>
          </tr></thead>
          <tbody>
            <template x-for="(row,idx) in rows" :key="idx">
              <tr class="border-b border-gray-50">
                <td class="px-4 py-2"><input type="text" :name="'desc['+idx+']'" x-model="row.description" placeholder="Item description…" class="w-full border-0 bg-transparent text-sm focus:outline-none focus:ring-1 focus:ring-amber-400 rounded px-1 py-1"></td>
                <td class="px-3 py-2"><input type="number" :name="'qty['+idx+']'" x-model.number="row.quantity" min="0" step="any" class="w-full text-center border border-gray-200 rounded px-2 py-1 text-sm focus:outline-none focus:border-amber-400"></td>
                <td class="px-3 py-2"><input type="text" :name="'unit['+idx+']'" x-model="row.unit" class="w-full border border-gray-200 rounded px-2 py-1 text-sm focus:outline-none focus:border-amber-400"></td>
                <td class="px-2 py-2 text-center"><button type="button" @click="removeRow(idx)" class="text-gray-300 hover:text-red-500 text-lg">×</button></td>
              </tr>
            </template>
          </tbody>
        </table></div>
        <div class="px-4 py-3 border-t border-gray-50"><button type="button" @click="addRow()" class="text-sm text-amber-600 hover:text-amber-700 font-medium">+ Add item</button></div>
      </div>

      <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
        <textarea name="notes" rows="2" class="<?= $input ?> resize-none"><?= htmlspecialchars($f['notes']) ?></textarea>
      </div>

      <div class="flex items-center gap-3">
        <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg"><?= htmlspecialchars($submit_label) ?></button>
        <a href="<?= $cancel_url ?>" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
      </div>
    </div>
  </form>
</div>

<script>
const DN_ROWS = <?= $rows_json ?>;
function deliveryBuilder() {
    return {
        rows: [],
        init() {
            if (DN_ROWS.length) { DN_ROWS.forEach(r => this.addRow(r.description, r.quantity, r.unit)); }
            else { this.addRow(); }
        },
        addRow(description='', quantity=1, unit='piece') { this.rows.push({description, quantity, unit}); },
        removeRow(i) { if (this.rows.length > 1) this.rows.splice(i, 1); }
    };
}
</script>
