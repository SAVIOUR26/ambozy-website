<?php
$id=(int)($_GET['id']??0); if(!$id){header('Location: /crm/invoices/');exit;}
$page_title='Send Invoice'; $active_nav='invoices';
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../../includes/crm_email.php';

$invoice=null;$items=[];
if($pdo){
    $s=$pdo->prepare("SELECT i.*,c.name client_name,c.email client_email FROM invoices i JOIN clients c ON i.client_id=c.id WHERE i.id=?");
    $s->execute([$id]);$invoice=$s->fetch();
    if($invoice){$si=$pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id=? ORDER BY sort_order");$si->execute([$id]);$items=$si->fetchAll();}
}
if(!$invoice){flash('error','Invoice not found.');redirect('/crm/invoices/');}

if($_SERVER['REQUEST_METHOD']==='POST'&&$pdo){
    $to_email=clean($_POST['to_email']??''); $to_name=clean($_POST['to_name']??'');
    if(!filter_var($to_email,FILTER_VALIDATE_EMAIL)){flash('error','Invalid email.');redirect("/crm/invoices/send?id=$id");}

    $inv_data=$invoice;
    $inv_data['total']=number_format($invoice['total'],0);
    $html=email_invoice($inv_data,['name'=>$to_name?:$invoice['client_name']],$items);
    $subj="Invoice {$invoice['invoice_number']} from Ambozy Graphics Solutions — UGX ".number_format($invoice['total'],0)." due";
    $ok=crm_send_email($pdo,'invoice',$to_email,$to_name,$subj,$html,'invoice',$id);

    if($ok){
        $pdo->prepare("UPDATE invoices SET status='sent',sent_at=NOW() WHERE id=? AND status='draft'")->execute([$id]);
        log_activity($pdo,'invoice_sent',"Invoice {$invoice['invoice_number']} sent to {$to_email}.",'invoice',$id);
        flash('success',"Invoice emailed to {$to_email}.");
        redirect("/crm/invoices/view?id=$id");
    } else {
        flash('error','Email could not be sent. Check server mail configuration.');
        redirect("/crm/invoices/send?id=$id");
    }
}
?>
<div class="max-w-lg">
  <div class="mb-5"><a href="/crm/invoices/view?id=<?=$id?>" class="text-sm text-gray-400 hover:text-gray-600 flex items-center gap-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>Back to Invoice</a></div>
  <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-5 flex items-center justify-between">
    <div><p class="font-semibold text-amber-900"><?=htmlspecialchars($invoice['invoice_number'])?></p><p class="text-sm text-amber-700">Due: <?=$invoice['due_date']?date('d M Y',strtotime($invoice['due_date'])):'On receipt'?></p></div>
    <p class="font-bold text-amber-800 text-lg"><?=fmt_money($invoice['total'])?></p>
  </div>
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100"><h2 class="font-semibold text-gray-800">Send Invoice to Client</h2><p class="text-sm text-gray-400 mt-0.5">An HTML invoice with payment instructions will be sent.</p></div>
    <form method="POST" class="px-6 py-5 space-y-5">
      <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Recipient Name</label><input type="text" name="to_name" value="<?=htmlspecialchars($invoice['client_name'])?>" class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400"></div>
      <div><label class="block text-sm font-medium text-gray-700 mb-1.5">Recipient Email <span class="text-red-500">*</span></label><input type="email" name="to_email" value="<?=htmlspecialchars($invoice['client_email']??'')?>" required class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400"></div>
      <div class="flex items-center gap-3 pt-2">
        <button type="submit" class="bg-blue-500 hover:bg-blue-400 text-white font-semibold text-sm px-6 py-2.5 rounded-lg">✉ Send Invoice Now</button>
        <a href="/crm/invoices/view?id=<?=$id?>" class="text-sm text-gray-400 hover:text-gray-600">Cancel</a>
      </div>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
