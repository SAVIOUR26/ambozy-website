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
    $s = $pdo->prepare("SELECT q.*, c.name client_name, c.email client_email, c.phone client_phone, c.company client_company, c.address client_address, c.city client_city FROM quotations q JOIN clients c ON q.client_id=c.id WHERE q.id=?");
    $s->execute([$id]); $quote = $s->fetch();
    if ($quote) { $si = $pdo->prepare("SELECT * FROM quotation_items WHERE quotation_id=? ORDER BY sort_order"); $si->execute([$id]); $items=$si->fetchAll(); }
}
if (!$quote) { echo 'Quote not found.'; exit; }

$disc_amt = round($quote['subtotal'] * $quote['discount_percent'] / 100, 0);
$tax_base  = $quote['subtotal'] - $disc_amt;
$tax_amt   = round($tax_base * $quote['tax_percent'] / 100, 0);
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
    .header{display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:32px;padding-bottom:24px;border-bottom:2px solid #f59e0b}
    .logo-block{display:flex;align-items:center;gap:12px}
    .logo-box{width:48px;height:48px;background:#0f172a;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#f59e0b;font-weight:700;font-size:16px}
    .company-name{font-size:18px;font-weight:700;color:#0f172a}
    .company-sub{font-size:11px;color:#64748b;margin-top:2px}
    .doc-title{text-align:right}
    .doc-title h1{font-size:28px;font-weight:800;color:#0f172a;letter-spacing:-0.5px}
    .doc-title .doc-no{font-size:14px;color:#f59e0b;font-weight:700;font-family:monospace;margin-top:4px}
    .status-badge{display:inline-block;padding:3px 10px;border-radius:99px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;margin-top:6px;background:#fef3c7;color:#92400e}
    .meta-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px}
    .meta-box{background:#f8fafc;border-radius:8px;padding:16px}
    .meta-box h3{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:8px}
    .meta-box p{font-size:13px;color:#1e293b;line-height:1.6}
    .meta-box .label{font-size:11px;color:#94a3b8;margin-top:4px}
    table{width:100%;border-collapse:collapse;margin-bottom:20px}
    thead{background:#0f172a}
    thead th{color:#fff;text-align:left;padding:10px 12px;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.05em}
    thead th:last-child,thead th:nth-last-child(2),thead th:nth-last-child(3){text-align:right}
    tbody tr{border-bottom:1px solid #f1f5f9}
    tbody tr:nth-child(even){background:#f8fafc}
    td{padding:10px 12px;font-size:13px;color:#334155}
    td:last-child,td:nth-last-child(2),td:nth-last-child(3){text-align:right}
    td.num{text-align:center}
    .totals{display:flex;justify-content:flex-end;margin-bottom:24px}
    .totals-inner{width:260px}
    .totals-row{display:flex;justify-content:space-between;padding:6px 0;font-size:13px;border-bottom:1px solid #f1f5f9;color:#475569}
    .totals-row.grand{border-top:2px solid #0f172a;border-bottom:none;padding-top:10px;font-size:16px;font-weight:800;color:#0f172a}
    .totals-row.grand .amount{color:#f59e0b}
    .notes-section{background:#f8fafc;border-radius:8px;padding:16px;margin-bottom:20px;font-size:12px;color:#475569;line-height:1.7}
    .notes-section h3{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:6px}
    .footer-strip{border-top:1px solid #e2e8f0;padding-top:16px;display:flex;justify-content:space-between;font-size:11px;color:#94a3b8}
    .print-btn{position:fixed;top:16px;right:16px;background:#f59e0b;color:#fff;border:none;padding:10px 20px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.15)}
    @media print{.print-btn{display:none}body{padding:20px}}
  </style>
</head>
<body>
<button class="print-btn" onclick="window.print()">🖨 Print / Save PDF</button>

<div class="page">
  <!-- Header -->
  <div class="header">
    <div class="logo-block">
      <div class="logo-box">AG</div>
      <div>
        <div class="company-name"><?= SITE_NAME ?></div>
        <div class="company-sub">Print · Brand · Deliver · Est. 2010</div>
        <div class="company-sub" style="margin-top:2px"><?= SITE_PHONE ?> | <?= SITE_EMAIL ?></div>
      </div>
    </div>
    <div class="doc-title">
      <h1>QUOTATION</h1>
      <div class="doc-no"><?= htmlspecialchars($quote['quote_number']) ?></div>
      <div class="status-badge"><?= strtoupper($quote['status']) ?></div>
    </div>
  </div>

  <!-- Meta -->
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

  <!-- Items table -->
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

  <!-- Totals -->
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

  <!-- Footer -->
  <div class="footer-strip">
    <span><?= SITE_NAME ?> | Plot 43 Nasser/Nkrumah Road, Kampala</span>
    <span><?= SITE_PHONE ?> | <?= SITE_EMAIL ?></span>
    <span>Printed <?= date('d M Y') ?></span>
  </div>
</div>
</body>
</html>
