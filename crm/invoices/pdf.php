<?php
$id=(int)($_GET['id']??0); if(!$id){header('Location: /crm/invoices/');exit;}
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/crm_helpers.php';
require_login();

$invoice=null;$items=[];$payments=[];
if($pdo){
    $s=$pdo->prepare("SELECT i.*,c.name client_name,c.email client_email,c.phone client_phone,c.company client_company,c.address client_address,c.city client_city FROM invoices i JOIN clients c ON i.client_id=c.id WHERE i.id=?");
    $s->execute([$id]);$invoice=$s->fetch();
    if($invoice){$si=$pdo->prepare("SELECT * FROM invoice_items WHERE invoice_id=? ORDER BY sort_order");$si->execute([$id]);$items=$si->fetchAll();$sp=$pdo->prepare("SELECT * FROM payments WHERE invoice_id=? ORDER BY payment_date");$sp->execute([$id]);$payments=$sp->fetchAll();}
}
if(!$invoice){echo 'Invoice not found.';exit;}
$balance=max(0,$invoice['total']-$invoice['amount_paid']);
$disc_amt=round($invoice['subtotal']*$invoice['discount_percent']/100,0);
$tax_base=$invoice['subtotal']-$disc_amt;
$tax_amt=round($tax_base*$invoice['tax_percent']/100,0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Invoice <?=htmlspecialchars($invoice['invoice_number'])?></title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',Arial,sans-serif;font-size:13px;color:#1e293b;background:#fff;padding:40px}
    .page{max-width:800px;margin:0 auto}
    .header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:32px;padding-bottom:24px;border-bottom:2px solid #f59e0b}
    .logo-box{width:48px;height:48px;background:#0f172a;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#f59e0b;font-weight:700;font-size:16px}
    .company-name{font-size:18px;font-weight:700;color:#0f172a}
    .company-sub{font-size:11px;color:#64748b;margin-top:2px}
    .doc-title h1{font-size:28px;font-weight:800;color:#0f172a;letter-spacing:-0.5px;text-align:right}
    .doc-no{font-size:14px;color:#f59e0b;font-weight:700;font-family:monospace;text-align:right;margin-top:4px}
    .status-badge{display:inline-block;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;text-transform:uppercase;float:right;margin-top:6px;background:#fef3c7;color:#92400e}
    .meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
    .meta-box{background:#f8fafc;border-radius:8px;padding:16px}
    .meta-box h3{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:8px}
    .meta-box p{font-size:13px;color:#1e293b;line-height:1.6}
    .label{font-size:11px;color:#94a3b8;margin-top:4px}
    table{width:100%;border-collapse:collapse;margin-bottom:20px}
    thead{background:#0f172a}
    thead th{color:#fff;text-align:left;padding:10px 12px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em}
    thead th.r{text-align:right}
    tbody tr{border-bottom:1px solid #f1f5f9}
    tbody tr:nth-child(even){background:#f8fafc}
    td{padding:10px 12px;font-size:13px;color:#334155}
    td.r{text-align:right}
    td.c{text-align:center}
    .totals{display:flex;justify-content:flex-end;margin-bottom:20px}
    .totals-inner{width:260px}
    .tr{display:flex;justify-content:space-between;padding:6px 0;font-size:13px;border-bottom:1px solid #f1f5f9;color:#475569}
    .tr.grand{border-top:2px solid #0f172a;border-bottom:none;padding-top:10px;font-size:16px;font-weight:800;color:#0f172a}
    .tr.balance{font-size:15px;color:#dc2626;font-weight:700;border-bottom:none}
    .tr.paid-row{color:#16a34a}
    .payment-history{background:#f0fdf4;border:1px solid #bbf7d0;border-radius:8px;padding:16px;margin-bottom:20px}
    .payment-history h3{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#16a34a;margin-bottom:8px}
    .pay-row{display:flex;justify-content:space-between;font-size:12px;padding:4px 0;border-bottom:1px solid #dcfce7;color:#166534}
    .pay-row:last-child{border-bottom:none}
    .notes-section{background:#f8fafc;border-radius:8px;padding:16px;margin-bottom:20px;font-size:12px;color:#475569}
    .notes-section h3{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:6px}
    .footer-strip{border-top:1px solid #e2e8f0;padding-top:16px;display:flex;justify-content:space-between;font-size:11px;color:#94a3b8}
    .print-btn{position:fixed;top:16px;right:16px;background:#f59e0b;color:#fff;border:none;padding:10px 20px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.15)}
    @media print{.print-btn{display:none}body{padding:20px}}
  </style>
</head>
<body>
<button class="print-btn" onclick="window.print()">🖨 Print / Save PDF</button>
<div class="page">
  <div class="header">
    <div style="display:flex;align-items:center;gap:12px">
      <div class="logo-box">AG</div>
      <div>
        <div class="company-name"><?=SITE_NAME?></div>
        <div class="company-sub">Print · Brand · Deliver · Est. 2010</div>
        <div class="company-sub"><?=SITE_PHONE?> | <?=SITE_EMAIL?></div>
      </div>
    </div>
    <div class="doc-title"><h1>INVOICE</h1><div class="doc-no"><?=htmlspecialchars($invoice['invoice_number'])?></div><div class="status-badge"><?=strtoupper($invoice['status'])?></div></div>
  </div>

  <div class="meta-grid">
    <div class="meta-box">
      <h3>Bill To</h3>
      <p style="font-weight:600"><?=htmlspecialchars($invoice['client_name'])?></p>
      <?php if($invoice['client_company']): ?><p><?=htmlspecialchars($invoice['client_company'])?></p><?php endif;?>
      <?php if($invoice['client_email']): ?><p><?=htmlspecialchars($invoice['client_email'])?></p><?php endif;?>
      <?php if($invoice['client_phone']): ?><p><?=htmlspecialchars($invoice['client_phone'])?></p><?php endif;?>
    </div>
    <div class="meta-box">
      <h3>Invoice Details</h3>
      <p class="label">Issue Date</p><p><?=date('d F Y',strtotime($invoice['created_at']))?></p>
      <?php if($invoice['due_date']): ?><p class="label" style="margin-top:6px">Due Date</p><p style="font-weight:600;color:#dc2626"><?=date('d F Y',strtotime($invoice['due_date']))?></p><?php endif;?>
    </div>
  </div>

  <table>
    <thead><tr><th style="width:30px">#</th><th>Description</th><th class="r" style="width:60px">Qty</th><th style="width:60px">Unit</th><th class="r" style="width:120px">Unit Price</th><th class="r" style="width:130px">Total</th></tr></thead>
    <tbody>
      <?php foreach($items as $i=>$it): ?><tr><td><?=$i+1?></td><td><?=htmlspecialchars($it['description'])?></td><td class="c"><?=$it['quantity']+0?></td><td><?=htmlspecialchars($it['unit'])?></td><td class="r">UGX <?=number_format($it['unit_price'],0)?></td><td class="r"><strong>UGX <?=number_format($it['total'],0)?></strong></td></tr><?php endforeach;?>
    </tbody>
  </table>

  <div class="totals"><div class="totals-inner">
    <div class="tr"><span>Subtotal</span><span>UGX <?=number_format($invoice['subtotal'],0)?></span></div>
    <?php if($invoice['discount_percent']>0): ?><div class="tr" style="color:#dc2626"><span>Discount (<?=$invoice['discount_percent']?>%)</span><span>- UGX <?=number_format($disc_amt,0)?></span></div><?php endif;?>
    <?php if($invoice['tax_percent']>0): ?><div class="tr"><span>Tax (<?=$invoice['tax_percent']?>%)</span><span>UGX <?=number_format($tax_amt,0)?></span></div><?php endif;?>
    <div class="tr grand"><span>TOTAL</span><span style="color:#f59e0b">UGX <?=number_format($invoice['total'],0)?></span></div>
    <?php if($invoice['amount_paid']>0): ?>
      <div class="tr paid-row"><span>Paid</span><span>- UGX <?=number_format($invoice['amount_paid'],0)?></span></div>
      <div class="tr balance"><span>BALANCE DUE</span><span style="color:<?=$balance>0?'#dc2626':'#16a34a'?>">UGX <?=number_format($balance,0)?></span></div>
    <?php endif;?>
  </div></div>

  <?php if(!empty($payments)): ?>
    <div class="payment-history"><h3>Payment History</h3>
      <?php foreach($payments as $p): ?><div class="pay-row"><span><?=date('d M Y',strtotime($p['payment_date']))?> — <?=ucfirst(str_replace('_',' ',$p['method']))?><?=$p['reference']?' ('.$p['reference'].')':''?></span><span>UGX <?=number_format($p['amount'],0)?></span></div><?php endforeach;?>
    </div>
  <?php endif;?>

  <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:16px;margin-bottom:20px;font-size:12px;color:#1e40af">
    <strong>Payment Options:</strong><br>
    📱 Mobile Money (MTN/Airtel): 0782 187 799 &nbsp;·&nbsp; 🏦 Bank Transfer: Contact us for details &nbsp;·&nbsp; 💵 Cash: Plot 43 Nasser/Nkrumah Road, Kampala<br>
    Please quote invoice number <strong><?=htmlspecialchars($invoice['invoice_number'])?></strong> as your payment reference.
  </div>

  <?php if($invoice['notes']||$invoice['terms']): ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
      <?php if($invoice['notes']): ?><div class="notes-section"><h3>Notes</h3><?=nl2br(htmlspecialchars($invoice['notes']))?></div><?php endif;?>
      <?php if($invoice['terms']): ?><div class="notes-section"><h3>Terms</h3><?=nl2br(htmlspecialchars($invoice['terms']))?></div><?php endif;?>
    </div>
  <?php endif;?>

  <div class="footer-strip">
    <span><?=SITE_NAME?> | Plot 43 Nasser/Nkrumah Road, Kampala</span>
    <span><?=SITE_PHONE?> | <?=SITE_EMAIL?></span>
    <span>Printed <?=date('d M Y')?></span>
  </div>
</div>
</body></html>
