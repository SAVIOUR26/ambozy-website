<?php
/**
 * ONE-TIME SETUP HELPER — Ambozy Graphics Solutions Ltd
 *
 * PURPOSE: Generate your bcrypt password hash and produce a
 *          ready-to-paste config.local.php for the server.
 *
 * USAGE:
 *   1. Visit https://ambozygraphics.com/setup.php
 *   2. Fill in your details and click Generate
 *   3. Copy the generated config.local.php content
 *   4. Create the file in public_html/ via cPanel File Manager
 *   5. DELETE THIS FILE immediately — it must not stay on the server
 *
 * ⚠️  This file is excluded from future FTP deploys (.github/workflows/deploy.yml)
 *     but you should still delete it manually after use.
 */

// Block access once config.local.php already exists and DB is working
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
    if (isset($pdo) && $pdo instanceof PDO) {
        http_response_code(403);
        die('<h2 style="font-family:sans-serif;color:#dc2626;padding:2rem">
             ⛔ Setup is complete. Delete this file from the server immediately.<br>
             <small style="color:#6b7280">config.local.php already exists and database is connected.</small>
             </h2>');
    }
}

$hash    = '';
$config  = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password  = $_POST['password']  ?? '';
    $password2 = $_POST['password2'] ?? '';
    $db_name   = trim($_POST['db_name']   ?? '');
    $db_user   = trim($_POST['db_user']   ?? '');
    $db_pass   = trim($_POST['db_pass']   ?? '');
    $db_host   = trim($_POST['db_host']   ?? 'localhost');
    $admin_user= trim($_POST['admin_user']?? 'admin');

    if (!$password)              $error = 'Password is required.';
    elseif ($password !== $password2) $error = 'Passwords do not match.';
    elseif (strlen($password) < 8)   $error = 'Password must be at least 8 characters.';
    elseif (!$db_name || !$db_user)  $error = 'Database name and user are required.';

    if (!$error) {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        // Test DB connection with provided credentials
        $db_ok = false; $db_msg = '';
        try {
            $test = new PDO(
                "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
                $db_user, $db_pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $db_ok = true;
        } catch (PDOException $e) {
            $db_msg = $e->getMessage();
        }

        $config = <<<PHP
<?php
define('SITE_NAME',  'Ambozy Graphics Solutions Ltd');
define('SITE_URL',   'https://ambozygraphics.com');
define('SITE_EMAIL', 'info@ambozygraphics.com');
define('SITE_PHONE', '+256 782 187 799');
define('WHATSAPP_NO','256782187799');

define('DB_HOST',    '$db_host');
define('DB_NAME',    '$db_name');
define('DB_USER',    '$db_user');
define('DB_PASS',    '$db_pass');
define('DB_CHARSET', 'utf8mb4');

define('ADMIN_USER', '$admin_user');
define('ADMIN_HASH', '$hash');

define('MAIL_FROM',  'noreply@ambozygraphics.com');
define('MAIL_TO',    'info@ambozygraphics.com');

try {
    \$pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET,
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
         PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
         PDO::ATTR_EMULATE_PREPARES   => false]
    );
} catch (PDOException \$e) {
    \$pdo = null;
    error_log('Ambozy DB: ' . \$e->getMessage());
}
PHP;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Ambozy — Server Setup</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-lg">

  <!-- Header -->
  <div class="flex items-center gap-3 mb-6">
    <div class="w-10 h-10 bg-amber-500 rounded-xl flex items-center justify-center shrink-0">
      <span class="text-white font-bold text-sm">AG</span>
    </div>
    <div>
      <p class="text-white font-semibold">Ambozy Graphics Solutions</p>
      <p class="text-amber-500 text-xs font-medium">One-Time Server Setup</p>
    </div>
  </div>

  <div class="bg-amber-500/10 border border-amber-500/30 rounded-xl p-4 mb-5 text-amber-300 text-sm">
    ⚠️ <strong>Delete this file immediately after use.</strong>
    Visit <code class="bg-amber-500/20 px-1 rounded">/crm/</code> to confirm login works, then delete
    <code class="bg-amber-500/20 px-1 rounded">setup.php</code> from cPanel File Manager.
  </div>

  <?php if ($config): ?>
  <!-- Success: show generated config -->
  <div class="bg-white rounded-xl overflow-hidden shadow-xl mb-5">
    <div class="bg-green-500 px-5 py-3 flex items-center gap-2">
      <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
      <p class="text-white font-semibold">Config generated successfully</p>
    </div>
    <div class="p-5 space-y-4">

      <?php if (isset($db_ok)): ?>
      <div class="flex items-center gap-2 text-sm <?= $db_ok ? 'text-green-700' : 'text-red-600' ?>">
        <?= $db_ok
          ? '<svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Database connection <strong>successful</strong>'
          : '<svg class="w-4 h-4 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg> Database connection failed — check credentials'
        ?>
        <?php if (!$db_ok && isset($db_msg)): ?><br><code class="text-xs mt-1 block text-red-500"><?= htmlspecialchars($db_msg) ?></code><?php endif; ?>
      </div>
      <?php endif; ?>

      <div>
        <p class="text-sm font-semibold text-gray-700 mb-2">
          Steps:
        </p>
        <ol class="text-sm text-gray-600 space-y-1 list-decimal list-inside">
          <li>Copy the entire config below</li>
          <li>Open <strong>cPanel → File Manager → public_html/</strong></li>
          <li>Create a new file named <code class="bg-gray-100 px-1 rounded">config.local.php</code></li>
          <li>Paste and save</li>
          <li>Visit <a href="/crm/" class="text-amber-600 underline">/crm/</a> and log in</li>
          <li>Delete this file (<code class="bg-gray-100 px-1 rounded">setup.php</code>) immediately</li>
        </ol>
      </div>

      <div>
        <div class="flex items-center justify-between mb-1">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">config.local.php</p>
          <button onclick="copyConfig()" class="text-xs text-amber-600 font-medium hover:text-amber-700">
            Copy to clipboard
          </button>
        </div>
        <textarea id="configOutput" rows="20" readonly
                  class="w-full font-mono text-xs bg-slate-900 text-green-400 border border-slate-700 rounded-lg p-3 focus:outline-none resize-none"><?= htmlspecialchars($config) ?></textarea>
      </div>

    </div>
  </div>
  <?php endif; ?>

  <!-- Form -->
  <?php if (!$config): ?>
  <div class="bg-white rounded-xl shadow-xl overflow-hidden">
    <div class="px-6 py-5 border-b border-gray-100">
      <h2 class="font-semibold text-gray-800">Generate config.local.php</h2>
      <p class="text-xs text-gray-400 mt-0.5">Fill in your server details — nothing is stored or transmitted externally.</p>
    </div>
    <form method="POST" class="px-6 py-5 space-y-4">

      <?php if ($error): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Database (from cPanel → MySQL Databases)</p>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">DB Host</label>
          <input type="text" name="db_host" value="localhost"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">DB Name <span class="text-red-500">*</span></label>
          <input type="text" name="db_name" required placeholder="e.g. ambozy_db"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">DB User <span class="text-red-500">*</span></label>
          <input type="text" name="db_user" required placeholder="e.g. ambozy_user"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
        </div>
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">DB Password</label>
          <input type="text" name="db_pass" placeholder="DB user password"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
        </div>
      </div>

      <div class="border-t border-gray-100 pt-4">
        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">CRM Admin Login</p>
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2 md:col-span-1">
            <label class="block text-sm font-medium text-gray-700 mb-1">Admin Username</label>
            <input type="text" name="admin_user" value="admin"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4 mt-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
            <input type="password" name="password" required placeholder="Min 8 characters"
                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password <span class="text-red-500">*</span></label>
            <input type="password" name="password2" required
                   class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400">
          </div>
        </div>
      </div>

      <div class="pt-2">
        <button type="submit"
                class="w-full bg-amber-500 hover:bg-amber-400 text-white font-semibold py-2.5 rounded-lg transition-colors">
          Generate config.local.php
        </button>
      </div>
    </form>
  </div>
  <?php endif; ?>

</div>

<script>
function copyConfig() {
    const ta = document.getElementById('configOutput');
    ta.select();
    navigator.clipboard.writeText(ta.value).then(() => {
        const btn = event.target;
        btn.textContent = 'Copied!';
        btn.classList.add('text-green-600');
        setTimeout(() => { btn.textContent = 'Copy to clipboard'; btn.classList.remove('text-green-600'); }, 2000);
    });
}
</script>

</body>
</html>
