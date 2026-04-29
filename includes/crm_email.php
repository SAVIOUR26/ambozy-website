<?php
/**
 * CRM email helpers — HTML templates + send wrapper
 */

/**
 * Send an HTML email via PHP mail().
 * Logs outcome to email_logs table.
 */
function crm_send_email(
    PDO    $pdo,
    string $type,
    string $to_email,
    string $to_name,
    string $subject,
    string $html_body,
    string $related_type = null,
    int    $related_id   = null
): bool {
    $from_name  = SITE_NAME;
    $from_email = MAIL_FROM;

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$from_name} <{$from_email}>\r\n";
    $headers .= "Reply-To: " . SITE_EMAIL . "\r\n";
    $headers .= "X-Mailer: AmbozyGCRM/1.0\r\n";

    $sent = @mail($to_email, $subject, $html_body, $headers);

    $pdo->prepare(
        "INSERT INTO email_logs (type, recipient_email, recipient_name, subject, status, related_type, related_id)
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    )->execute([
        $type, $to_email, $to_name, $subject,
        $sent ? 'sent' : 'failed',
        $related_type, $related_id,
    ]);

    return $sent;
}

/**
 * Wrap content in the standard branded email shell.
 */
function email_shell(string $body_html, string $preheader = ''): string
{
    $year  = date('Y');
    $name  = SITE_NAME;
    $phone = SITE_PHONE;
    $email = SITE_EMAIL;
    $url   = SITE_URL;

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$name}</title>
<style>
  body{margin:0;padding:0;background:#f4f4f5;font-family:Inter,-apple-system,sans-serif;color:#1e293b}
  .wrap{max-width:600px;margin:32px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.08)}
  .head{background:#0f172a;padding:28px 36px;display:flex;align-items:center;gap:14px}
  .logo{background:#f59e0b;width:40px;height:40px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;font-weight:700;color:#fff;font-size:14px;flex-shrink:0}
  .head-title{color:#fff;font-size:16px;font-weight:600;margin:0}
  .head-sub{color:#94a3b8;font-size:12px;margin:2px 0 0}
  .body{padding:36px}
  .btn{display:inline-block;background:#f59e0b;color:#fff!important;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:600;font-size:14px;margin:16px 0}
  .divider{border:none;border-top:1px solid #e2e8f0;margin:24px 0}
  .info-box{background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:20px;margin:20px 0}
  .info-row{display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid #f1f5f9;font-size:14px}
  .info-row:last-child{border-bottom:none;font-weight:600;font-size:15px}
  .info-label{color:#64748b}
  .info-value{color:#1e293b;font-weight:500}
  .footer{background:#f8fafc;border-top:1px solid #e2e8f0;padding:24px 36px;font-size:12px;color:#94a3b8;text-align:center}
  h2{font-size:22px;margin:0 0 6px;color:#0f172a}
  p{font-size:14px;line-height:1.7;color:#475569;margin:0 0 14px}
  table.items{width:100%;border-collapse:collapse;font-size:13px;margin:16px 0}
  table.items th{background:#f8fafc;text-align:left;padding:10px 12px;color:#64748b;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.05em}
  table.items td{padding:10px 12px;border-bottom:1px solid #f1f5f9;color:#334155}
  table.items tr:last-child td{border-bottom:none}
  .badge{display:inline-block;padding:3px 10px;border-radius:99px;font-size:12px;font-weight:600}
</style>
</head>
<body>
<span style="display:none;max-height:0;overflow:hidden">{$preheader}</span>
<div class="wrap">
  <div class="head">
    <div class="logo">AG</div>
    <div>
      <p class="head-title">{$name}</p>
      <p class="head-sub">Print · Brand · Deliver</p>
    </div>
  </div>
  <div class="body">
    {$body_html}
  </div>
  <div class="footer">
    <p style="margin:0 0 6px">{$name}</p>
    <p style="margin:0">📞 {$phone} &nbsp;·&nbsp; ✉️ {$email}</p>
    <p style="margin:8px 0 0">&copy; {$year} {$name}. All rights reserved.</p>
  </div>
</div>
</body>
</html>
HTML;
}

// ── Specific email builders ───────────────────────────────────

function email_lead_acknowledgement(string $client_name, string $service = '', string $ref = ''): string
{
    $service_line = $service ? "<p>Service interest: <strong>{$service}</strong></p>" : '';
    $ref_line     = $ref     ? "<p style='color:#94a3b8;font-size:12px'>Reference: {$ref}</p>" : '';
    $body = <<<HTML
<h2>Thank you for reaching out! 👋</h2>
<p>Hi {$client_name},</p>
<p>We've received your inquiry and one of our team members will get back to you within <strong>24 hours</strong> with a personalised quote.</p>
{$service_line}
<p>In the meantime, feel free to reach us on WhatsApp for a faster response.</p>
{$ref_line}
<a href="https://wa.me/256782187799" class="btn">Chat on WhatsApp</a>
<hr class="divider">
<p style="font-size:13px;color:#94a3b8">We print. We brand. We deliver — Est. 2010, Kampala.</p>
HTML;
    return email_shell($body, 'We received your inquiry — we\'ll be in touch within 24 hours.');
}

function email_quotation(array $quote, array $client, array $items): string
{
    $valid_until = $quote['valid_until'] ? date('d M Y', strtotime($quote['valid_until'])) : 'On request';
    $items_html  = '';
    foreach ($items as $item) {
        $items_html .= "<tr>
            <td>{$item['description']}</td>
            <td style='text-align:center'>{$item['quantity']} {$item['unit']}</td>
            <td style='text-align:right'>UGX " . number_format($item['unit_price'], 0) . "</td>
            <td style='text-align:right;font-weight:600'>UGX " . number_format($item['total'], 0) . "</td>
        </tr>";
    }

    $disc_row = $quote['discount_percent'] > 0
        ? "<div class='info-row'><span class='info-label'>Discount ({$quote['discount_percent']}%)</span><span class='info-value'>- UGX " . number_format($quote['subtotal'] * $quote['discount_percent'] / 100, 0) . "</span></div>" : '';
    $tax_row  = $quote['tax_percent'] > 0
        ? "<div class='info-row'><span class='info-label'>Tax ({$quote['tax_percent']}%)</span><span class='info-value'>UGX " . number_format(($quote['subtotal'] * (1 - $quote['discount_percent']/100)) * $quote['tax_percent'] / 100, 0) . "</span></div>" : '';

    $body = <<<HTML
<h2>Your Quotation is Ready 📄</h2>
<p>Hi {$client['name']},</p>
<p>Please find your quotation from <strong>Ambozy Graphics Solutions Ltd</strong> below.</p>
<div class="info-box" style="margin-bottom:20px">
  <div class="info-row"><span class="info-label">Quote No.</span><span class="info-value" style="font-family:monospace">{$quote['quote_number']}</span></div>
  <div class="info-row"><span class="info-label">Subject</span><span class="info-value">{$quote['title']}</span></div>
  <div class="info-row"><span class="info-label">Valid Until</span><span class="info-value">{$valid_until}</span></div>
</div>
<table class="items">
  <thead><tr><th>Description</th><th style="text-align:center">Qty</th><th style="text-align:right">Unit Price</th><th style="text-align:right">Total</th></tr></thead>
  <tbody>{$items_html}</tbody>
</table>
<div class="info-box">
  <div class="info-row"><span class="info-label">Subtotal</span><span class="info-value">UGX {$quote['subtotal']}</span></div>
  {$disc_row}
  {$tax_row}
  <div class="info-row"><span class="info-label">Total</span><span class="info-value" style="color:#0f172a;font-size:16px">UGX {$quote['total']}</span></div>
</div>
HTML;

    if ($quote['notes']) {
        $body .= "<p style='font-size:13px;color:#64748b'><strong>Notes:</strong> {$quote['notes']}</p>";
    }
    if ($quote['terms']) {
        $body .= "<hr class='divider'><p style='font-size:12px;color:#94a3b8'><strong>Terms &amp; Conditions:</strong><br>{$quote['terms']}</p>";
    }

    $body .= "<p>To accept this quotation or ask any questions, reply to this email or reach us on WhatsApp.</p>
              <a href='https://wa.me/256782187799?text=Hi%2C+I%27m+accepting+quote+{$quote['quote_number']}' class='btn'>Accept on WhatsApp</a>";

    return email_shell($body, "Your quote {$quote['quote_number']} from Ambozy Graphics");
}

function email_invoice(array $invoice, array $client, array $items): string
{
    $due_date   = $invoice['due_date'] ? date('d M Y', strtotime($invoice['due_date'])) : 'On receipt';
    $items_html = '';
    foreach ($items as $item) {
        $items_html .= "<tr>
            <td>{$item['description']}</td>
            <td style='text-align:center'>{$item['quantity']} {$item['unit']}</td>
            <td style='text-align:right'>UGX " . number_format($item['unit_price'], 0) . "</td>
            <td style='text-align:right;font-weight:600'>UGX " . number_format($item['total'], 0) . "</td>
        </tr>";
    }

    $body = <<<HTML
<h2>Invoice {$invoice['invoice_number']} 🧾</h2>
<p>Hi {$client['name']},</p>
<p>Please find your invoice from <strong>Ambozy Graphics Solutions Ltd</strong>. Payment is due by <strong>{$due_date}</strong>.</p>
<div class="info-box" style="margin-bottom:20px">
  <div class="info-row"><span class="info-label">Invoice No.</span><span class="info-value" style="font-family:monospace">{$invoice['invoice_number']}</span></div>
  <div class="info-row"><span class="info-label">Due Date</span><span class="info-value">{$due_date}</span></div>
</div>
<table class="items">
  <thead><tr><th>Description</th><th style="text-align:center">Qty</th><th style="text-align:right">Unit Price</th><th style="text-align:right">Total</th></tr></thead>
  <tbody>{$items_html}</tbody>
</table>
<div class="info-box">
  <div class="info-row"><span class="info-label">Total Due</span><span class="info-value" style="color:#0f172a;font-size:18px;font-weight:700">UGX {$invoice['total']}</span></div>
</div>
<p><strong>Payment Options:</strong></p>
<p style="font-size:13px;color:#475569">
  📱 Mobile Money (MTN/Airtel) — 0782 187 799<br>
  🏦 Bank Transfer — Please contact us for bank details<br>
  💵 Cash — At our offices, Plot 43 Nasser/Nkrumah Road, Kampala
</p>
<p>Please include the invoice number <strong>{$invoice['invoice_number']}</strong> as the payment reference.</p>
<a href="https://wa.me/256782187799" class="btn">Confirm Payment via WhatsApp</a>
HTML;

    if ($invoice['notes']) {
        $body .= "<hr class='divider'><p style='font-size:13px;color:#64748b'>{$invoice['notes']}</p>";
    }

    return email_shell($body, "Invoice {$invoice['invoice_number']} — UGX {$invoice['total']} due {$due_date}");
}

function email_order_status(array $order, array $client): string
{
    $status_messages = [
        'in_production' => ['title' => 'Your Order is in Production ⚙️', 'msg' => 'Great news! Your order is now in production. We\'ll notify you as soon as it\'s ready.'],
        'ready'         => ['title' => 'Your Order is Ready! 🎉',         'msg' => 'Your order is ready for collection or delivery. Please contact us to arrange pick-up or confirm delivery.'],
        'delivered'     => ['title' => 'Order Delivered ✅',               'msg' => 'Your order has been delivered. We hope you love it! Please let us know if everything is to your satisfaction.'],
        'completed'     => ['title' => 'Order Completed ✅',               'msg' => 'Your order has been completed. Thank you for choosing Ambozy Graphics Solutions!'],
    ];

    $info = $status_messages[$order['status']] ?? ['title' => 'Order Update', 'msg' => "Your order {$order['order_number']} has been updated to: {$order['status']}."];
    $due  = $order['due_date'] ? date('d M Y', strtotime($order['due_date'])) : null;

    $body = <<<HTML
<h2>{$info['title']}</h2>
<p>Hi {$client['name']},</p>
<p>{$info['msg']}</p>
<div class="info-box">
  <div class="info-row"><span class="info-label">Order No.</span><span class="info-value" style="font-family:monospace">{$order['order_number']}</span></div>
  <div class="info-row"><span class="info-label">Description</span><span class="info-value">{$order['title']}</span></div>
  <div class="info-row"><span class="info-label">Status</span><span class="info-value">{$order['status']}</span></div>
  HTML;
    if ($due) {
        $body .= "<div class='info-row'><span class='info-label'>Due Date</span><span class='info-value'>{$due}</span></div>";
    }
    $body .= "</div>";

    if ($order['status'] === 'ready') {
        $body .= "<a href='https://wa.me/256782187799?text=Hi%2C+I%27d+like+to+collect+order+{$order['order_number']}' class='btn'>Arrange Collection</a>";
    }

    return email_shell($body, "Order {$order['order_number']} update from Ambozy Graphics");
}

function email_payment_receipt(array $payment, array $invoice, array $client): string
{
    $balance = $invoice['total'] - $invoice['amount_paid'];
    $body    = <<<HTML
<h2>Payment Received ✅</h2>
<p>Hi {$client['name']},</p>
<p>We've received your payment. Thank you!</p>
<div class="info-box">
  <div class="info-row"><span class="info-label">Invoice No.</span><span class="info-value" style="font-family:monospace">{$invoice['invoice_number']}</span></div>
  <div class="info-row"><span class="info-label">Amount Received</span><span class="info-value" style="color:#16a34a;font-weight:700">UGX {$payment['amount']}</span></div>
  <div class="info-row"><span class="info-label">Payment Method</span><span class="info-value">{$payment['method']}</span></div>
  <div class="info-row"><span class="info-label">Date</span><span class="info-value">{$payment['payment_date']}</span></div>
  <div class="info-row"><span class="info-label">Balance Remaining</span><span class="info-value" style="color:<?= $balance > 0 ? '#dc2626' : '#16a34a' ?>">UGX {$balance}</span></div>
</div>
<p>Thank you for your business. We appreciate you choosing Ambozy Graphics Solutions!</p>
HTML;
    return email_shell($body, "Payment receipt for {$invoice['invoice_number']}");
}
