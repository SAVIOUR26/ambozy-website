<?php
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /crm/catalog/'); exit; }
$page_title = 'Edit Catalog Item';
$active_nav = 'catalog';
require_once __DIR__ . '/../partials/header.php';

$item = null;
if ($pdo) { $s = $pdo->prepare("SELECT * FROM catalog_items WHERE id=?"); $s->execute([$id]); $item = $s->fetch(); }
if (!$item) { flash('error','Item not found.'); redirect('/crm/catalog/'); }

$f = $item; $errors = [];

$categories = ['Branded Merchandise','Branded Giveaways','Books & Magazines','Stationery',
                'Marketing Materials','Signage & Signs','Point of Sale','Packaging Solutions',
                'Awards & Plaques','Outdoor Advertising','Design Services','Other'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    foreach (['code','category','name','description','unit'] as $k) { $f[$k] = clean($_POST[$k] ?? ''); }
    $f['unit_price'] = (float)($_POST['unit_price'] ?? 0);
    $f['is_active']  = isset($_POST['is_active']) ? 1 : 0;
    $cat_custom = clean($_POST['category_custom'] ?? '');
    if ($f['category'] === '__custom__' && $cat_custom) { $f['category'] = $cat_custom; }
    if (!$f['name']) $errors['name'] = 'Item name is required.';

    if (empty($errors)) {
        $pdo->prepare("UPDATE catalog_items SET code=?,category=?,name=?,description=?,unit=?,unit_price=?,is_active=? WHERE id=?")
            ->execute([$f['code']?:null,$f['category']?:null,$f['name'],$f['description']?:null,$f['unit'],$f['unit_price'],$f['is_active'],$id]);
        flash('success','Item updated.');
        redirect('/crm/catalog/');
    }
}

$is_known_cat = in_array($f['category'], $categories);
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
      <h2 class="font-semibold text-gray-800">Edit Item</h2>
    </div>
    <form method="POST" class="px-6 py-5 space-y-5"
          x-data="{ customCat: <?= !$is_known_cat && $f['category'] ? 'true' : 'false' ?> }">

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Item Name <span class="text-red-500">*</span></label>
        <input type="text" name="name" value="<?= htmlspecialchars($f['name']) ?>" required
               class="w-full border <?= isset($errors['name'])?'border-red-400':'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        <?php if(isset($errors['name'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['name'] ?></p><?php endif; ?>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Category</label>
        <select name="category" @change="customCat = ($event.target.value === '__custom__')"
                class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
          <option value="">— None —</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= $f['category']===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
          <?php endforeach; ?>
          <option value="__custom__" <?= !$is_known_cat && $f['category']?'selected':'' ?>>
            <?= !$is_known_cat && $f['category'] ? htmlspecialchars($f['category']) : '+ Custom category…' ?>
          </option>
        </select>
        <input x-show="customCat" type="text" name="category_custom"
               value="<?= !$is_known_cat ? htmlspecialchars($f['category']??'') : '' ?>"
               placeholder="Type category name"
               class="mt-2 w-full border border-amber-300 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
      </div>

      <div class="grid grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Item Code</label>
          <input type="text" name="code" value="<?= htmlspecialchars($f['code']??'') ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Unit</label>
          <input type="text" name="unit" value="<?= htmlspecialchars($f['unit']) ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Unit Price (UGX)</label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">UGX</span>
          <input type="number" name="unit_price" value="<?= $f['unit_price'] ?>" min="0" step="100"
                 class="w-full border border-gray-200 rounded-lg pl-12 pr-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Description</label>
        <textarea name="description" rows="2"
                  class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400 resize-none"><?= htmlspecialchars($f['description']??'') ?></textarea>
      </div>

      <label class="flex items-center gap-3 cursor-pointer">
        <input type="checkbox" name="is_active" value="1" <?= $f['is_active']?'checked':'' ?>
               class="w-4 h-4 rounded border-gray-300 text-amber-500 focus:ring-amber-400">
        <span class="text-sm text-gray-700">Active</span>
      </label>

      <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors">
          Save Changes
        </button>
        <a href="/crm/catalog/" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
