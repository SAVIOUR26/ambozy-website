<?php
/**
 * Print-ready quote PDF view — open in new tab → Ctrl+P → Save as PDF
 */
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /crm/quotes/'); exit; }

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/crm_helpers.php';
require_login();

$quote = null; $items = [];
if ($pdo) {
    $s = $pdo->prepare("SELECT q.*, c.name client_name, c.email client_email, c.phone client_phone, c.company client_company, c.address client_address, c.city client_city, a.full_name prepared_by, a.signature_path FROM quotations q JOIN clients c ON q.client_id=c.id LEFT JOIN admin_users a ON q.created_by=a.id WHERE q.id=?");
    $s->execute([$id]); $quote = $s->fetch();
    if ($quote) { $si = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id=? ORDER BY sort_order"); $si->execute([$id]); $items=$si->fetchAll(); }
}
if (!$quote) { echo 'Quote not found.'; exit; }

$disc_amt = round($quote['subtotal'] * $quote['discount_percent'] / 100, 0);
$tax_base  = $quote['subtotal'] - $disc_amt;
$tax_amt   = round($tax_base * $quote['tax_percent'] / 100, 0);

/* ── Amount in words ──────────────────────────────────────── */
function number_to_words(float $amount): string {
    $ones  = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
               'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
               'Seventeen','Eighteen','Nineteen'];
    $tens  = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];

    function _chunk(int $n, array $ones, array $tens): string {
        if ($n === 0) return '';
        $out = '';
        if ($n >= 100) { $out .= $ones[(int)($n/100)] . ' Hundred '; $n %= 100; }
        if ($n >= 20)  { $out .= $tens[(int)($n/10)] . ' '; $n %= 10; }
        if ($n > 0)    { $out .= $ones[$n] . ' '; }
        return $out;
    }

    $n = (int) round($amount);
    if ($n === 0) return 'Zero Shillings Only';

    $billions  = (int)($n / 1_000_000_000); $n %= 1_000_000_000;
    $millions  = (int)($n / 1_000_000);     $n %= 1_000_000;
    $thousands = (int)($n / 1_000);         $n %= 1_000;
    $remainder = $n;

    $words = '';
    if ($billions)  $words .= _chunk($billions,  $ones, $tens) . 'Billion ';
    if ($millions)  $words .= _chunk($millions,  $ones, $tens) . 'Million ';
    if ($thousands) $words .= _chunk($thousands, $ones, $tens) . 'Thousand ';
    if ($remainder) $words .= _chunk($remainder, $ones, $tens);

    return trim($words) . ' Shillings Only';
}

$wa_display = '+256 702 371 230';
$phone_2    = '+256 782 187 799';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Quote <?= htmlspecialchars($quote['quote_number']) ?></title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',Arial,sans-serif;font-size:13px;color:#1e293b;background:#fff;padding:40px}
    .page{max-width:800px;margin:0 auto}

    /* ── Header ── */
    .header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:0;padding-bottom:20px}
    .logo-block{display:flex;align-items:center;gap:16px}
    .logo-block img{height:72px;width:auto;display:block}
    .company-name{font-size:17px;font-weight:700;color:#0f172a;line-height:1.2}
    .company-sub{font-size:10.5px;color:#64748b;margin-top:3px}
    .contact-right{text-align:right;font-size:11.5px;color:#334155;line-height:1.8}
    .contact-right a{color:#334155;text-decoration:none}
    .contact-right .wa{color:#16a34a;font-weight:600}

    /* ── Divider + QUOTATION title bar ── */
    .divider{border:none;border-top:2px solid #f59e0b;margin:18px 0 0}
    .title-bar{
      background:linear-gradient(135deg,#0f172a 0%,#1e3a5f 100%);
      color:#fff;padding:14px 24px;
      display:flex;justify-content:space-between;align-items:center;
      margin-bottom:24px;
    }
    .title-bar h1{font-size:24px;font-weight:800;letter-spacing:0.06em;color:#fff}
    .title-bar .doc-no{font-family:monospace;font-size:13px;color:#f59e0b;font-weight:700;margin-top:3px}
    .title-bar .status-badge{display:inline-block;padding:3px 10px;border-radius:99px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;background:#fef3c7;color:#92400e;margin-top:5px}
    .title-bar-contacts{display:flex;gap:22px;font-size:11px;align-items:center;color:rgba(255,255,255,0.85)}
    .title-bar-contacts span{display:flex;align-items:center;gap:5px}
    .icon-email::before{content:'✉'}
    .icon-phone::before{content:'☎'}
    .icon-wa{color:#4ade80;font-weight:700}

    /* ── Meta grid ── */
    .meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
    .meta-box{background:#f8fafc;border-radius:8px;padding:16px}
    .meta-box h3{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:8px}
    .meta-box p{font-size:13px;color:#1e293b;line-height:1.6}
    .meta-box .label{font-size:11px;color:#94a3b8;margin-top:4px}

    /* ── Items table ── */
    table{width:100%;border-collapse:collapse;margin-bottom:20px}
    thead{background:#0f172a}
    thead th{color:#fff;text-align:left;padding:10px 12px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em}
    thead th:last-child,thead th:nth-last-child(2),thead th:nth-last-child(3){text-align:right}
    tbody tr{border-bottom:1px solid #f1f5f9}
    tbody tr:nth-child(even){background:#f8fafc}
    td{padding:10px 12px;font-size:13px;color:#334155}
    td:last-child,td:nth-last-child(2),td:nth-last-child(3){text-align:right}
    td.num{text-align:center}

    /* ── Totals ── */
    .totals{display:flex;justify-content:flex-end;margin-bottom:8px}
    .totals-inner{width:280px}
    .totals-row{display:flex;justify-content:space-between;padding:6px 0;font-size:13px;border-bottom:1px solid #f1f5f9;color:#475569}
    .totals-row.grand{border-top:2px solid #0f172a;border-bottom:2px solid #0f172a;padding:10px 0;font-size:16px;font-weight:800;color:#0f172a}
    .totals-row.grand .amount{color:#f59e0b}
    .amount-words{
      display:flex;justify-content:flex-end;margin-bottom:24px;
    }
    .amount-words-inner{
      width:100%;background:#fffbeb;border:1px solid #fde68a;
      border-radius:6px;padding:10px 16px;
      font-size:12px;color:#78350f;
    }
    .amount-words-inner strong{display:block;font-size:10px;text-transform:uppercase;letter-spacing:.08em;color:#92400e;margin-bottom:3px}

    /* ── Notes ── */
    .notes-section{background:#f8fafc;border-radius:8px;padding:16px;margin-bottom:20px;font-size:12px;color:#475569;line-height:1.7}
    .notes-section h3{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:6px}

    /* ── Signature block ── */
    .sig-section{display:flex;justify-content:space-between;gap:32px;margin-top:28px;margin-bottom:24px}
    .sig-box{flex:1;max-width:260px}
    .sig-box .sig-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:8px}
    .sig-box .sig-image-wrap{height:64px;display:flex;align-items:flex-end;padding-bottom:6px;border-bottom:1px solid #cbd5e1}
    .sig-box .sig-image-wrap img{max-height:56px;max-width:180px;object-fit:contain}
    .sig-box .sig-name{font-size:11px;color:#334155;font-weight:600;margin-top:5px}
    .sig-box .sig-org{font-size:10.5px;color:#94a3b8}
    .sig-box .sig-empty{height:64px;border-bottom:1px solid #cbd5e1}
    /* ── Footer ── */
    .footer-strip{border-top:1px solid #e2e8f0;padding-top:14px;display:flex;justify-content:space-between;font-size:10.5px;color:#94a3b8}

    /* ── Print button ── */
    .print-btn{position:fixed;top:16px;right:16px;background:#f59e0b;color:#fff;border:none;padding:10px 20px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.15)}
    @media print{.print-btn{display:none}body{padding:20px}}
  </style>
</head>
<body>
<button class="print-btn" onclick="window.print()">🖨 Print / Save PDF</button>

<div class="page">

  <!-- ── Header: Logo + Contact block ── -->
  <div class="header">
    <div class="logo-block">
      <img src="/assets/images/logo_main.png" alt="Ambozy Graphics Solutions Ltd">
      <div>
        <div class="company-name"><?= SITE_NAME ?></div>
        <div class="company-sub">Print · Brand · Deliver · Est. 2010</div>
        <div class="company-sub" style="margin-top:4px;font-size:10px;color:#94a3b8">Plot 1314 Church Road, Buye, Ntinda, Kampala</div>
      </div>
    </div>
    <div class="contact-right">
      <div>✉ <a href="mailto:<?= SITE_EMAIL ?>"><?= SITE_EMAIL ?></a></div>
      <div class="wa">
        <svg style="width:12px;height:12px;vertical-align:middle;margin-right:3px" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg><?= $wa_display ?>
      </div>
      <div>☎ <?= $phone_2 ?></div>
      <div>🌐 <a href="<?= SITE_URL ?>"><?= str_replace(['https://','http://'], '', SITE_URL) ?></a></div>
    </div>
  </div>

  <!-- ── Divider + Quotation title bar ── -->
  <hr class="divider">
  <div class="title-bar">
    <div>
      <h1>QUOTATION</h1>
      <div class="doc-no"><?= htmlspecialchars($quote['quote_number']) ?></div>
      <div class="status-badge"><?= strtoupper($quote['status']) ?></div>
    </div>
    <div class="title-bar-contacts">
      <span><span class="icon-email"></span> <?= SITE_EMAIL ?></span>
      <span><span class="icon-phone"></span> <?= $phone_2 ?></span>
      <span><span class="icon-wa">WA</span> <?= $wa_display ?></span>
    </div>
  </div>

  <!-- ── Meta ── -->
  <div class="meta-grid">
    <div class="meta-box">
      <h3>Bill To</h3>
      <p style="font-weight:600"><?= htmlspecialchars($quote['client_name']) ?></p>
      <?php if ($quote['client_company']): ?><p><?= htmlspecialchars($quote['client_company']) ?></p><?php endif; ?>
      <?php if ($quote['client_email']): ?><p><?= htmlspecialchars($quote['client_email']) ?></p><?php endif; ?>
      <?php if ($quote['client_phone']): ?><p><?= htmlspecialchars($quote['client_phone']) ?></p><?php endif; ?>
      <?php if ($quote['client_address']): ?><p><?= htmlspecialchars(trim($quote['client_address'].', '.($quote['client_city']??''), ', ')) ?></p><?php endif; ?>
    </div>
    <div class="meta-box">
      <h3>Quote Details</h3>
      <p><strong>Subject:</strong> <?= htmlspecialchars($quote['title']) ?></p>
      <p class="label" style="margin-top:8px">Issue Date</p>
      <p><?= date('d F Y', strtotime($quote['created_at'])) ?></p>
      <?php if ($quote['valid_until']): ?>
        <p class="label" style="margin-top:6px">Valid Until</p>
        <p><?= date('d F Y', strtotime($quote['valid_until'])) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── Items table ── -->
  <table>
    <thead>
      <tr>
        <th style="width:30px">#</th>
        <th>Description</th>
        <th style="width:60px;text-align:center">Qty</th>
        <th style="width:60px">Unit</th>
        <th style="width:120px">Unit Price</th>
        <th style="width:130px">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($items as $i => $it): ?>
      <tr>
        <td><?= $i+1 ?></td>
        <td><?= htmlspecialchars($it['description']) ?></td>
        <td class="num"><?= $it['quantity']+0 ?></td>
        <td><?= htmlspecialchars($it['unit']) ?></td>
        <td>UGX <?= number_format($it['unit_price'],0) ?></td>
        <td><strong>UGX <?= number_format($it['total'],0) ?></strong></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <!-- ── Totals ── -->
  <div class="totals">
    <div class="totals-inner">
      <div class="totals-row"><span>Subtotal</span><span>UGX <?= number_format($quote['subtotal'],0) ?></span></div>
      <?php if ($quote['discount_percent'] > 0): ?>
        <div class="totals-row" style="color:#dc2626"><span>Discount (<?= $quote['discount_percent'] ?>%)</span><span>- UGX <?= number_format($disc_amt,0) ?></span></div>
      <?php endif; ?>
      <?php if ($quote['tax_percent'] > 0): ?>
        <div class="totals-row"><span>Tax / VAT (<?= $quote['tax_percent'] ?>%)</span><span>UGX <?= number_format($tax_amt,0) ?></span></div>
      <?php endif; ?>
      <div class="totals-row grand"><span>TOTAL</span><span class="amount">UGX <?= number_format($quote['total'],0) ?></span></div>
    </div>
  </div>

  <!-- ── Amount in Words ── -->
  <div class="amount-words">
    <div class="amount-words-inner">
      <strong>Amount in Words (UGX)</strong>
      <?= number_to_words((float)$quote['total']) ?>
    </div>
  </div>

  <?php if ($quote['notes'] || $quote['terms']): ?>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:24px">
      <?php if ($quote['notes']): ?>
        <div class="notes-section"><h3>Notes</h3><?= nl2br(htmlspecialchars($quote['notes'])) ?></div>
      <?php endif; ?>
      <?php if ($quote['terms']): ?>
        <div class="notes-section"><h3>Terms &amp; Conditions</h3><?= nl2br(htmlspecialchars($quote['terms'])) ?></div>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <!-- ── Signature block ── -->
  <div class="sig-section">
    <!-- Client acceptance -->
    <div class="sig-box">
      <div class="sig-label">Client Acceptance</div>
      <div class="sig-empty"></div>
      <div class="sig-name" style="color:#94a3b8"><?= htmlspecialchars($quote['client_name']) ?></div>
      <div class="sig-org" style="font-size:10px;margin-top:2px">Signature &amp; Date</div>
    </div>

    <!-- Prepared by -->
    <div class="sig-box" style="text-align:right">
      <div class="sig-label">Prepared &amp; Authorized by</div>
      <div class="sig-image-wrap" style="justify-content:flex-end">
        <?php
          $sig = $quote['signature_path'] ?? null;
          $sig_file = $sig ? __DIR__ . '/../../' . $sig : null;
          if ($sig_file && file_exists($sig_file)):
        ?>
          <img src="/<?= htmlspecialchars($sig) ?>" alt="Signature">
        <?php else: ?>
          <span style="font-size:10px;color:#e2e8f0;align-self:center">No signature uploaded</span>
        <?php endif; ?>
      </div>
      <div class="sig-name"><?= htmlspecialchars($quote['prepared_by'] ?: 'Ambozy Team') ?></div>
      <div class="sig-org"><?= SITE_NAME ?></div>
    </div>
  </div>

  <!-- ── Footer ── -->
  <div class="footer-strip">
    <span><?= SITE_NAME ?> | Plot 1314 Church Road, Buye, Ntinda, Kampala</span>
    <span><?= $wa_display ?> | <?= SITE_EMAIL ?></span>
    <span>Printed <?= date('d M Y') ?></span>
  </div>

</div>
</body>
</html>
