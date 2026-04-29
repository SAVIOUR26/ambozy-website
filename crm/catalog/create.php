<?php
$page_title = 'Add Catalog Item';
$active_nav = 'catalog';
require_once __DIR__ . '/../partials/header.php';

$errors = [];
$f = ['code'=>'','category'=>'','name'=>'','description'=>'','unit'=>'piece','unit_price'=>'','is_active'=>1];

$categories = ['Branded Merchandise','Branded Giveaways','Books & Magazines','Stationery',
                'Marketing Materials','Signage & Signs','Point of Sale','Packaging Solutions',
                'Awards & Plaques','Outdoor Advertising','Design Services','Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    foreach (['code','category','name','description','unit'] as $k) { $f[$k] = clean($_POST[$k] ?? ''); }
    $f['unit_price'] = (float)($_POST['unit_price'] ?? 0);
    $f['is_active']  = isset($_POST['is_active']) ? 1 : 0;
    $cat_custom      = clean($_POST['category_custom'] ?? '');
    if ($f['category'] === '__custom__' && $cat_custom) { $f['category'] = $cat_custom; }

    if (!$f['name'])                 $errors['name']       = 'Item name is required.';
    if ($f['unit_price'] < 0)        $errors['unit_price'] = 'Price cannot be negative.';

    if (empty($errors)) {
        $pdo->prepare(
            "INSERT INTO catalog_items (code, category, name, description, unit, unit_price, is_active)
             VALUES (?,?,?,?,?,?,?)"
        )->execute([$f['code']?:null, $f['category']?:null, $f['name'],
                    $f['description']?:null, $f['unit'], $f['unit_price'], $f['is_active']]);
        flash('success', "'{$f['name']}' added to catalog.");
        redirect('/crm/catalog/');
    }
}
?>
<div class="max-w-xl">
  <div class="mb-5">
    <a href="/crm/catalog/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Back to Catalog
    </a>
  </div>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800">Add Catalog Item</h2>
      <p class="text-sm text-gray-400 mt-0.5">Items appear in the line-item picker when creating quotes &amp; invoices.</p>
    </div>
    <form method="POST" class="px-6 py-5 space-y-5" x-data="{ customCat: false }">

      <!-- Name -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Item Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="<?= htmlspecialchars($f['name']) ?>" required
               class="w-full border <?= isset($errors['name'])?'border-red-400':'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400"
               placeholder="e.g. A3 Poster (Full Colour)">
        <?php if(isset($errors['name'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['name'] ?></p><?php endif; ?>
      </div>

      <!-- Category -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Category</label>
        <select name="category" @change="customCat = ($event.target.value === '__custom__')"
                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
          <option value="">— None —</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= $f['category']===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
          <?php endforeach; ?>
          <option value="__custom__">+ Custom category…</option>
        </select>
        <input x-show="customCat" type="text" name="category_custom" placeholder="Type category name"
               class="mt-2 w-full border border-amber-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
      </div>

      <!-- Code + Unit -->
      <div class="grid grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Item Code <span class="text-gray-400 font-normal">(optional)</span></label>
          <input type="text" name="code" value="<?= htmlspecialchars($f['code']) ?>"
                 placeholder="e.g. PRT-001"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Unit</label>
          <input type="text" name="unit" value="<?= htmlspecialchars($f['unit']) ?>"
                 placeholder="piece / set / m² / page"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
      </div>

      <!-- Unit Price -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Unit Price (UGX)</label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">UGX</span>
          <input type="number" name="unit_price" value="<?= $f['unit_price'] ?>" min="0" step="100"
                 class="w-full border <?= isset($errors['unit_price'])?'border-red-400':'border-gray-200' ?> rounded-lg pl-12 pr-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
        <?php if(isset($errors['unit_price'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['unit_price'] ?></p><?php endif; ?>
      </div>

      <!-- Description -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Description <span class="text-gray-400 font-normal">(optional)</span></label>
        <textarea name="description" rows="2"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 resize-none"
                  placeholder="Brief description for internal reference"><?= htmlspecialchars($f['description']) ?></textarea>
      </div>

      <!-- Active -->
      <label class="flex items-center gap-3 cursor-pointer">
        <input type="checkbox" name="is_active" value="1" <?= $f['is_active']?'checked':'' ?>
               class="w-4 h-4 rounded border-gray-300 text-amber-500 focus:ring-amber-400">
        <span class="text-sm text-gray-700">Active (visible in quote/invoice builder)</span>
      </label>

      <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors">
          Add to Catalog
        </button>
        <a href="/crm/catalog/" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
