<?php
$id=(int)($_GET['id']??0); if(!$id){header('Location: /crm/invoices/');exit;}
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/crm_helpers.php';
require_login();

$invoice=null;$items=[];$payments=[];
if($pdo){
    $s=$pdo->prepare("SELECT i.*,c.name client_name,c.email client_email,c.phone client_phone,c.company client_company,c.address client_address,c.city client_city,a.full_name prepared_by,a.signature_path FROM invoices i JOIN clients c ON i.client_id=c.id LEFT JOIN admin_users a ON i.created_by=a.id WHERE i.id=?");
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
    .header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0;padding-bottom:20px}
    .logo-block{display:flex;align-items:center;gap:16px}
    .logo-block img{height:72px;width:auto;display:block}
    .company-name{font-size:17px;font-weight:700;color:#0f172a;line-height:1.2}
    .company-sub{font-size:10.5px;color:#64748b;margin-top:3px}
    .contact-right{text-align:right;font-size:11.5px;color:#334155;line-height:1.8}
    .contact-right a{color:#334155;text-decoration:none}
    .contact-right .wa{color:#16a34a;font-weight:600}
    .divider{border:none;border-top:2px solid #f59e0b;margin:18px 0 0}
    .title-bar{background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 100%);color:#fff;padding:14px 24px;display:flex;justify-content:space-between;align-items:center;margin-bottom:24px}
    .title-bar h1{font-size:24px;font-weight:800;letter-spacing:0.06em;color:#fff}
    .doc-no{font-family:monospace;font-size:13px;color:#f59e0b;font-weight:700;margin-top:3px}
    .status-badge{display:inline-block;padding:3px 10px;border-radius:99px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;background:#fef3c7;color:#92400e;margin-top:5px}
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
    .sig-section{display:flex;justify-content:space-between;gap:48px;margin-top:32px;margin-bottom:24px;padding-top:20px;border-top:1px solid #e2e8f0}
    .sig-box{flex:1}
    .sig-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:10px}
    .sig-area{height:72px;border:1.5px dashed #cbd5e1;border-radius:6px;background:#f8fafc;display:flex;align-items:center;justify-content:center;margin-bottom:8px}
    .sig-area img{max-height:60px;max-width:200px;object-fit:contain}
    .sig-placeholder{font-size:10px;color:#cbd5e1;letter-spacing:.04em}
    .sig-name{font-size:12px;color:#334155;font-weight:600}
    .sig-sub{font-size:10.5px;color:#94a3b8;margin-top:2px}
    .footer-strip{border-top:1px solid #e2e8f0;padding-top:16px;display:flex;justify-content:space-between;font-size:11px;color:#94a3b8}
    .print-btn{position:fixed;top:16px;right:16px;background:#f59e0b;color:#fff;border:none;padding:10px 20px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.15)}
    @media print{.print-btn{display:none}body{padding:20px}}
  </style>
</head>
<body>
<button class="print-btn" onclick="window.print()">🖨 Print / Save PDF</button>
<?php $wa_display='+256 702 371 230'; $phone_2='+256 782 187 799'; ?>
<div class="page">

  <!-- Header: Logo + Contact -->
  <div class="header">
    <div class="logo-block">
      <img src="/assets/images/logo_main.png" alt="Ambozy Graphics Solutions Ltd">
      <div>
        <div class="company-name"><?=SITE_NAME?></div>
        <div class="company-sub">Print · Brand · Deliver</div>
        <div class="company-sub" style="margin-top:4px;font-size:10px;color:#94a3b8">Plot 1314 Church Road, Buye, Ntinda, Kampala</div>
      </div>
    </div>
    <div class="contact-right">
      <div>✉ <a href="mailto:<?=SITE_EMAIL?>"><?=SITE_EMAIL?></a></div>
      <div style="color:#EA4335;font-weight:600">
        <svg style="width:12px;height:12px;vertical-align:middle;margin-right:3px" viewBox="0 0 24 24" fill="currentColor"><path d="M24 5.457v13.909c0 .904-.732 1.636-1.636 1.636h-3.819V11.73L12 16.64l-6.545-4.91v9.273H1.636A1.636 1.636 0 0 1 0 19.366V5.457c0-2.023 2.309-3.178 3.927-1.964L5.455 4.64 12 9.548l6.545-4.91 1.528-1.145C21.69 2.28 24 3.434 24 5.457z"/></svg>Ambozygraphics@gmail.com
      </div>
      <div class="wa">
        <svg style="width:12px;height:12px;vertical-align:middle;margin-right:3px" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg><?=$wa_display?>
      </div>
      <div>☎ <?=$phone_2?></div>
      <div>🌐 <a href="<?=SITE_URL?>"><?=str_replace(['https://','http://'],'',SITE_URL)?></a></div>
    </div>
  </div>

  <!-- Divider + Invoice title bar -->
  <hr class="divider">
  <div class="title-bar">
    <div>
      <h1>INVOICE</h1>
      <div class="doc-no"><?=htmlspecialchars($invoice['invoice_number'])?></div>
      <div class="status-badge"><?=strtoupper($invoice['status'])?></div>
    </div>
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
    📱 Mobile Money (MTN/Airtel): 0782 187 799 &nbsp;·&nbsp; 🏦 Bank Transfer: Contact us for details &nbsp;·&nbsp; 💵 Cash: Plot 1314 Church Road, Buye, Ntinda, Kampala<br>
    Please quote invoice number <strong><?=htmlspecialchars($invoice['invoice_number'])?></strong> as your payment reference.
  </div>

  <?php if($invoice['notes']||$invoice['terms']): ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
      <?php if($invoice['notes']): ?><div class="notes-section"><h3>Notes</h3><?=nl2br(htmlspecialchars($invoice['notes']))?></div><?php endif;?>
      <?php if($invoice['terms']): ?><div class="notes-section"><h3>Terms</h3><?=nl2br(htmlspecialchars($invoice['terms']))?></div><?php endif;?>
    </div>
  <?php endif;?>

  <!-- ── Signature block ── -->
  <div class="sig-section">
    <div class="sig-box">
      <div class="sig-label">Received by</div>
      <div class="sig-area">
        <span class="sig-placeholder">Sign here</span>
      </div>
      <div class="sig-name"><?=htmlspecialchars($invoice['client_name'])?></div>
      <div class="sig-sub">Signature &amp; Date</div>
    </div>
    <div class="sig-box" style="text-align:right">
      <div class="sig-label">Issued by</div>
      <div class="sig-area" style="justify-content:flex-end;padding-right:12px">
        <?php
          $sig = $invoice['signature_path'] ?? null;
          $sig_file = $sig ? __DIR__ . '/../../' . $sig : null;
          if ($sig_file && file_exists($sig_file)):
        ?>
          <img src="/<?=htmlspecialchars($sig)?>" alt="Signature">
        <?php else: ?>
          <span class="sig-placeholder">Signature pending</span>
        <?php endif; ?>
      </div>
      <div class="sig-name"><?=htmlspecialchars($invoice['prepared_by'] ?: 'Ambozy Team')?></div>
      <div class="sig-sub"><?=SITE_NAME?></div>
    </div>
  </div>

  <div class="footer-strip">
    <span><?=SITE_NAME?> | Plot 1314 Church Road, Buye, Ntinda, Kampala</span>
    <span><?=$wa_display?> | <?=SITE_EMAIL?></span>
    <span>Printed <?=date('d M Y')?></span>
  </div>
</div>
</body></html>
