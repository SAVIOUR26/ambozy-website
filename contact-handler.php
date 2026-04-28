<?php
/**
 * AMBOZY GRAPHICS SOLUTIONS LTD
 * Quote Inquiry Form Handler
 */

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Sanitize inputs
function sanitize(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES, 'UTF-8');
}

$name     = sanitize($_POST['name']    ?? '');
$email    = sanitize($_POST['email']   ?? '');
$phone    = sanitize($_POST['phone']   ?? '');
$company  = sanitize($_POST['company'] ?? '');
$service  = sanitize($_POST['service'] ?? '');
$budget   = sanitize($_POST['budget']  ?? '');
$message  = sanitize($_POST['message'] ?? '');

// Validation
$errors = [];
if (strlen($name) < 2)              $errors[] = 'Please enter your full name.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
if (strlen($message) < 10)         $errors[] = 'Please describe your project briefly.';

if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// CSRF-light honeypot check
if (!empty($_POST['website'])) {
    echo json_encode(['success' => true]); // silently discard bots
    exit;
}

// Store in DB (if available) - optional, gracefully skipped
try {
    if (file_exists(__DIR__ . '/config.php')) {
        require_once __DIR__ . '/config.php';
        if (isset($pdo)) {
            $stmt = $pdo->prepare("INSERT INTO inquiries (name, email, phone, company, service, budget, message, created_at)
                                   VALUES (:name, :email, :phone, :company, :service, :budget, :message, NOW())");
            $stmt->execute(compact('name','email','phone','company','service','budget','message'));
        }
    }
} catch (Throwable $e) {
    // DB save failed — still try to send email
    error_log('Ambozy inquiry DB error: ' . $e->getMessage());
}

// Send email notification
$to      = 'ambozygraphics@gmail.com';
$subject = "New Quote Inquiry from $name — Ambozy Graphics";
$body    = "New quote inquiry received via ambozygraphics.shop\n\n"
         . "Name:     $name\n"
         . "Email:    $email\n"
         . "Phone:    $phone\n"
         . "Company:  $company\n"
         . "Service:  $service\n"
         . "Budget:   $budget\n\n"
         . "Message:\n$message\n\n"
         . "---\nSent: " . date('Y-m-d H:i:s');

$headers  = "From: noreply@ambozygraphics.shop\r\n";
$headers .= "Reply-To: $email\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$sent = mail($to, $subject, $body, $headers);

if ($sent) {
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! We received your inquiry and will contact you within 24 hours.'
    ]);
} else {
    // Still acknowledge — DB likely saved it
    echo json_encode([
        'success' => true,
        'message' => 'Thank you! Your inquiry was received. We will be in touch shortly.'
    ]);
}
