<?php
/**
 * Print-ready delivery note — open in new tab → Ctrl+P → Save as PDF
 */
$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: /crm/delivery/'); exit; }

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/crm_helpers.php';
require_once __DIR__ . '/../../includes/crm_delivery.php';
require_login();

$dn = null; $items = [];
if ($pdo) {
    ensure_delivery_tables($pdo);
    $sql = "SELECT d.*, c.name client_name, c.email client_email, c.phone client_phone, c.company client_company, c.address client_address, c.city client_city,
                   q.quote_number, i.invoice_number, a.full_name prepared_by, %s
            FROM delivery_notes d JOIN clients c ON d.client_id=c.id
            LEFT JOIN quotations q ON d.quotation_id=q.id
            LEFT JOIN invoices i ON d.invoice_id=i.id
            LEFT JOIN admin_users a ON d.created_by=a.id
            WHERE d.id=?";
    try {
        $s = $pdo->prepare(sprintf($sql, 'a.signature_path'));
        $s->execute([$id]);
    } catch (PDOException $e) { // signature column not added on this database yet
        $s = $pdo->prepare(sprintf($sql, 'NULL AS signature_path'));
        $s->execute([$id]);
    }
    $dn = $s->fetch();
    if ($dn) { $si = $pdo->prepare("SELECT * FROM delivery_note_items WHERE delivery_note_id=? ORDER BY sort_order"); $si->execute([$id]); $items = $si->fetchAll(); }
}
if (!$dn) { echo 'Delivery note not found.'; exit; }

$wa_display = '+256 702 371 230';
$phone_2    = '+256 782 187 799';
$addr = $dn['delivery_address'] ?: trim(unclean($dn['client_address']) . ($dn['client_city'] ? ', ' . unclean($dn['client_city']) : ''), ', ');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Delivery Note <?= htmlspecialchars($dn['dn_number']) ?></title>
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
    .sig-section{display:flex;justify-content:space-between;gap:48px;margin-top:32px;margin-bottom:24px;padding-top:20px;border-top:1px solid #e2e8f0}
    .sig-box{flex:1}
    .sig-box .sig-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#94a3b8;margin-bottom:10px}
    .sig-box .sig-area{height:72px;border:1.5px dashed #cbd5e1;border-radius:6px;background:#f8fafc;display:flex;align-items:center;justify-content:center;margin-bottom:8px}
    .sig-box .sig-area img{max-height:60px;max-width:200px;object-fit:contain}
    .sig-box .sig-area .sig-placeholder{font-size:10px;color:#cbd5e1;letter-spacing:.04em}
    .sig-box .sig-name{font-size:12px;color:#334155;font-weight:600}
    .sig-box .sig-sub{font-size:10.5px;color:#94a3b8;margin-top:2px}
    /* ── Footer ── */
    .footer-strip{border-top:1px solid #e2e8f0;padding-top:14px;display:flex;justify-content:space-between;font-size:10.5px;color:#94a3b8}

    /* ── Print button ── */
    .print-btn{position:fixed;top:16px;right:16px;background:#f59e0b;color:#fff;border:none;padding:10px 20px;border-radius:8px;font-size:14px;font-weight:600;cursor:pointer;box-shadow:0 2px 8px rgba(0,0,0,.15)}
    @media print{.print-btn{display:none}body{padding:20px}}
  
    .tbl-qty{text-align:center}
    thead th:last-child,thead th:nth-last-child(2),thead th:nth-last-child(3),td:last-child,td:nth-last-child(2),td:nth-last-child(3){text-align:left}
    thead th.c,td.c{text-align:center}
  </style>
</head>
<body>
<?php $pdf_filename = $dn['dn_number']; include __DIR__ . '/../partials/pdf_toolbar.php'; ?>

<div class="page">

  <!-- ── Header: Logo + Contact block ── -->
  <div class="header">
    <div class="logo-block">
      <img src="/assets/images/logo_main.png" alt="Ambozy Graphics Solutions Ltd">
      <div>
        <div class="company-name"><?= SITE_NAME ?></div>
        <div class="company-sub">Print · Brand · Deliver</div>
        <div class="company-sub" style="margin-top:4px;font-size:10px;color:#94a3b8">Plot 1314 Church Road, Buye, Ntinda, Kampala</div>
      </div>
    </div>
    <div class="contact-right">
      <div>✉ <a href="mailto:<?= SITE_EMAIL ?>"><?= SITE_EMAIL ?></a></div>
      <div style="color:#EA4335;font-weight:600">
        <svg style="width:12px;height:12px;vertical-align:middle;margin-right:3px" viewBox="0 0 24 24" fill="currentColor"><path d="M24 5.457v13.909c0 .904-.732 1.636-1.636 1.636h-3.819V11.73L12 16.64l-6.545-4.91v9.273H1.636A1.636 1.636 0 0 1 0 19.366V5.457c0-2.023 2.309-3.178 3.927-1.964L5.455 4.64 12 9.548l6.545-4.91 1.528-1.145C21.69 2.28 24 3.434 24 5.457z"/></svg>ambozygraphics@gmail.com
      </div>
      <div class="wa">
        <svg style="width:12px;height:12px;vertical-align:middle;margin-right:3px" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg><?= $wa_display ?>
      </div>
      <div>☎ <?= $phone_2 ?></div>
      <div>🌐 <a href="<?= SITE_URL ?>"><?= str_replace(['https://','http://'], '', SITE_URL) ?></a></div>
    </div>
  </div>

  <hr class="divider">
  <div class="title-bar">
    <div>
      <h1>DELIVERY NOTE</h1>
      <div class="doc-no"><?= htmlspecialchars($dn['dn_number']) ?></div>
      <div class="status-badge"><?= strtoupper($dn['status']) ?></div>
    </div>
  </div>

  <div class="meta-grid">
    <div class="meta-box">
      <h3>Deliver To</h3>
      <p style="font-weight:600"><?= htmlspecialchars($dn['delivered_to'] ?: $dn['client_name']) ?></p>
      <?php if ($dn['client_company']): ?><p><?= htmlspecialchars(unclean($dn['client_company'])) ?></p><?php endif; ?>
      <?php if ($addr): ?><p><?= htmlspecialchars($addr) ?></p><?php endif; ?>
      <?php if ($dn['client_phone']): ?><p><?= htmlspecialchars($dn['client_phone']) ?></p><?php endif; ?>
    </div>
    <div class="meta-box">
      <h3>Delivery Details</h3>
      <p><strong>Subject:</strong> <?= htmlspecialchars($dn['title']) ?></p>
      <p class="label" style="margin-top:8px">Delivery Date</p>
      <p><?= date('d F Y', strtotime($dn['delivery_date'])) ?></p>
      <?php if ($dn['quote_number'] || $dn['invoice_number']): ?>
        <p class="label" style="margin-top:6px">Reference</p>
        <p><?= htmlspecialchars(trim(($dn['quote_number'] ? 'Quote ' . $dn['quote_number'] : '') . ' ' . ($dn['invoice_number'] ? 'Invoice ' . $dn['invoice_number'] : ''))) ?></p>
      <?php endif; ?>
      <?php if ($dn['driver_name'] || $dn['vehicle_no']): ?>
        <p class="label" style="margin-top:6px">Driver / Vehicle</p>
        <p><?= htmlspecialchars(trim(($dn['driver_name'] ?? '') . ($dn['vehicle_no'] ? ' · ' . $dn['vehicle_no'] : ''), ' ·')) ?></p>
      <?php endif; ?>
    </div>
  </div>

  <table>
    <thead><tr>
      <th style="width:30px">#</th><th>Description</th><th class="c" style="width:90px">Qty</th><th style="width:100px">Unit</th>
    </tr></thead>
    <tbody>
      <?php foreach ($items as $i => $it): ?>
      <tr><td><?= $i + 1 ?></td><td><?= htmlspecialchars($it['description']) ?></td><td class="c"><?= $it['quantity'] + 0 ?></td><td><?= htmlspecialchars($it['unit']) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php if ($dn['notes']): ?>
    <div class="notes-section"><h3>Notes</h3><?= nl2br(htmlspecialchars($dn['notes'])) ?></div>
  <?php endif; ?>

  <p style="font-size:11.5px;color:#64748b;margin-bottom:8px">Please check the goods on receipt. The above items were received in good order and condition unless otherwise stated.</p>

  <div class="sig-section">
    <div class="sig-box">
      <div class="sig-label">Delivered by</div>
      <div class="sig-area">
        <?php
          $sig = $dn['signature_path'] ?? null;
          $sig_file = $sig ? __DIR__ . '/../../' . $sig : null;
          if ($sig_file && file_exists($sig_file)): ?>
          <img src="/<?= htmlspecialchars($sig) ?>" alt="Signature">
        <?php else: ?><span class="sig-placeholder">Sign here</span><?php endif; ?>
      </div>
      <div class="sig-name"><?= htmlspecialchars($dn['driver_name'] ?: ($dn['prepared_by'] ?: 'Ambozy Team')) ?></div>
      <div class="sig-sub"><?= SITE_NAME ?> · Signature &amp; Date</div>
    </div>
    <div class="sig-box" style="text-align:right">
      <div class="sig-label">Received by</div>
      <div class="sig-area"><span class="sig-placeholder">Sign here</span></div>
      <div class="sig-name"><?= htmlspecialchars($dn['received_by'] ?: ($dn['delivered_to'] ?: $dn['client_name'])) ?></div>
      <div class="sig-sub">Name, Signature, Stamp &amp; Date</div>
    </div>
  </div>

  <div class="footer-strip">
    <span><?= SITE_NAME ?> | Plot 1314 Church Road, Buye, Ntinda, Kampala</span>
    <span><?= $wa_display ?> | <?= SITE_EMAIL ?></span>
    <span>Printed <?= date('d M Y') ?></span>
  </div>

</div>
</body>
</html>
