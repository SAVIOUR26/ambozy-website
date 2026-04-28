<?php
require_once dirname(__DIR__) . '/includes/auth.php';
if (is_logged_in()) { header('Location: /admin/dashboard.php'); exit; }

require_once dirname(__DIR__) . '/includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password && isset($pdo)) {
        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admin_users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            login_user($user['id'], $user['username']);
            // Update last login
            $pdo->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
            header('Location: /admin/dashboard.php');
            exit;
        }
    }
    $error = 'Invalid username or password.';
    sleep(1); // rate-limit brute force
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login — Ambozy Graphics</title>
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo">AMB<span>◆</span>ZY</div>
    <div class="login-sub">Admin Panel</div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <div class="form-group">
        <label class="form-label" for="username">Username</label>
        <input class="form-control" type="text" id="username" name="username"
               autocomplete="username" required autofocus
               value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label class="form-label" for="password">Password</label>
        <input class="form-control" type="password" id="password" name="password"
               autocomplete="current-password" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px">
        Sign In →
      </button>
    </form>

    <p class="text-muted text-sm mt-16" style="text-align:center">
      <a href="/" style="color:var(--o)">← Back to website</a>
    </p>
  </div>
</div>
</body>
</html>
