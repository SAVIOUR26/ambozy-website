<?php
/**
 * AMBOZY GRAPHICS SOLUTIONS LTD
 * config.php — Site configuration & DB connection
 * 
 * ⚠️  Copy this to config.local.php and fill real credentials.
 *     config.local.php is git-ignored and loaded first if present.
 */

// ── Load local overrides (production credentials, never committed) ──
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
    return;
}

// ── Site constants ──────────────────────────────────────────────────
define('SITE_NAME',  'Ambozy Graphics Solutions Ltd');
define('SITE_URL',   'https://ambozygraphics.com');
define('SITE_EMAIL', 'ambozygraphics@gmail.com');
define('SITE_PHONE', '+256 782 187 799');
define('WHATSAPP_NO','256782187799');   // digits only, no +

// ── Database ────────────────────────────────────────────────────────
define('DB_HOST',    'localhost');
define('DB_NAME',    'ambozy_db');      // create this in phpMyAdmin
define('DB_USER',    'ambozy_user');    // DB user
define('DB_PASS',    'CHANGE_ME');      // DB password
define('DB_CHARSET', 'utf8mb4');

// ── Admin credentials (change before going live!) ───────────────────
define('ADMIN_USER', 'admin');
define('ADMIN_HASH', password_hash('CHANGE_ME_PASSWORD', PASSWORD_DEFAULT));

// ── Mail ────────────────────────────────────────────────────────────
define('MAIL_FROM',  'noreply@ambozygraphics.com');
define('MAIL_TO',    'ambozygraphics@gmail.com');

// ── PDO connection ──────────────────────────────────────────────────
try {
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    // Graceful fail — site still works, just no DB persistence
    $pdo = null;
    error_log('Ambozy DB connection failed: ' . $e->getMessage());
}
