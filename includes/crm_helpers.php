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

    $pdo->beginTransaction();
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

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }

    return sprintf('%s-%d-%04d', $prefix, $year, $num);
}

/**
 * Log an activity to the activity stream.
 */
function log_activity(PDO $pdo, string $type, string $description, string $related_type = null, int $related_id = null): void
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
