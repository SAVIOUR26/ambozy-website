<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_login();
require_once dirname(__DIR__) . '/includes/db.php';

$active_page = 'settings';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $keys = ['site_name','site_phone','site_email','site_address','whatsapp','facebook','instagram','twitter'];
    $stmt = $pdo->prepare("INSERT INTO settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`)");
    foreach ($keys as $k) {
        $stmt->execute([$k, htmlspecialchars(trim($_POST[$k] ?? ''), ENT_QUOTES, 'UTF-8')]);
    }
    // Password change
    if (!empty($_POST['new_password']) && strlen($_POST['new_password']) >= 8) {
        $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE admin_users SET password_hash=? WHERE username=?")->execute([$hash, $_SESSION['admin_user']]);
        $message .= ' Password updated.';
    }
    $message = 'Settings saved.' . $message;
}

$settings = [];
if ($pdo) {
    foreach ($pdo->query("SELECT `key`,`value` FROM settings")->fetchAll() as $row) {
        $settings[$row['key']] = $row['value'];
    }
}
function sval(array $s, string $k): string { return htmlspecialchars($s[$k] ?? ''); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Settings — Ambozy Admin</title>
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<div class="admin-layout">
  <?php require_once __DIR__ . '/partials/sidebar.php'; ?>
  <div>
    <div class="topbar"><div class="topbar-title">Settings</div></div>
    <div class="main-content">
      <?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>

      <form method="POST">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">

          <div class="card">
            <div class="card-header"><div class="card-title">Site Info</div></div>
            <div class="card-body">
              <div class="form-group">
                <label class="form-label">Site Name</label>
                <input class="form-control" name="site_name" value="<?= sval($settings,'site_name') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Phone</label>
                <input class="form-control" name="site_phone" value="<?= sval($settings,'site_phone') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Email</label>
                <input class="form-control" type="email" name="site_email" value="<?= sval($settings,'site_email') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">Address</label>
                <input class="form-control" name="site_address" value="<?= sval($settings,'site_address') ?>">
              </div>
              <div class="form-group">
                <label class="form-label">WhatsApp Number (digits only, no +)</label>
                <input class="form-control" name="whatsapp" value="<?= sval($settings,'whatsapp') ?>" placeholder="256782187799">
                <div class="form-hint">Used for the floating chat button</div>
              </div>
            </div>
          </div>

          <div style="display:flex;flex-direction:column;gap:20px">
            <div class="card">
              <div class="card-header"><div class="card-title">Social Links</div></div>
              <div class="card-body">
                <?php foreach (['facebook','instagram','twitter'] as $net): ?>
                <div class="form-group">
                  <label class="form-label"><?= ucfirst($net) ?> URL</label>
                  <input class="form-control" name="<?= $net ?>" value="<?= sval($settings,$net) ?>" placeholder="https://<?= $net ?>.com/ambozy">
                </div>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="card">
              <div class="card-header"><div class="card-title">Change Password</div></div>
              <div class="card-body">
                <div class="form-group">
                  <label class="form-label">New Password (min. 8 chars)</label>
                  <input class="form-control" type="password" name="new_password" placeholder="Leave blank to keep current">
                </div>
                <div class="form-hint">Leave blank to keep the current password.</div>
              </div>
            </div>
          </div>

        </div>
        <div style="margin-top:20px">
          <button class="btn btn-primary">Save All Settings</button>
        </div>
      </form>

    </div>
  </div>
</div>
</body>
</html>
