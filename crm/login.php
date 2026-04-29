<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    header('Location: /crm/dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password && $pdo) {
        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admin_users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            login_user((int)$user['id'], $user['username']);
            $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
            header('Location: /crm/dashboard.php');
            exit;
        } else {
            sleep(1);
            $error = 'Invalid username or password.';
        }
    } else {
        $error = 'Please enter your username and password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-900">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sign In — Ambozy CRM</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="h-full flex items-center justify-center bg-slate-900 px-4">
  <div class="w-full max-w-sm">
    <!-- Logo -->
    <div class="text-center mb-8">
      <div class="inline-flex items-center justify-center w-14 h-14 bg-amber-500 rounded-2xl mb-4">
        <span class="text-white font-bold text-xl">AG</span>
      </div>
      <h1 class="text-white text-2xl font-bold">Ambozy CRM</h1>
      <p class="text-slate-400 text-sm mt-1">Sign in to your workspace</p>
    </div>

    <!-- Card -->
    <div class="bg-slate-800 rounded-2xl p-8 shadow-2xl border border-slate-700">
      <?php if ($error): ?>
        <div class="mb-5 bg-red-500/10 border border-red-500/30 text-red-400 text-sm px-4 py-3 rounded-lg">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-5">
        <div>
          <label class="block text-slate-300 text-sm font-medium mb-1.5">Username</label>
          <input type="text" name="username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                 autocomplete="username" autofocus required
                 class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-2.5 text-sm
                        placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
        </div>
        <div>
          <label class="block text-slate-300 text-sm font-medium mb-1.5">Password</label>
          <input type="password" name="password" autocomplete="current-password" required
                 class="w-full bg-slate-700 border border-slate-600 text-white rounded-lg px-4 py-2.5 text-sm
                        placeholder-slate-400 focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
        </div>
        <button type="submit"
                class="w-full bg-amber-500 hover:bg-amber-400 text-white font-semibold py-2.5 rounded-lg
                       text-sm transition-colors mt-2">
          Sign In
        </button>
      </form>
    </div>

    <p class="text-center text-slate-500 text-xs mt-6">
      &copy; <?= date('Y') ?> Ambozy Graphics Solutions Ltd
    </p>
  </div>
</body>
</html>
