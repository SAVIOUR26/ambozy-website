<?php
/**
 * Delivery notes — table bootstrap + small helpers.
 * ensure_delivery_tables() is idempotent, so no manual SQL import is needed on the server.
 */

function ensure_delivery_tables(PDO $pdo): void
{
    static $done = false;
    if ($done) return;

    $pdo->exec("CREATE TABLE IF NOT EXISTS `delivery_notes` (
      `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
      `dn_number`        VARCHAR(20)   NOT NULL UNIQUE,
      `client_id`        INT UNSIGNED  NOT NULL,
      `quotation_id`     INT UNSIGNED  DEFAULT NULL,
      `invoice_id`       INT UNSIGNED  DEFAULT NULL,
      `order_id`         INT UNSIGNED  DEFAULT NULL,
      `title`            VARCHAR(200)  NOT NULL,
      `delivery_date`    DATE          NOT NULL,
      `delivered_to`     VARCHAR(150)  DEFAULT NULL,
      `delivery_address` TEXT          DEFAULT NULL,
      `driver_name`      VARCHAR(100)  DEFAULT NULL,
      `vehicle_no`       VARCHAR(40)   DEFAULT NULL,
      `status`           ENUM('draft','dispatched','delivered','cancelled') NOT NULL DEFAULT 'draft',
      `notes`            TEXT          DEFAULT NULL,
      `received_by`      VARCHAR(120)  DEFAULT NULL,
      `created_by`       INT UNSIGNED  DEFAULT NULL,
      `created_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at`       DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_client` (`client_id`),
      KEY `idx_status` (`status`),
      KEY `idx_quote`  (`quotation_id`),
      KEY `idx_invoice`(`invoice_id`),
      CONSTRAINT `fk_dn_client` FOREIGN KEY (`client_id`) REFERENCES `clients`(`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS `delivery_note_items` (
      `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
      `delivery_note_id` INT UNSIGNED  NOT NULL,
      `description`      VARCHAR(255)  NOT NULL,
      `quantity`         DECIMAL(10,3) NOT NULL DEFAULT 1.000,
      `unit`             VARCHAR(40)   DEFAULT 'piece',
      `sort_order`       TINYINT       NOT NULL DEFAULT 0,
      PRIMARY KEY (`id`),
      KEY `idx_dn` (`delivery_note_id`),
      CONSTRAINT `fk_dnitem_dn` FOREIGN KEY (`delivery_note_id`) REFERENCES `delivery_notes`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $done = true;
}

function delivery_badge(string $status): string
{
    return match ($status) {
        'draft'      => 'bg-gray-100 text-gray-600',
        'dispatched' => 'bg-blue-100 text-blue-700',
        'delivered'  => 'bg-green-100 text-green-700',
        'cancelled'  => 'bg-red-100 text-red-600',
        default      => 'bg-gray-100 text-gray-600',
    };
}

/**
 * Read item rows posted from the delivery form (desc[], qty[], unit[]).
 */
function delivery_post_items(): array
{
    $items = [];
    $descs = $_POST['desc'] ?? [];
    $qtys  = $_POST['qty']  ?? [];
    $units = $_POST['unit'] ?? [];
    foreach ($descs as $i => $desc) {
        $desc = trim($desc);
        if ($desc === '') continue;
        $items[] = [
            'description' => $desc,
            'quantity'    => max(0, (float)($qtys[$i] ?? 1)),
            'unit'        => trim($units[$i] ?? 'piece') ?: 'piece',
        ];
    }
    return $items;
}
