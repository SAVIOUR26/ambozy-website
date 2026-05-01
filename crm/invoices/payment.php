<?php
$id=(int)($_GET['id']??0); if(!$id){header('Location: /crm/invoices/');exit;}
$page_title='Record Payment'; $active_nav='invoices';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../../includes/crm_email.php';

$invoice=null;
if($pdo){$s=$pdo->prepare("SELECT i.*,c.name client_name,c.email client_email FROM invoices i JOIN clients c ON i.client_id=c.id WHERE i.id=?");$s->execute([$id]);$invoice=$s->fetch();}
if(!$invoice){flash('error','Invoice not found.');redirect('/crm/invoices/');}
if(in_array($invoice['status'],['paid','cancelled'])){flash('error','This invoice is already '.ucfirst($invoice['status']).'.');redirect("/crm/invoices/view?id=$id");}

$balance=max(0,$invoice['total']-$invoice['amount_paid']);
$errors=[];
$f=['amount'=>$balance,'method'=>'cash','reference'=>'','payment_date'=>date('Y-m-d'),'notes'=>'','send_receipt'=>1];

if($_SERVER['REQUEST_METHOD']==='POST'&&$pdo){
    $f['amount']       = max(0,(float)($_POST['amount']??0));
    $f['method']       = clean($_POST['method']??'cash');
    $f['reference']    = clean($_POST['reference']??'');
    $f['payment_date'] = clean($_POST['payment_date']??date('Y-m-d'));
    $f['notes']        = clean($_POST['notes']??'');
    $f['send_receipt'] = isset($_POST['send_receipt'])?1:0;

    if($f['amount']<=0) $errors['amount']='Amount must be greater than 0.';
    if($f['amount']>$balance+0.01) $errors['amount']='Amount exceeds balance due of '.fmt_money($balance).'.';

    if(empty($errors)){
        $pdo->prepare("INSERT INTO payments (invoice_id,client_id,amount,method,reference,notes,payment_date,recorded_by) VALUES (?,?,?,?,?,?,?,?)")
            ->execute([$id,$invoice['client_id'],$f['amount'],$f['method'],$f['reference']?:null,$f['notes']?:null,$f['payment_date'],$_SESSION['admin_id']??null]);

        $new_paid=$invoice['amount_paid']+$f['amount'];
        $new_balance=max(0,$invoice['total']-$new_paid);
        $new_status=$new_balance<=0.005?'paid':($new_paid>0?'partial':'sent');
        $paid_at_sql=$new_status==='paid'?',paid_at=NOW()':'';
        $pdo->prepare("UPDATE invoices SET amount_paid=?,status=?$paid_at_sql WHERE id=?")->execute([$new_paid,$new_status,$id]);

        log_activity($pdo,'payment_recorded',"Payment of ".fmt_money($f['amount'])." recorded on invoice {$invoice['invoice_number']}.",'invoice',$id);

        // Send receipt email
        if($f['send_receipt']&&$invoice['client_email']){
            $pay_data=['amount'=>number_format($f['amount'],0),'method'=>str_replace('_',' ',$f['method']),'reference'=>$f['reference'],'payment_date'=>$f['payment_date']];
            $inv_data=$invoice; $inv_data['amount_paid']=$new_paid;
            $html=email_payment_receipt($pay_data,$inv_data,['name'=>$invoice['client_name']]);
            crm_send_email($pdo,'receipt',$invoice['client_email'],$invoice['client_name'],"Payment Receipt — {$invoice['invoice_number']}",$html,'invoice',$id);
        }

        flash('success',fmt_money($f['amount']).' recorded. Invoice is now '.ucfirst($new_status).'.');
        redirect("/crm/invoices/view?id=$id");
    }
}
?>
<div class="max-w-lg">
  <div class="mb-5"><a href="/crm/invoices/view?id=<?=$id?>" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back to Invoice</a></div>

  <!-- Balance summary -->
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 mb-5">
    <div class="flex items-center justify-between mb-3">
      <h3 class="font-semibold text-gray-800"><?=htmlspecialchars($invoice['invoice_number'])?></h3>
      <span class="text-xs px-2 py-0.5 rounded-full font-medium <?=invoice_badge($invoice['status'])?>"><?=ucfirst($invoice['status'])?></span>
    </div>
    <div class="grid grid-cols-3 gap-4 text-center">
      <div><p class="text-xs text-gray-400 font-medium">Total</p><p class="text-base font-bold text-gray-800 mt-0.5"><?=fmt_money($invoice['total'])?></p></div>
      <div><p class="text-xs text-gray-400 font-medium">Paid</p><p class="text-base font-bold text-green-600 mt-0.5"><?=fmt_money($invoice['amount_paid'])?></p></div>
      <div><p class="text-xs text-gray-400 font-medium">Balance Due</p><p class="text-base font-bold text-red-600 mt-0.5"><?=fmt_money($balance)?></p></div>
    </div>
    <div class="mt-3 bg-gray-100 rounded-full h-2">
      <div class="bg-green-500 h-2 rounded-full transition-all" style="width:<?=min(100,round($invoice['amount_paid']/$invoice['total']*100))?>%"></div>
    </div>
  </div>

  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100"><h2 class="font-semibold text-gray-800">Record Payment</h2></div>
    <form method="POST" class="px-6 py-5 space-y-5">

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Amount (UGX) <span class="text-red-500">*</span></label>
        <div class="relative">
          <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm font-medium">UGX</span>
          <input type="number" name="amount" value="<?=$f['amount']?>" min="1" step="100" required
                 class="w-full border <?=isset($errors['amount'])?'border-red-400':'border-gray-200'?> rounded-lg pl-12 pr-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
        <?php if(isset($errors['amount'])): ?><p class="text-red-500 text-xs mt-1"><?=$errors['amount']?></p><?php endif;?>
        <p class="text-xs text-gray-400 mt-1">Balance due: <?=fmt_money($balance)?></p>
      </div>

      <div class="grid grid-cols-2 gap-5">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Payment Method</label>
          <select name="method" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
            <?php foreach(['cash'=>'💵 Cash','mobile_money'=>'📱 Mobile Money','bank_transfer'=>'🏦 Bank Transfer','cheque'=>'📋 Cheque','card'=>'💳 Card'] as $v=>$l): ?>
              <option value="<?=$v?>" <?=$f['method']===$v?'selected':''?>><?=$l?></option>
            <?php endforeach;?>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1.5">Payment Date</label>
          <input type="date" name="payment_date" value="<?=$f['payment_date']?>" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
        </div>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Reference / Transaction ID</label>
        <input type="text" name="reference" value="<?=htmlspecialchars($f['reference'])?>" placeholder="e.g. MTN transaction ref, cheque no."
               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1.5">Notes</label>
        <textarea name="notes" rows="2" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 resize-none"><?=htmlspecialchars($f['notes'])?></textarea>
      </div>

      <?php if($invoice['client_email']): ?>
      <label class="flex items-center gap-3 cursor-pointer">
        <input type="checkbox" name="send_receipt" value="1" <?=$f['send_receipt']?'checked':''?> class="w-4 h-4 rounded border-gray-300 text-amber-500 focus:ring-amber-400">
        <span class="text-sm text-gray-700">Send receipt email to <strong><?=htmlspecialchars($invoice['client_email'])?></strong></span>
      </label>
      <?php endif;?>

      <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-green-500 hover:bg-green-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg transition-colors">
          ✓ Record Payment
        </button>
        <a href="/crm/invoices/view?id=<?=$id?>" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
