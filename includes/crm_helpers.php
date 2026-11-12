<?php
/**
 * CRM helper functions — document numbering, activity logging, formatting
 */

/**
 * Generate the next sequential document number.
 * e.g. next_doc_number('QUO') → 'QUO-2026-0003'
 */
function next_doc_number(PDO $pdo, string $prefix): string
{
    $year = (int) date('Y');

    // Join the caller's transaction if one is already open
    $own_tx = !$pdo->inTransaction();
    if ($own_tx) { $pdo->beginTransaction(); }
    try {
        $stmt = $pdo->prepare(
            "INSERT INTO doc_sequences (prefix, year, last_number)
             VALUES (?, ?, 1)
             ON DUPLICATE KEY UPDATE last_number = last_number + 1"
        );
        $stmt->execute([$prefix, $year]);

        $num = (int) $pdo->query(
            "SELECT last_number FROM doc_sequences
             WHERE prefix = " . $pdo->quote($prefix) . "
               AND year   = $year"
        )->fetchColumn();

        if ($own_tx) { $pdo->commit(); }
    } catch (Throwable $e) {
        if ($own_tx && $pdo->inTransaction()) { $pdo->rollBack(); }
        throw $e;
    }

    return sprintf('%s-%d-%04d', $prefix, $year, $num);
}

/**
 * Log an activity to the activity stream.
 */
function log_activity(PDO $pdo, string $type, string $description, ?string $related_type = null, ?int $related_id = null): void
{
    $uid = $_SESSION['admin_id'] ?? null;
    $stmt = $pdo->prepare(
        "INSERT INTO activities (type, description, related_type, related_id, created_by)
         VALUES (?, ?, ?, ?, ?)"
    );
    $stmt->execute([$type, $description, $related_type, $related_id, $uid]);
}

/**
 * Format a UGX amount with thousand separators.
 */
function fmt_money(float $amount): string
{
    return 'UGX ' . number_format($amount, 0, '.', ',');
}

/**
 * Return a Tailwind badge class for a lead status.
 */
function lead_badge(string $status): string
{
    return match ($status) {
        'new'       => 'bg-blue-100 text-blue-700',
        'contacted' => 'bg-yellow-100 text-yellow-700',
        'qualified' => 'bg-purple-100 text-purple-700',
        'quoted'    => 'bg-indigo-100 text-indigo-700',
        'won'       => 'bg-green-100 text-green-700',
        'lost'      => 'bg-red-100 text-red-700',
        default     => 'bg-gray-100 text-gray-600',
    };
}

/**
 * Return a Tailwind badge class for a client status.
 */
function client_badge(string $status): string
{
    return match ($status) {
        'active'   => 'bg-green-100 text-green-700',
        'inactive' => 'bg-gray-100 text-gray-500',
        default    => 'bg-gray-100 text-gray-600',
    };
}

/**
 * Return a Tailwind badge class for an order status.
 */
function order_badge(string $status): string
{
    return match ($status) {
        'pending'       => 'bg-yellow-100 text-yellow-700',
        'in_production' => 'bg-blue-100 text-blue-700',
        'ready'         => 'bg-indigo-100 text-indigo-700',
        'delivered'     => 'bg-teal-100 text-teal-700',
        'completed'     => 'bg-green-100 text-green-700',
        'cancelled'     => 'bg-red-100 text-red-700',
        default         => 'bg-gray-100 text-gray-600',
    };
}

/**
 * Return a Tailwind badge class for an invoice status.
 */
function invoice_badge(string $status): string
{
    return match ($status) {
        'draft'     => 'bg-gray-100 text-gray-600',
        'sent'      => 'bg-blue-100 text-blue-700',
        'partial'   => 'bg-yellow-100 text-yellow-700',
        'paid'      => 'bg-green-100 text-green-700',
        'overdue'   => 'bg-red-100 text-red-700',
        'cancelled' => 'bg-gray-100 text-gray-400',
        default     => 'bg-gray-100 text-gray-600',
    };
}

/**
 * Return a Tailwind badge class for a quotation status.
 */
function quote_badge(string $status): string
{
    return match ($status) {
        'draft'    => 'bg-gray-100 text-gray-600',
        'sent'     => 'bg-blue-100 text-blue-700',
        'accepted' => 'bg-green-100 text-green-700',
        'rejected' => 'bg-red-100 text-red-700',
        'expired'  => 'bg-orange-100 text-orange-700',
        default    => 'bg-gray-100 text-gray-600',
    };
}

/**
 * Return a Tailwind badge class for a purchase/supplier payment status.
 */
function purchase_badge(string $status): string
{
    return match ($status) {
        'pending'   => 'bg-yellow-100 text-yellow-700',
        'received'  => 'bg-blue-100 text-blue-700',
        'partial'   => 'bg-orange-100 text-orange-700',
        'paid'      => 'bg-green-100 text-green-700',
        'cancelled' => 'bg-gray-100 text-gray-400',
        default     => 'bg-gray-100 text-gray-600',
    };
}

/**
 * Return a Tailwind badge class for a loan status.
 */
function loan_badge(string $status): string
{
    return match ($status) {
        'active'      => 'bg-blue-100 text-blue-700',
        'fully_paid'  => 'bg-green-100 text-green-700',
        'defaulted'   => 'bg-red-100 text-red-700',
        'written_off' => 'bg-gray-100 text-gray-400',
        default       => 'bg-gray-100 text-gray-600',
    };
}

/**
 * Return a Tailwind badge class for a payroll status.
 */
function payroll_badge(string $status): string
{
    return match ($status) {
        'draft'    => 'bg-gray-100 text-gray-600',
        'approved' => 'bg-blue-100 text-blue-700',
        'paid'     => 'bg-green-100 text-green-700',
        default    => 'bg-gray-100 text-gray-600',
    };
}

/**
 * Return a Tailwind badge class for a statutory obligation status.
 */
function statutory_badge(string $status): string
{
    return match ($status) {
        'pending' => 'bg-yellow-100 text-yellow-700',
        'paid'    => 'bg-green-100 text-green-700',
        'partial' => 'bg-orange-100 text-orange-700',
        'overdue' => 'bg-red-100 text-red-700',
        default   => 'bg-gray-100 text-gray-600',
    };
}

/**
 * Return a Tailwind badge class for an employee status.
 */
function employee_badge(string $status): string
{
    return match ($status) {
        'active'     => 'bg-green-100 text-green-700',
        'terminated' => 'bg-red-100 text-red-700',
        'on_leave'   => 'bg-yellow-100 text-yellow-700',
        default      => 'bg-gray-100 text-gray-600',
    };
}

/**
 * Calculate Uganda PAYE (monthly) on a given gross salary (UGX).
 * Rates per URA individual income tax (2024/2025):
 *   0 – 235,000          : 0%
 *   235,001 – 335,000    : 10% on excess of 235,000
 *   335,001 – 410,000    : 10,000 + 20% on excess of 335,000
 *   410,001 – 10,000,000 : 25,000 + 30% on excess of 410,000
 *   Over 10,000,000      : +10% additional on excess (upper band)
 */
function calculate_paye(float $gross): float
{
    if ($gross <= 235000) return 0.0;
    if ($gross <= 335000) return ($gross - 235000) * 0.10;
    if ($gross <= 410000) return 10000 + ($gross - 335000) * 0.20;
    if ($gross <= 10000000) return 25000 + ($gross - 410000) * 0.30;
    return 25000 + (10000000 - 410000) * 0.30 + ($gross - 10000000) * 0.40;
}

/**
 * Sanitise a plain text string from POST/GET input.
 */
function clean(string $val): string
{
    return trim(htmlspecialchars($val, ENT_QUOTES, 'UTF-8'));
}

/**
 * Redirect and exit.
 */
function redirect(string $url): never
{
    header("Location: $url");
    exit;
}

/**
 * Flash message helpers (stored in session).
 */
function flash(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function get_flash(): ?array
{
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

/**
 * CSRF protection for state-changing POST actions (delete, etc.)
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$_POST['csrf']);
}

/**
 * Edit + Delete icon buttons for a list row / detail page.
 *   $base    e.g. '/crm/loans'  (edit page = $base/edit?id=, delete handler = $base/delete)
 *   $confirm text shown in the browser confirm dialog
 */
function row_actions(string $base, int $id, string $confirm = 'Delete this record? This cannot be undone.'): string
{
    $edit = '<a href="' . $base . '/edit?id=' . $id . '" title="Edit" class="inline-flex p-1.5 rounded-md text-gray-400 hover:text-amber-600 hover:bg-amber-50 transition-colors">'
          . '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>';
    $del  = '<form method="POST" action="' . $base . '/delete" class="inline" onsubmit="return confirm(' . htmlspecialchars(json_encode($confirm), ENT_QUOTES) . ')">'
          . csrf_field() . '<input type="hidden" name="id" value="' . $id . '">'
          . '<button type="submit" title="Delete" class="inline-flex p-1.5 rounded-md text-gray-400 hover:text-red-600 hover:bg-red-50 transition-colors">'
          . '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></form>';
    return '<span class="inline-flex items-center gap-0.5">' . $edit . $del . '</span>';
}

/**
 * Reload previously stored (HTML-escaped by clean()) text so it can be shown in an edit form.
 */
function unclean(?string $val): string
{
    return html_entity_decode((string)$val, ENT_QUOTES, 'UTF-8');
}

/**
 * Make sure admin_users.signature_path exists (used by the PDF signature block and the Profile page).
 * Safe to call repeatedly; it only alters the table once.
 */
function ensure_signature_column(PDO $pdo): void
{
    try {
        if (!$pdo->query("SHOW COLUMNS FROM admin_users LIKE 'signature_path'")->fetch()) {
            $pdo->exec("ALTER TABLE admin_users ADD COLUMN signature_path VARCHAR(255) DEFAULT NULL");
        }
    } catch (Throwable $e) {
        error_log('ensure_signature_column: ' . $e->getMessage());
    }
}

/**
 * Client selection for quotations, invoices and delivery notes.
 * The client <select> may send an existing client id, or "new" together with a typed name
 * (a one-time / walk-in client that is saved automatically).
 *
 * Returns: client id (> 0) for an existing client, -1 for a valid new client, 0 if invalid.
 * Sets $errors['client_id'] when invalid.
 */
function client_choice(array &$errors): int
{
    $raw = $_POST['client_id'] ?? '';
    if ($raw === 'new') {
        $name  = trim($_POST['new_client_name'] ?? '');
        $email = trim($_POST['new_client_email'] ?? '');
        if ($name === '') { $errors['client_id'] = "Type the client's name."; return 0; }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors['client_id'] = 'The client email address is not valid.'; return 0; }
        return -1;
    }
    $id = (int)$raw;
    if ($id <= 0) { $errors['client_id'] = 'Select a client or choose "New / one-time client".'; return 0; }
    return $id;
}

/**
 * Save the typed one-time client and return its id.
 * An existing client with the same name (and same phone, when given) is reused instead of duplicated.
 */
function create_one_time_client(PDO $pdo): int
{
    $name    = clean($_POST['new_client_name']    ?? '');
    $phone   = clean($_POST['new_client_phone']   ?? '');
    $email   = trim($_POST['new_client_email']    ?? '');
    $company = clean($_POST['new_client_company'] ?? '');

    $q = $pdo->prepare("SELECT id FROM clients WHERE LOWER(name) = LOWER(?) AND IFNULL(phone,'') = ? LIMIT 1");
    $q->execute([$name, $phone]);
    if ($existing = $q->fetchColumn()) { return (int)$existing; }

    $code = next_doc_number($pdo, 'CLI');
    $pdo->prepare(
        "INSERT INTO clients (code, name, email, phone, company, type, source, notes, created_by)
         VALUES (?,?,?,?,?,?,?,?,?)"
    )->execute([
        $code, $name, $email ?: null, $phone ?: null, $company ?: null,
        $company ? 'business' : 'individual', 'walk-in',
        'Added on the spot while preparing a document (one-time client).',
        $_SESSION['admin_id'] ?? null,
    ]);
    $id = (int)$pdo->lastInsertId();
    log_activity($pdo, 'client_created', "Client {$code} — {$name} added while creating a document.", 'client', $id);
    return $id;
}
