<?php
$page_title = 'New Invoice';
$active_nav = 'invoices';
require_once __DIR__ . '/../partials/header.php';

$clients = []; $catalog = [];
$quote_id = (int)($_GET['quote_id'] ?? 0);
$order_id = (int)($_GET['order_id'] ?? 0);
$client_id_pre = (int)($_GET['client_id'] ?? 0);

$prefill_items = []; $prefill_client = 0; $prefill_title = '';

if ($pdo) {
    $clients = $pdo->query("SELECT id,name,company FROM clients WHERE status='active' ORDER BY name")->fetchAll();
    $catalog = $pdo->query("SELECT id,category,name,unit,unit_price FROM catalog_items WHERE is_active=1 ORDER BY category,name")->fetchAll();

    // Pre-fill from quote
    if ($quote_id) {
        $sq = $pdo->prepare("SELECT * FROM quotations WHERE id=?"); $sq->execute([$quote_id]); $q=$sq->fetch();
        if ($q) {
            $prefill_client = $q['client_id'];
            $prefill_title  = $q['title'];
            $si = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id=? ORDER BY sort_order"); $si->execute([$quote_id]); $prefill_items=$si->fetchAll();
        }
    }
    // Pre-fill from order
    if ($order_id) {
        $so = $pdo->prepare("SELECT * FROM orders WHERE id=?"); $so->execute([$order_id]); $ord=$so->fetch();
        if ($ord) { $prefill_client=$ord['client_id']; $prefill_title=$ord['title']; }
    }
    if ($client_id_pre) $prefill_client = $client_id_pre;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $client_id  = (int)($_POST['client_id'] ?? 0);
    $title      = clean($_POST['title'] ?? '');
    $due_date   = clean($_POST['due_date'] ?? '');
    $discount   = min(100,max(0,(float)($_POST['discount_percent']??0)));
    $tax        = min(100,max(0,(float)($_POST['tax_percent']??0)));
    $notes      = clean($_POST['notes'] ?? '');
    $terms      = clean($_POST['terms'] ?? '');

    $descs  = $_POST['desc']       ?? [];
    $qtys   = $_POST['qty']        ?? [];
    $units  = $_POST['unit']       ?? [];
    $prices = $_POST['unit_price'] ?? [];
    $cids   = $_POST['catalog_id'] ?? [];

    if (!$client_id) $errors['client_id'] = 'Select a client.';
    if (empty(array_filter($descs))) $errors['items'] = 'Add at least one line item.';

    if (empty($errors)) {
        $items = []; $subtotal = 0;
        foreach ($descs as $i => $desc) {
            $desc = trim($desc); if (!$desc) continue;
            $qty   = max(0,(float)($qtys[$i]??1));
            $price = max(0,(float)($prices[$i]??0));
            $tot   = round($qty*$price,2); $subtotal+=$tot;
            $items[]=[ 'catalog_item_id'=>$cids[$i]?:null,'description'=>$desc,'quantity'=>$qty,'unit'=>trim($units[$i]??'piece'),'unit_price'=>$price,'total'=>$tot,'sort_order'=>$i ];
        }
        $disc_amt = round($subtotal*$discount/100,2);
        $tax_base = $subtotal-$disc_amt;
        $tax_amt  = round($tax_base*$tax/100,2);
        $grand    = round($tax_base+$tax_amt,2);

        $inv_no = next_doc_number($pdo,'INV');
        $pdo->prepare(
            "INSERT INTO invoices (invoice_number,client_id,order_id,quotation_id,status,subtotal,discount_percent,tax_percent,total,amount_paid,due_date,notes,terms,created_by)
             VALUES (?,?,?,?,?,?,?,?,?,0,?,?,?,?)"
        )->execute([$inv_no,$client_id,$order_id?:null,$quote_id?:null,'draft',$subtotal,$discount,$tax,$grand,$due_date?:null,$notes?:null,$terms?:null,$_SESSION['admin_id']??null]);
        $iid=(int)$pdo->lastInsertId();

        $ins=$pdo->prepare("INSERT INTO invoice_items (invoice_id,catalog_item_id,description,quantity,unit,unit_price,total,sort_order) VALUES (?,?,?,?,?,?,?,?)");
        foreach($items as $it){ $ins->execute([$iid,$it['catalog_item_id'],$it['description'],$it['quantity'],$it['unit'],$it['unit_price'],$it['total'],$it['sort_order']]); }

        log_activity($pdo,'invoice_created',"Invoice $inv_no created.",'invoice',$iid);
        flash('success',"Invoice $inv_no created.");
        redirect("/crm/invoices/view.php?id=$iid");
    }
}

$catalog_json = json_encode(array_map(fn($c)=>['id'=>$c['id'],'category'=>$c['category'],'name'=>$c['name'],'unit'=>$c['unit'],'unit_price'=>(float)$c['unit_price']],$catalog));
$prefill_json = json_encode(array_map(fn($it)=>['description'=>$it['description'],'quantity'=>(float)$it['quantity'],'unit'=>$it['unit'],'unit_price'=>(float)$it['unit_price'],'catalog_id'=>$it['catalog_item_id']],$prefill_items));
?>
<div class="max-w-4xl">
  <div class="mb-5"><a href="/crm/invoices/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Invoices</a></div>

  <form method="POST" x-data="invoiceBuilder()" x-init="init()">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

      <div class="lg:col-span-2 space-y-5">
        <!-- Header -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
          <h2 class="font-semibold text-gray-800">Invoice Details</h2>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Client <span class="text-red-500">*</span></label>
            <select name="client_id" required class="w-full border <?= isset($errors['client_id'])?'border-red-400':'border-gray-200' ?> rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
              <option value="">— Select client —</option>
              <?php foreach($clients as $cl): ?><option value="<?=$cl['id']?>" <?=$prefill_client==$cl['id']?'selected':''?>><?=htmlspecialchars($cl['name'])?><?=$cl['company']?' — '.htmlspecialchars($cl['company']):''?></option><?php endforeach; ?>
            </select>
            <?php if(isset($errors['client_id'])): ?><p class="text-red-500 text-xs mt-1"><?=$errors['client_id']?></p><?php endif; ?>
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">Due Date</label>
              <input type="date" name="due_date" value="<?= date('Y-m-d',strtotime('+14 days')) ?>"
                     class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1.5">VAT / Tax %</label>
              <input type="number" name="tax_percent" x-model.number="tax" @input="recalc()" min="0" max="100" step="0.5" value="0"
                     class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            </div>
          </div>
        </div>

        <!-- Line items (same builder as quotes) -->
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
          <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
            <h2 class="font-semibold text-gray-800">Line Items</h2>
            <div class="relative" x-data="{ open:false, search:'' }">
              <button type="button" @click="open=!open" class="text-sm bg-slate-100 hover:bg-slate-200 text-slate-700 px-3 py-1.5 rounded-lg font-medium">+ From Catalog</button>
              <div x-show="open" x-cloak @click.outside="open=false" class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-xl border border-gray-200 z-20 overflow-hidden">
                <div class="p-3 border-b border-gray-100"><input type="text" x-model="search" placeholder="Search catalog…" class="w-full border border-gray-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:border-amber-400"></div>
                <div class="max-h-64 overflow-y-auto">
                  <template x-for="item in catalogFiltered(search)" :key="item.id">
                    <button type="button" @click="addFromCatalog(item);open=false;search=''" class="w-full text-left px-4 py-2.5 hover:bg-amber-50 border-b border-gray-50">
                      <p class="text-sm font-medium text-gray-800" x-text="item.name"></p>
                      <p class="text-xs text-gray-400"><span x-text="item.category"></span> · UGX <span x-text="item.unit_price.toLocaleString()"></span></p>
                    </button>
                  </template>
                  <p x-show="catalogFiltered(search).length===0" class="text-center text-gray-400 text-sm py-6">No items found</p>
                </div>
              </div>
            </div>
          </div>
          <?php if(isset($errors['items'])): ?><p class="text-red-500 text-xs px-5 pt-3"><?=$errors['items']?></p><?php endif; ?>
          <div class="overflow-x-auto"><table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100"><tr>
              <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Description</th>
              <th class="text-center px-3 py-3 text-xs font-semibold text-gray-500 uppercase w-20">Qty</th>
              <th class="text-left px-3 py-3 text-xs font-semibold text-gray-500 uppercase w-24">Unit</th>
              <th class="text-right px-3 py-3 text-xs font-semibold text-gray-500 uppercase w-32">Unit Price</th>
              <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase w-32">Total</th>
              <th class="w-10"></th>
            </tr></thead>
            <tbody>
              <template x-for="(row,idx) in rows" :key="idx">
                <tr class="border-b border-gray-50">
                  <td class="px-4 py-2"><input type="text" :name="'desc['+idx+']'" x-model="row.description" placeholder="Item description…" class="w-full border-0 bg-transparent text-sm focus:outline-none focus:ring-1 focus:ring-amber-400 rounded px-1 py-1"><input type="hidden" :name="'catalog_id['+idx+']'" :value="row.catalog_id"></td>
                  <td class="px-3 py-2"><input type="number" :name="'qty['+idx+']'" x-model.number="row.quantity" @input="recalc()" min="0" step="1" class="w-full text-center border border-gray-200 rounded px-2 py-1 text-sm focus:outline-none focus:border-amber-400"></td>
                  <td class="px-3 py-2"><input type="text" :name="'unit['+idx+']'" x-model="row.unit" class="w-full border border-gray-200 rounded px-2 py-1 text-sm focus:outline-none focus:border-amber-400"></td>
                  <td class="px-3 py-2"><input type="number" :name="'unit_price['+idx+']'" x-model.number="row.unit_price" @input="recalc()" min="0" step="100" class="w-full text-right border border-gray-200 rounded px-2 py-1 text-sm focus:outline-none focus:border-amber-400"></td>
                  <td class="px-4 py-2 text-right font-medium text-gray-800">UGX <span x-text="row.total.toLocaleString()"></span></td>
                  <td class="px-2 py-2 text-center"><button type="button" @click="removeRow(idx)" class="text-gray-300 hover:text-red-500 text-lg">×</button></td>
                </tr>
              </template>
            </tbody>
          </table></div>
          <div class="px-4 py-3 border-t border-gray-50"><button type="button" @click="addRow()" class="text-sm text-amber-600 hover:text-amber-700 font-medium">+ Add line item</button></div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes to Client</label>
            <textarea name="notes" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 resize-none"><?=htmlspecialchars($_POST['notes']??'')?></textarea>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1.5">Terms &amp; Conditions</label>
            <textarea name="terms" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 resize-none"><?=htmlspecialchars($_POST['terms']??'Payment due within 14 days. Late payments attract 5% interest.')?></textarea>
          </div>
        </div>
      </div>

      <!-- Right: totals -->
      <div class="space-y-5">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 sticky top-5">
          <h3 class="font-semibold text-gray-800 mb-4">Summary</h3>
          <div class="space-y-3 text-sm">
            <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span class="font-medium">UGX <span x-text="subtotal.toLocaleString()">0</span></span></div>
            <div class="flex items-center justify-between gap-2">
              <span class="text-gray-500 shrink-0">Discount</span>
              <div class="flex items-center gap-1"><input type="number" name="discount_percent" x-model.number="discount" @input="recalc()" min="0" max="100" step="0.5" class="w-16 text-right border border-gray-200 rounded px-2 py-1 text-xs focus:outline-none focus:border-amber-400"><span class="text-gray-400 text-xs">%</span></div>
            </div>
            <div x-show="discount>0" class="flex justify-between text-red-500"><span>- Discount</span><span>UGX <span x-text="discAmt.toLocaleString()"></span></span></div>
            <div x-show="tax>0" class="flex justify-between"><span class="text-gray-500">+ Tax (<span x-text="tax"></span>%)</span><span class="font-medium">UGX <span x-text="taxAmt.toLocaleString()"></span></span></div>
            <div class="flex justify-between border-t border-gray-100 pt-3"><span class="font-semibold text-gray-800">Total Due</span><span class="font-bold text-lg text-amber-600">UGX <span x-text="grandTotal.toLocaleString()">0</span></span></div>
          </div>
          <div class="mt-5 space-y-2">
            <button type="submit" class="w-full bg-amber-500 hover:bg-amber-400 text-white font-semibold text-sm py-2.5 rounded-lg">Save Invoice</button>
            <a href="/crm/invoices/" class="block text-center text-sm text-gray-400 hover:text-gray-600 py-1">Cancel</a>
          </div>
        </div>
      </div>
    </div>
  </form>
</div>

<script>
const CATALOG = <?= $catalog_json ?>;
const PREFILL = <?= $prefill_json ?>;

function invoiceBuilder() {
    return {
        rows:[], subtotal:0, discount:0, tax:0, discAmt:0, taxAmt:0, grandTotal:0,
        init() {
            if (PREFILL.length) { PREFILL.forEach(p => this.addRow(p.description, p.quantity, p.unit, p.unit_price, p.catalog_id)); }
            else { this.addRow(); }
        },
        addRow(desc='',qty=1,unit='piece',price=0,cid=null){ this.rows.push({description:desc,quantity:qty,unit,unit_price:price,total:0,catalog_id:cid}); this.recalc(); },
        addFromCatalog(item){ this.addRow(item.name,1,item.unit,item.unit_price,item.id); },
        removeRow(i){ if(this.rows.length>1){this.rows.splice(i,1);this.recalc();} },
        recalc(){
            this.rows.forEach(r=>{r.total=Math.round(r.quantity*r.unit_price);});
            this.subtotal=this.rows.reduce((s,r)=>s+r.total,0);
            this.discAmt=Math.round(this.subtotal*this.discount/100);
            const tb=this.subtotal-this.discAmt;
            this.taxAmt=Math.round(tb*this.tax/100);
            this.grandTotal=tb+this.taxAmt;
        },
        catalogFiltered(q){ if(!q)return CATALOG; const ql=q.toLowerCase(); return CATALOG.filter(c=>c.name.toLowerCase().includes(ql)||(c.category||'').toLowerCase().includes(ql)); }
    };
}
</script>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
