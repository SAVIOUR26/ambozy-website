<?php
$id=(int)($_GET['id']??0); if(!$id){header('Location: /crm/invoices/');exit;}
$page_title='Invoice'; $active_nav='invoices';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../../includes/crm_email.php';

$invoice=null;$items=[];$payments=[];
if($pdo){
    $s=$pdo->prepare("SELECT i.*,c.name client_name,c.email client_email,c.phone client_phone,c.company client_company FROM invoices i JOIN clients c ON i.client_id=c.id WHERE i.id=?");
    $s->execute([$id]);$invoice=$s->fetch();
    if($invoice){
        $si=$pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id=? ORDER BY sort_order");$si->execute([$id]);$items=$si->fetchAll();
        $sp=$pdo->prepare("SELECT * FROM payments WHERE invoice_id=? ORDER BY payment_date DESC");$sp->execute([$id]);$payments=$sp->fetchAll();
    }
}
if(!$invoice){flash('error','Invoice not found.');redirect('/crm/invoices/');}
$page_title=$invoice['invoice_number'];
$balance=$invoice['total']-$invoice['amount_paid'];

if($_SERVER['REQUEST_METHOD']==='POST'&&$pdo){
    $act=$_POST['action']??'';
    if($act==='change_status'){
        $ns=$_POST['new_status']??'';
        if(in_array($ns,['draft','sent','partial','paid','overdue','cancelled'])){
            $paid_at=($ns==='paid')?',paid_at=NOW()':'';
            $sent_at=($ns==='sent'&&$invoice['status']==='draft')?',sent_at=NOW()':'';
            $pdo->prepare("UPDATE invoices SET status=?$paid_at$sent_at WHERE id=?")->execute([$ns,$id]);
            log_activity($pdo,'invoice_status',"Invoice {$invoice['invoice_number']} → $ns.",'invoice',$id);
            flash('success',"Status updated to $ns.");
            redirect("/crm/invoices/view?id=$id");
        }
    }
}
?>

<div class="flex items-center justify-between mb-5 flex-wrap gap-3">
  <a href="/crm/invoices/" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1">
    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    Invoices
  </a>
  <div class="flex items-center gap-2 flex-wrap">
    <a href="/crm/invoices/pdf?id=<?=$id?>" target="_blank" class="text-sm bg-white border border-gray-200 hover:border-indigo-400 text-gray-700 px-3 py-2 rounded-lg">🖨 Print / PDF</a>
    <?php if(in_array($invoice['status'],['draft','sent','partial','overdue'])): ?>
      <a href="/crm/invoices/send?id=<?=$id?>" class="text-sm bg-blue-500 hover:bg-blue-400 text-white px-3 py-2 rounded-lg font-medium">✉ Send to Client</a>
    <?php endif; ?>
    <?php if(!in_array($invoice['status'],['paid','cancelled'])): ?>
      <a href="/crm/invoices/payment?id=<?=$id?>" class="text-sm bg-green-500 hover:bg-green-400 text-white px-3 py-2 rounded-lg font-medium">+ Record Payment</a>
    <?php endif; ?>
  </div>
</div>

<div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

  <div class="xl:col-span-2 space-y-5">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="p-6 border-b border-gray-100 flex items-start justify-between gap-4 flex-wrap">
        <div>
          <p class="text-xs text-gray-400 uppercase tracking-wider font-medium mb-1">Invoice</p>
          <h2 class="text-2xl font-bold text-gray-900 font-mono"><?=htmlspecialchars($invoice['invoice_number'])?></h2>
        </div>
        <span class="text-sm px-3 py-1 rounded-full font-semibold <?=invoice_badge($invoice['status'])?>"><?=ucfirst($invoice['status'])?></span>
      </div>

      <div class="grid grid-cols-2 md:grid-cols-4 divide-x divide-y md:divide-y-0 divide-gray-100 border-b border-gray-100">
        <?php foreach(['Client'=>htmlspecialchars($invoice['client_name']),'Issued'=>date('d M Y',strtotime($invoice['created_at'])),'Due'=>$invoice['due_date']?date('d M Y',strtotime($invoice['due_date'])):'—','Sent'=>$invoice['sent_at']?date('d M Y',strtotime($invoice['sent_at'])):'—'] as $l=>$v): ?>
          <div class="px-5 py-3.5"><p class="text-xs text-gray-400 font-medium"><?=$l?></p><p class="text-sm font-medium text-gray-800 mt-0.5"><?=$v?></p></div>
        <?php endforeach; ?>
      </div>

      <div class="overflow-x-auto"><table class="w-full text-sm">
        <thead class="bg-gray-50 border-b border-gray-100"><tr>
          <th class="text-left px-5 py-3 text-xs font-semibold text-gray-500 uppercase">#</th>
          <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Description</th>
          <th class="text-center px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Qty</th>
          <th class="text-left px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Unit</th>
          <th class="text-right px-4 py-3 text-xs font-semibold text-gray-500 uppercase">Unit Price</th>
          <th class="text-right px-5 py-3 text-xs font-semibold text-gray-500 uppercase">Total</th>
        </tr></thead>
        <tbody class="divide-y divide-gray-50">
          <?php foreach($items as $i=>$it): ?>
          <tr>
            <td class="px-5 py-3.5 text-gray-400"><?=$i+1?></td>
            <td class="px-4 py-3.5 text-gray-800 font-medium"><?=htmlspecialchars($it['description'])?></td>
            <td class="px-4 py-3.5 text-center text-gray-600"><?=$it['quantity']+0?></td>
            <td class="px-4 py-3.5 text-gray-500"><?=htmlspecialchars($it['unit'])?></td>
            <td class="px-4 py-3.5 text-right text-gray-600"><?=fmt_money($it['unit_price'])?></td>
            <td class="px-5 py-3.5 text-right font-semibold text-gray-800"><?=fmt_money($it['total'])?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table></div>

      <div class="flex justify-end p-5 border-t border-gray-100">
        <div class="w-64 space-y-2 text-sm">
          <div class="flex justify-between"><span class="text-gray-500">Subtotal</span><span class="font-medium"><?=fmt_money($invoice['subtotal'])?></span></div>
          <?php if($invoice['discount_percent']>0):$d=round($invoice['subtotal']*$invoice['discount_percent']/100);?><div class="flex justify-between text-red-500"><span>Discount (<?=$invoice['discount_percent']?>%)</span><span>- <?=fmt_money($d)?></span></div><?php endif;?>
          <?php if($invoice['tax_percent']>0):$tb=$invoice['subtotal']*(1-$invoice['discount_percent']/100);$ta=round($tb*$invoice['tax_percent']/100);?><div class="flex justify-between"><span class="text-gray-500">Tax (<?=$invoice['tax_percent']?>%)</span><span><?=fmt_money($ta)?></span></div><?php endif;?>
          <div class="flex justify-between border-t border-gray-200 pt-2"><span class="font-bold text-gray-800">Total</span><span class="font-bold text-xl text-amber-600"><?=fmt_money($invoice['total'])?></span></div>
          <?php if($invoice['amount_paid']>0):?>
          <div class="flex justify-between text-green-600"><span>Paid</span><span class="font-medium">- <?=fmt_money($invoice['amount_paid'])?></span></div>
          <div class="flex justify-between border-t border-gray-200 pt-2"><span class="font-bold text-gray-800">Balance Due</span><span class="font-bold text-lg <?=$balance>0?'text-red-600':'text-green-600'?>"><?=fmt_money(max(0,$balance))?></span></div>
          <?php endif;?>
        </div>
      </div>

      <?php if($invoice['notes']||$invoice['terms']): ?>
        <div class="px-5 pb-5 space-y-3 border-t border-gray-100 pt-4">
          <?php if($invoice['notes']): ?><div><p class="text-xs font-semibold text-gray-400 uppercase mb-1">Notes</p><p class="text-sm text-gray-600"><?=nl2br(htmlspecialchars($invoice['notes']))?></p></div><?php endif;?>
          <?php if($invoice['terms']): ?><div><p class="text-xs font-semibold text-gray-400 uppercase mb-1">Terms</p><p class="text-sm text-gray-500"><?=nl2br(htmlspecialchars($invoice['terms']))?></p></div><?php endif;?>
        </div>
      <?php endif; ?>
    </div>

    <!-- Payments history -->
    <?php if(!empty($payments)): ?>
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
      <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
        <h3 class="font-semibold text-gray-800">Payment History</h3>
        <span class="text-sm text-green-600 font-medium">Paid: <?=fmt_money($invoice['amount_paid'])?></span>
      </div>
      <div class="divide-y divide-gray-50">
        <?php foreach($payments as $pay): ?>
        <div class="flex items-center px-5 py-3.5 gap-4">
          <div class="flex-1"><p class="text-sm font-medium text-gray-800"><?=fmt_money($pay['amount'])?></p><p class="text-xs text-gray-400 capitalize"><?=str_replace('_',' ',$pay['method'])?><?=$pay['reference']?' · '.$pay['reference']:''?></p></div>
          <p class="text-xs text-gray-400"><?=date('d M Y',strtotime($pay['payment_date']))?></p>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Right sidebar -->
  <div class="space-y-5">
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
      <h3 class="font-semibold text-gray-800 mb-4">Status</h3>
      <form method="POST" class="space-y-3">
        <input type="hidden" name="action" value="change_status">
        <div class="grid grid-cols-2 gap-2">
          <?php foreach(['draft','sent','partial','paid','overdue','cancelled'] as $s): ?>
            <label class="cursor-pointer"><input type="radio" name="new_status" value="<?=$s?>" class="sr-only peer" <?=$invoice['status']===$s?'checked':''?>>
              <div class="text-center py-2 px-1 rounded-lg border-2 text-xs font-medium transition-colors <?=$invoice['status']===$s?'border-amber-500 bg-amber-50 text-amber-700':'border-gray-100 text-gray-500 hover:border-amber-300'?> peer-checked:border-amber-500 peer-checked:bg-amber-50 peer-checked:text-amber-700"><?=ucfirst($s)?></div>
            </label>
          <?php endforeach; ?>
        </div>
        <button type="submit" class="w-full bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold py-2 rounded-lg">Update</button>
      </form>
    </div>

    <!-- Balance card -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
      <h3 class="font-semibold text-gray-800 mb-3">Balance</h3>
      <div class="text-3xl font-bold <?=$balance>0?'text-red-600':'text-green-600'?>"><?=fmt_money(max(0,$balance))?></div>
      <p class="text-xs text-gray-400 mt-1">of <?=fmt_money($invoice['total'])?> total</p>
      <?php if($balance>0):?>
        <div class="mt-3 bg-gray-100 rounded-full h-2"><div class="bg-green-500 h-2 rounded-full" style="width:<?=min(100,round($invoice['amount_paid']/$invoice['total']*100))?>%"></div></div>
        <p class="text-xs text-gray-400 mt-1"><?=round($invoice['amount_paid']/$invoice['total']*100)?>% paid</p>
        <a href="/crm/invoices/payment?id=<?=$id?>" class="mt-3 inline-block w-full text-center bg-green-500 hover:bg-green-400 text-white text-sm font-semibold py-2 rounded-lg transition-colors">+ Record Payment</a>
      <?php endif;?>
    </div>

    <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
      <h3 class="font-semibold text-gray-800 mb-3">Bill To</h3>
      <p class="text-sm font-semibold text-gray-800"><?=htmlspecialchars($invoice['client_name'])?></p>
      <?php if($invoice['client_company']): ?><p class="text-sm text-gray-500"><?=htmlspecialchars($invoice['client_company'])?></p><?php endif;?>
      <?php if($invoice['client_email']): ?><p class="text-sm text-amber-600 mt-1"><?=htmlspecialchars($invoice['client_email'])?></p><?php endif;?>
      <?php if($invoice['client_phone']): ?><p class="text-sm text-gray-500"><?=htmlspecialchars($invoice['client_phone'])?></p><?php endif;?>
      <a href="/crm/clients/view?id=<?=$invoice['client_id']?>" class="mt-3 inline-block text-xs text-amber-600 font-medium">View client →</a>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
