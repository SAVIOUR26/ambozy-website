<?php
/**
 * TEMPORARY DIAGNOSTIC v2 — delete after use
 * Visit: https://ambozygraphics.com/crm_diag.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<style>body{font-family:monospace;padding:20px;background:#0f172a;color:#e2e8f0}
h2{color:#f59e0b;margin-top:20px}
.ok{color:#4ade80}.err{color:#f87171}.warn{color:#fbbf24}
table{border-collapse:collapse;width:100%}
td,th{padding:6px 12px;border:1px solid #334155;text-align:left}
th{background:#1e293b}pre{background:#1e293b;padding:12px;overflow:auto;font-size:12px;white-space:pre-wrap}
</style>';

// ── Load config ──────────────────────────────────────────────────────
echo '<h2>1. Config + DB</h2>';
try {
    require_once __DIR__ . '/config.php';
    echo isset($pdo) && $pdo ? '<p class="ok">PDO connected ✓</p>' : '<p class="err">PDO null ✗</p>';
} catch (Throwable $e) {
    echo '<p class="err">config.php error: '.htmlspecialchars($e->getMessage()).'</p>';
}

// ── admin_users table ────────────────────────────────────────────────
echo '<h2>2. Admin Users Table</h2>';
if (isset($pdo) && $pdo) {
    try {
        $users = $pdo->query("SELECT id, username, full_name FROM admin_users")->fetchAll();
        if ($users) {
            echo '<table><tr><th>ID</th><th>Username</th><th>Full Name</th></tr>';
            foreach ($users as $u) {
                echo '<tr><td>'.$u['id'].'</td><td>'.htmlspecialchars($u['username']).'</td><td>'.htmlspecialchars($u['full_name']??'').'</td></tr>';
            }
            echo '</table>';
        } else {
            echo '<p class="warn">⚠️ admin_users table is EMPTY — you cannot log in. Run the INSERT below.</p>';
            echo '<pre>INSERT INTO admin_users (username, password_hash, full_name, email)
VALUES (\'admin\', \''.password_hash('Admin@2026', PASSWORD_DEFAULT).'\', \'Ambozy Admin\', \'ambozygraphics@gmail.com\');</pre>';
            echo '<p class="warn">Password will be: <strong>Admin@2026</strong> — change it after first login.</p>';
        }
    } catch (Throwable $e) {
        echo '<p class="err">admin_users error: '.htmlspecialchars($e->getMessage()).'</p>';
        echo '<p class="warn">Table may not exist. Run the SQL below to create and seed it:</p>';
        echo '<pre>CREATE TABLE IF NOT EXISTS `admin_users` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `username`      VARCHAR(60)   NOT NULL UNIQUE,
  `password_hash` VARCHAR(255)  NOT NULL,
  `full_name`     VARCHAR(120)  DEFAULT NULL,
  `email`         VARCHAR(180)  DEFAULT NULL,
  `last_login`    DATETIME      DEFAULT NULL,
  `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO admin_users (username, password_hash, full_name, email)
VALUES (\'admin\', \''.password_hash('Admin@2026', PASSWORD_DEFAULT).'\', \'Ambozy Admin\', \'ambozygraphics@gmail.com\');</pre>';
    }
}

// ── Load helpers ─────────────────────────────────────────────────────
echo '<h2>3. CRM Helpers</h2>';
try {
    require_once __DIR__ . '/includes/crm_helpers.php';
    echo '<p class="ok">crm_helpers.php ✓</p>';
} catch (Throwable $e) {
    echo '<p class="err">crm_helpers error: '.htmlspecialchars($e->getMessage()).' on line '.$e->getLine().'</p>';
}

// ── Test header.php in isolation ─────────────────────────────────────
echo '<h2>4. CRM Header / Auth Test</h2>';
// Simulate being logged in so require_login() doesn't redirect
session_start();
$_SESSION['admin_id']   = 1;
$_SESSION['admin_user'] = 'diag_test';
$_SESSION['login_time'] = time();

ob_start();
try {
    $page_title  = 'Diag Test';
    $active_nav  = 'dashboard';
    include __DIR__ . '/crm/partials/header.php';
    $hdr = ob_get_clean();
    echo '<p class="ok">header.php loaded without fatal errors ✓</p>';
} catch (Throwable $e) {
    $hdr = ob_get_clean();
    echo '<p class="err">header.php FATAL: '.htmlspecialchars($e->getMessage()).'</p>';
    echo '<p class="err">File: '.htmlspecialchars($e->getFile()).' line '.$e->getLine().'</p>';
    echo '<pre>'.htmlspecialchars(substr($hdr,0,500)).'</pre>';
}

// ── Check deployed file versions ─────────────────────────────────────
echo '<h2>5. Deployed File Check</h2>';
$files = [
    'crm/dashboard.php'         => 'try {',
    'crm/partials/header.php'   => 'try { $_new_leads',
    'crm/leads/index.php'       => 'try {',
    'crm/clients/index.php'     => 'try {',
    'includes/crm_helpers.php'  => '?string $related_type',
];
echo '<table><tr><th>File</th><th>Has latest fix?</th></tr>';
foreach ($files as $file => $needle) {
    $path = __DIR__ . '/' . $file;
    $content = file_exists($path) ? file_get_contents($path) : '';
    $has = str_contains($content, $needle);
    echo '<tr><td>'.$file.'</td><td class="'.($has?'ok':'err').'">'.($has?'✓ yes':'✗ NO — old version deployed').'</td></tr>';
}
echo '</table>';

echo '<p style="color:#475569;margin-top:30px">⚠️ Delete crm_diag.php after diagnosing.</p>';
