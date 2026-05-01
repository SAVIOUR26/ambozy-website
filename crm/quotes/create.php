<?php
$page_title = 'New Quotation';
$active_nav = 'quotes';
require_once __DIR__ . '/../partials/header.php';

$errors  = [];
$clients = [];
$catalog = [];
$lead_id = (int)($_GET['lead_id'] ?? 0);
$client_id_pre = (int)($_GET['client_id'] ?? 0);

if ($pdo) {
    $clients = $pdo->query("SELECT id, name, company FROM clients WHERE status='active' ORDER BY name")->fetchAll();
    $catalog = $pdo->query("SELECT id, category, name, unit, unit_price FROM catalog_items WHERE is_active=1 ORDER BY category, name")->fetchAll();
}

// Handle form submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $client_id  = (int)($_POST['client_id'] ?? 0);
    $title      = clean($_POST['title'] ?? '');
    $valid_until= clean($_POST['valid_until'] ?? '');
    $discount   = min(100, max(0, (float)($_POST['discount_percent'] ?? 0)));
    $tax        = min(100, max(0, (float)($_POST['tax_percent'] ?? 0)));
    $notes      = clean($_POST['notes'] ?? '');
    $terms      = clean($_POST['terms'] ?? '');
    $status     = 'draft';

    $descs  = $_POST['desc']       ?? [];
    $qtys   = $_POST['qty']        ?? [];
    $units  = $_POST['unit']       ?? [];
    $prices = $_POST['unit_price'] ?? [];
    $cids   = $_POST['catalog_id'] ?? [];

    if (!$client_id) $errors['client_id'] = 'Select a client.';
    if (!$title)     $errors['title']     = 'Title / subject is required.';
    if (empty($descs) || !array_filter($descs)) $errors['items'] = 'Add at least one line item.';

    if (empty($errors)) {
        // Build items & totals
        $items    = [];
        $subtotal = 0;
        foreach ($descs as $i => $desc) {
            $desc = trim($desc);
            if (!$desc) continue;
            $qty   = max(0, (float)($qtys[$i] ?? 1));
            $price = max(0, (float)($prices[$i] ?? 0));
            $total = round($qty * $price, 2);
            $subtotal += $total;
            $items[] = [
                'catalog_item_id' => $cids[$i] ?: null,
                'description'     => $desc,
                'quantity'        => $qty,
                'unit'            => trim($units[$i] ?? 'piece'),
                'unit_price'      => $price,
                'total'           => $total,
                'sort_order'      => $i,
            ];
        }
        $disc_amt  = round($subtotal * $discount / 100, 2);
        $tax_base  = $subtotal - $disc_amt;
        $tax_amt   = round($tax_base * $tax / 100, 2);
        $grand     = round($tax_base + $tax_amt, 2);

        $quote_no = next_doc_number($pdo, 'QUO');
        $pdo->prepare(
            "INSERT INTO quotations (quote_number,client_id,lead_id,title,status,valid_until,subtotal,discount_percent,tax_percent,total,notes,terms,created_by)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
        )->execute([
            $quote_no, $client_id, $lead_id ?: null, $title, $status,
            $valid_until ?: null, $subtotal, $discount, $tax, $grand,
            $notes ?: null, $terms ?: null, $_SESSION['admin_id'] ?? null,
        ]);
        $qid = (int)$pdo->lastInsertId();

        $ins = $pdo->prepare(
            "INSERT INTO quotation_items (quotation_id,catalog_item_id,description,quantity,unit,unit_price,total,sort_order)
             VALUES (?,?,?,?,?,?,?,?)"
        );
        foreach ($items as $it) {
            $ins->execute([$qid, $it['catalog_item_id'], $it['description'],
                           $it['quantity'], $it['unit'], $it['unit_price'], $it['total'], $it['sort_order']]);
        }

        if ($lead_id) {
            $pdo->prepare("UPDATE leads SET status='quoted' WHERE id=?")->execute([$lead_id]);
        }
        log_activity($pdo, 'quote_created', "Quote $quote_no created.", 'quotation', $qid);
        flash('success', "Quote $quote_no created.");
        redirect("/crm/quotes/view?id=$qid");
    }
}

// JSON for JS picker
$catalog_json = json_encode(array_map(fn($c) => [
    'id'         => $c['id'],
    'category'   => $c['category'],
    'name'       => $c['name'],
    'unit'       => $c['unit'],
    'unit_price' => (float)$c['unit_price'],
], $catalog));
?>
<div class="max-w-4xl">
  <div class="mb-5">
    <a href="/crm/quotes/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
      <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      Quotations
    </a>
  </div>

  <form method="POST" x-data="quoteBuilder()" x-init="init()">

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

      <!-- Left: header fields -->
      <div class="lg:col-span-2 space-y-5">

        <!-- Client + Title -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
          <h2 class="font-semibold text-gray-800">Quote Details</h2>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Client <span class="text-red-500">*</span></label>
            <select name="client_id" required class="w-full border <?= isset($errors['client_id'])?'border-red-400':'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
              <option value="">— Select client —</option>
              <?php foreach ($clients as $cl): ?>
                <option value="<?= $cl['id'] ?>" <?= $client_id_pre===$cl['id']?'selected':'' ?>>
                  <?= htmlspecialchars($cl['name']) ?><?= $cl['company']?' — '.htmlspecialchars($cl['company']):'' ?>
                </option>
              <?php endforeach; ?>
            </select>
            <?php if(isset($errors['client_id'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['client_id'] ?></p><?php endif; ?>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Subject / Title <span class="text-red-500">*</span></label>
            <input type="text" name="title" value="<?= htmlspecialchars($_POST['title']??'') ?>" required
                   placeholder="e.g. Branded T-Shirts — 200 pcs"
                   class="w-full border <?= isset($errors['title'])?'border-red-400':'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
            <?php if(isset($errors['title'])): ?><p class="text-red-500 text-xs mt-1"><?= $errors['title'] ?></p><?php endif; ?>
          </div>

          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Valid Until</label>
              <input type="date" name="valid_until" value="<?= htmlspecialchars($_POST['valid_until'] ?? date('Y-m-d', strtotime('+14 days'))) ?>"
                     class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">VAT / Tax %</label>
              <input type="number" name="tax_percent" x-model.number="tax" @input="recalc()" min="0" max="100" step="0.5"
                     value="<?= $_POST['tax_percent'] ?? 0 ?>"
                     class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            </div>
          </div>
        </div>

        <!-- Line items -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Line Items</h2>
            <!-- Catalog picker -->
            <div class="relative" x-data="{ open: false, search: '' }">
              <button type="button" @click="open=!open"
                      class="text-sm bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded-lg font-medium transition-colors">
                + From Catalog
              </button>
              <div x-show="open" x-cloak @click.outside="open=false"
                   class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-gray-200 z-20 overflow-hidden">
                <div class="p-3 border-b border-gray-100">
                  <input type="text" x-model="search" placeholder="Search catalog…"
                         class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400">
                </div>
                <div class="max-h-64 overflow-y-auto">
                  <template x-for="item in catalogFiltered(search)" :key="item.id">
                    <button type="button"
                            @click="addFromCatalog(item); open=false; search=''"
                            class="w-full text-left px-4 py-2.5 hover:bg-amber-50 transition-colors border-b border-gray-50">
                      <p class="text-sm font-medium text-gray-800" x-text="item.name"></p>
                      <p class="text-xs text-gray-400"><span x-text="item.category"></span> · UGX <span x-text="item.unit_price.toLocaleString()"></span> / <span x-text="item.unit"></span></p>
                    </button>
                  </template>
                  <p x-show="catalogFiltered(search).length===0" class="text-center text-gray-400 text-sm py-6">No items found</p>
                </div>
              </div>
            </div>
          </div>

          <?php if(isset($errors['items'])): ?>
            <p class="text-red-500 text-xs px-5 pt-3"><?= $errors['items'] ?></p>
          <?php endif; ?>

          <!-- Items table -->
          <div class="overflow-x-auto">
            <table class="w-full text-sm">
              <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                  <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Description</th>
                  <th class="text-center px-3 py-3 text-xs font-semibold text-gray-500 uppercase w-20">Qty</th>
                  <th class="text-left px-3 py-3 text-xs font-semibold text-gray-500 uppercase w-24">Unit</th>
                  <th class="text-right px-3 py-3 text-xs font-semibold text-gray-500 uppercase w-32">Unit Price</th>
                  <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase w-32">Total</th>
                  <th class="w-10"></th>
                </tr>
              </thead>
              <tbody>
                <template x-for="(row, idx) in rows" :key="idx">
                  <tr class="border-b border-gray-50">
                    <td class="px-4 py-2">
                      <input type="text" :name="'desc['+idx+']'" x-model="row.description"
                             placeholder="Item description…"
                             class="w-full border-0 bg-transparent text-sm text-gray-800 focus:outline-none focus:ring-1 focus:ring-amber-400 rounded px-1 py-1">
                      <input type="hidden" :name="'catalog_id['+idx+']'" :value="row.catalog_id">
                    </td>
                    <td class="px-3 py-2">
                      <input type="number" :name="'qty['+idx+']'" x-model.number="row.quantity"
                             @input="recalc()" min="0" step="1"
                             class="w-full text-center border border-gray-200 rounded px-2 py-1 text-sm focus:outline-none focus:border-amber-400">
                    </td>
                    <td class="px-3 py-2">
                      <input type="text" :name="'unit['+idx+']'" x-model="row.unit"
                             class="w-full border border-gray-200 rounded px-2 py-1 text-sm focus:outline-none focus:border-amber-400">
                    </td>
                    <td class="px-3 py-2">
                      <input type="number" :name="'unit_price['+idx+']'" x-model.number="row.unit_price"
                             @input="recalc()" min="0" step="100"
                             class="w-full text-right border border-gray-200 rounded px-2 py-1 text-sm focus:outline-none focus:border-amber-400">
                    </td>
                    <td class="px-4 py-2 text-right font-medium text-gray-800">
                      UGX <span x-text="row.total.toLocaleString()"></span>
                    </td>
                    <td class="px-2 py-2 text-center">
                      <button type="button" @click="removeRow(idx)"
                              class="text-gray-300 hover:text-red-500 transition-colors text-lg leading-none">×</button>
                    </td>
                  </tr>
                </template>
              </tbody>
            </table>
          </div>
          <div class="px-4 py-3 border-t border-gray-50">
            <button type="button" @click="addRow()"
                    class="text-sm text-amber-600 hover:text-amber-700 font-medium">
              + Add line item
            </button>
          </div>
        </div>

        <!-- Notes & Terms -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes to Client</label>
            <textarea name="notes" rows="2" placeholder="Delivery time, special instructions…"
                      class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 resize-none"><?= htmlspecialchars($_POST['notes']??'') ?></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Terms &amp; Conditions</label>
            <textarea name="terms" rows="2" placeholder="Payment terms, validity conditions…"
                      class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 resize-none"><?= htmlspecialchars($_POST['terms'] ?? '50% deposit required. Quote valid for 14 days.') ?></textarea>
          </div>
        </div>

      </div><!-- /left -->

      <!-- Right: totals + submit -->
      <div class="space-y-5">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 sticky top-5">
          <h3 class="font-semibold text-gray-800 mb-4">Summary</h3>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-500">Subtotal</span>
              <span class="font-medium text-gray-800">UGX <span x-text="subtotal.toLocaleString()">0</span></span>
            </div>
            <div class="flex items-center justify-between gap-2">
              <span class="text-gray-500 shrink-0">Discount</span>
              <div class="flex items-center gap-1">
                <input type="number" name="discount_percent" x-model.number="discount" @input="recalc()"
                       min="0" max="100" step="0.5" class="w-16 text-right border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:border-amber-400">
                <span class="text-gray-400 text-xs">%</span>
              </div>
            </div>
            <div x-show="discount>0" class="flex justify-between text-red-500">
              <span>- Discount</span>
              <span>UGX <span x-text="discAmt.toLocaleString()"></span></span>
            </div>
            <div x-show="tax>0" class="flex justify-between">
              <span class="text-gray-500">+ Tax (<span x-text="tax"></span>%)</span>
              <span class="font-medium">UGX <span x-text="taxAmt.toLocaleString()"></span></span>
            </div>
            <div class="flex justify-between border-t border-gray-100 pt-3">
              <span class="font-semibold text-gray-800">Grand Total</span>
              <span class="font-bold text-lg text-amber-600">UGX <span x-text="grandTotal.toLocaleString()">0</span></span>
            </div>
          </div>
          <div class="mt-5 space-y-2">
            <button type="submit" name="save_status" value="draft"
                    class="w-full bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm py-2.5 rounded-lg transition-colors">
              Save as Draft
            </button>
            <a href="/crm/quotes/" class="block text-center text-sm text-gray-400 hover:text-gray-600 py-1">Cancel</a>
          </div>
        </div>
      </div><!-- /right -->

    </div><!-- /grid -->
  </form>
</div>

<script>
const CATALOG = <?= $catalog_json ?>;

function quoteBuilder() {
    return {
        rows: [],
        subtotal: 0, discount: 0, tax: 0, discAmt: 0, taxAmt: 0, grandTotal: 0,

        init() {
            this.addRow();
        },
        addRow(desc='', qty=1, unit='piece', price=0, cid=null) {
            this.rows.push({ description: desc, quantity: qty, unit, unit_price: price, total: 0, catalog_id: cid });
            this.recalc();
        },
        addFromCatalog(item) {
            this.addRow(item.name, 1, item.unit, item.unit_price, item.id);
        },
        removeRow(i) {
            if (this.rows.length > 1) { this.rows.splice(i, 1); this.recalc(); }
        },
        recalc() {
            this.rows.forEach(r => { r.total = Math.round(r.quantity * r.unit_price); });
            this.subtotal  = this.rows.reduce((s,r) => s + r.total, 0);
            this.discAmt   = Math.round(this.subtotal * this.discount / 100);
            const taxBase  = this.subtotal - this.discAmt;
            this.taxAmt    = Math.round(taxBase * this.tax / 100);
            this.grandTotal= taxBase + this.taxAmt;
        },
        catalogFiltered(q) {
            if (!q) return CATALOG;
            const ql = q.toLowerCase();
            return CATALOG.filter(c => c.name.toLowerCase().includes(ql) || (c.category||'').toLowerCase().includes(ql));
        }
    };
}
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>
