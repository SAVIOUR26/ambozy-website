<?php
/**
 * TEMPORARY DIAGNOSTIC — delete after use
 * Visit: https://ambozygraphics.shop/crm_diag.php
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo '<style>body{font-family:monospace;padding:20px;background:#0f172a;color:#e2e8f0}
h2{color:#f59e0b;margin-top:20px}
.ok{color:#4ade80}.err{color:#f87171}.warn{color:#fbbf24}
table{border-collapse:collapse;width:100%}
td,th{padding:6px 12px;border:1px solid #334155;text-align:left}
th{background:#1e293b}
</style>';

echo '<h2>PHP Version</h2>';
$v = PHP_VERSION;
$ok = version_compare($v, '8.0', '>=');
echo '<p class="'.($ok?'ok':'err').'">PHP ' . $v . ($ok?' ✓ (8.0+ required)':' ✗ NEED PHP 8.0 OR HIGHER — change in cPanel → PHP Selector') . '</p>';

echo '<h2>Config File</h2>';
$cfg = __DIR__ . '/config.local.php';
if (file_exists($cfg)) {
    echo '<p class="ok">config.local.php found ✓</p>';
} else {
    echo '<p class="err">config.local.php NOT FOUND ✗ — run setup.php to create it</p>';
}

echo '<h2>Database Connection</h2>';
if (file_exists($cfg)) {
    try {
        require_once $cfg;
        if (isset($pdo) && $pdo instanceof PDO) {
            echo '<p class="ok">PDO connected ✓</p>';
            // Check tables
            echo '<h2>Tables</h2><table><tr><th>Table</th><th>Status</th><th>Rows</th></tr>';
            $required = ['clients','leads','orders','invoices','invoice_items','payments',
                         'quotations','quotation_items','catalog_items','doc_sequences',
                         'email_logs','activities','inquiries'];
            foreach ($required as $t) {
                try {
                    $n = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
                    echo "<tr><td>$t</td><td class='ok'>✓ exists</td><td>$n</td></tr>";
                } catch (Exception $e) {
                    echo "<tr><td>$t</td><td class='err'>✗ MISSING</td><td>—</td></tr>";
                }
            }
            echo '</table>';
        } else {
            echo '<p class="err">PDO is null — DB credentials wrong or DB not reachable</p>';
        }
    } catch (Throwable $e) {
        echo '<p class="err">Error loading config: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
} else {
    echo '<p class="warn">Skipped — config.local.php missing</p>';
}

echo '<h2>CRM Helpers</h2>';
$helpers = __DIR__ . '/includes/crm_helpers.php';
if (file_exists($helpers)) {
    try {
        require_once $helpers;
        echo '<p class="ok">crm_helpers.php loaded ✓</p>';
    } catch (Throwable $e) {
        echo '<p class="err">crm_helpers.php ERROR: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
} else {
    echo '<p class="err">crm_helpers.php NOT FOUND ✗</p>';
}

echo '<h2>PHP Error Log (last 30 lines)</h2><pre style="background:#1e293b;padding:12px;overflow:auto;font-size:12px">';
$logfile = ini_get('error_log');
if ($logfile && file_exists($logfile)) {
    $lines = file($logfile);
    echo htmlspecialchars(implode('', array_slice($lines, -30)));
} else {
    // try common cPanel locations
    $candidates = [
        dirname(__DIR__) . '/logs/error_log',
        dirname(__DIR__) . '/error_log',
        __DIR__ . '/error_log',
        '/var/log/apache2/error.log',
    ];
    $found = false;
    foreach ($candidates as $c) {
        if (file_exists($c) && is_readable($c)) {
            $lines = file($c);
            echo htmlspecialchars(implode('', array_slice($lines, -30)));
            $found = true; break;
        }
    }
    if (!$found) echo 'Could not locate error log. Check cPanel → Metrics → Errors';
}
echo '</pre>';

echo '<p style="color:#475569;margin-top:30px">⚠️ Delete crm_diag.php after diagnosing.</p>';
