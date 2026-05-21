<?php
$page_title = 'My Profile';
$active_nav = '';
require_once __DIR__ . '/../partials/header.php';

$user = null;
if ($pdo) {
    $s = $pdo->prepare("SELECT id, username, full_name, email, signature_path FROM admin_users WHERE id = ?");
    $s->execute([$_SESSION['admin_id']]);
    $user = $s->fetch();
}

$msg = $err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user && $pdo) {
    $action = $_POST['action'] ?? '';

    if ($action === 'signature' && !empty($_FILES['signature']['tmp_name'])) {
        $file = $_FILES['signature'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        $allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg'];
        if (!isset($allowed[$mime])) {
            $err = 'Only PNG or JPG images are accepted.';
        } elseif ($file['size'] > 2 * 1024 * 1024) {
            $err = 'File must be under 2 MB.';
        } else {
            $dir = __DIR__ . '/../public/uploads/signatures/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);

            if ($user['signature_path']) {
                $old = __DIR__ . '/../' . $user['signature_path'];
                if (file_exists($old)) unlink($old);
            }

            $name = 'sig_' . $user['id'] . '_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
            if (move_uploaded_file($file['tmp_name'], $dir . $name)) {
                $path = 'public/uploads/signatures/' . $name;
                $pdo->prepare("UPDATE admin_users SET signature_path = ? WHERE id = ?")->execute([$path, $user['id']]);
                $user['signature_path'] = $path;
                $msg = 'Signature saved successfully.';
            } else {
                $err = 'Upload failed — check server permissions.';
            }
        }
    }

    if ($action === 'remove_signature') {
        if ($user['signature_path']) {
            $old = __DIR__ . '/../' . $user['signature_path'];
            if (file_exists($old)) unlink($old);
        }
        $pdo->prepare("UPDATE admin_users SET signature_path = NULL WHERE id = ?")->execute([$user['id']]);
        $user['signature_path'] = null;
        $msg = 'Signature removed.';
    }

    if ($action === 'profile') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        if ($full_name) {
            $pdo->prepare("UPDATE admin_users SET full_name = ?, email = ? WHERE id = ?")->execute([$full_name, $email, $user['id']]);
            $user['full_name'] = $full_name;
            $user['email']     = $email;
            $msg = 'Profile updated.';
        } else {
            $err = 'Name cannot be empty.';
        }
    }

    if ($action === 'password') {
        $ph = $pdo->prepare("SELECT password_hash FROM admin_users WHERE id = ?");
        $ph->execute([$user['id']]);
        $row = $ph->fetch();

        $current = $_POST['current_password'] ?? '';
        $new      = $_POST['new_password']     ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $row['password_hash'])) {
            $err = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $err = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $err = 'New passwords do not match.';
        } else {
            $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ?")->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            $msg = 'Password changed successfully.';
        }
    }
}
?>

<div class="max-w-3xl mx-auto space-y-6">

  <div class="flex items-center gap-3 mb-2">
    <div class="w-10 h-10 rounded-full bg-amber-500/20 text-amber-400 flex items-center justify-center font-bold text-sm">
      <?= strtoupper(substr($user['username'] ?? 'A', 0, 2)) ?>
    </div>
    <div>
      <h1 class="text-lg font-bold text-gray-900"><?= htmlspecialchars($user['full_name'] ?: $user['username']) ?></h1>
      <p class="text-sm text-gray-400">@<?= htmlspecialchars($user['username']) ?></p>
    </div>
  </div>

  <?php if ($msg): ?>
    <div class="bg-green-50 border border-green-200 text-green-700 text-sm px-4 py-3 rounded-lg"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>
  <?php if ($err): ?>
    <div class="bg-red-50 border border-red-200 text-red-600 text-sm px-4 py-3 rounded-lg"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>

  <!-- ── Signature ────────────────────────────────────────────── -->
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
    <h2 class="text-base font-semibold text-gray-800 mb-1">Document Signature</h2>
    <p class="text-sm text-gray-400 mb-5">This signature will appear on Quotations and Invoices you generate. Upload a PNG with a transparent background for best results.</p>

    <?php if ($user['signature_path'] && file_exists(__DIR__ . '/../' . $user['signature_path'])): ?>
      <!-- Preview -->
      <div class="mb-5 p-4 bg-gray-50 border border-gray-200 rounded-lg inline-block">
        <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mb-2">Current Signature</p>
        <img src="/<?= htmlspecialchars($user['signature_path']) ?>" alt="Signature"
             class="h-20 max-w-xs object-contain">
        <div class="mt-3 border-t border-gray-200 pt-2 text-xs text-gray-500">
          <?= htmlspecialchars($user['full_name'] ?: $user['username']) ?> &nbsp;·&nbsp; Ambozy Graphics Solutions Ltd
        </div>
      </div>
      <div class="flex gap-3 flex-wrap">
        <form method="POST" enctype="multipart/form-data" class="flex items-center gap-2">
          <input type="hidden" name="action" value="signature">
          <label class="cursor-pointer bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold px-4 py-2 rounded-lg transition-colors">
            Replace Signature
            <input type="file" name="signature" accept="image/png,image/jpeg" class="hidden"
                   onchange="this.closest('form').submit()">
          </label>
        </form>
        <form method="POST" onsubmit="return confirm('Remove your signature?')">
          <input type="hidden" name="action" value="remove_signature">
          <button type="submit" class="text-sm text-red-500 hover:text-red-700 px-4 py-2 rounded-lg border border-red-200 hover:border-red-400 transition-colors">
            Remove
          </button>
        </form>
      </div>
    <?php else: ?>
      <!-- Upload -->
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="signature">
        <label class="flex flex-col items-center justify-center w-full h-36 border-2 border-dashed border-gray-200 rounded-xl cursor-pointer hover:border-amber-400 hover:bg-amber-50/40 transition-colors group">
          <svg class="w-8 h-8 text-gray-300 group-hover:text-amber-400 mb-2 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
          </svg>
          <p class="text-sm text-gray-400 group-hover:text-amber-600 font-medium">Click to upload signature</p>
          <p class="text-xs text-gray-300 mt-1">PNG (transparent) or JPG · Max 2 MB</p>
          <input type="file" name="signature" accept="image/png,image/jpeg" class="hidden"
                 onchange="this.closest('form').submit()">
        </label>
      </form>
    <?php endif; ?>
  </div>

  <!-- ── Profile info ─────────────────────────────────────────── -->
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
    <h2 class="text-base font-semibold text-gray-800 mb-4">Profile Information</h2>
    <form method="POST" class="space-y-4">
      <input type="hidden" name="action" value="profile">
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Full Name</label>
          <input type="text" name="full_name" required
                 value="<?= htmlspecialchars($user['full_name'] ?? '') ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Email</label>
          <input type="email" name="email"
                 value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
      </div>
      <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Username</label>
        <input type="text" value="<?= htmlspecialchars($user['username']) ?>" disabled
               class="w-full border border-gray-100 rounded-lg px-3 py-2.5 text-sm bg-gray-50 text-gray-400 cursor-not-allowed">
      </div>
      <div class="flex justify-end">
        <button type="submit" class="bg-amber-500 hover:bg-amber-400 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors">
          Save Changes
        </button>
      </div>
    </form>
  </div>

  <!-- ── Change password ──────────────────────────────────────── -->
  <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-6">
    <h2 class="text-base font-semibold text-gray-800 mb-4">Change Password</h2>
    <form method="POST" class="space-y-4">
      <input type="hidden" name="action" value="password">
      <div>
        <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Current Password</label>
        <input type="password" name="current_password" required autocomplete="current-password"
               class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
      </div>
      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">New Password</label>
          <input type="password" name="new_password" required autocomplete="new-password" minlength="8"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
        <div>
          <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Confirm New Password</label>
          <input type="password" name="confirm_password" required autocomplete="new-password" minlength="8"
                 class="w-full border border-gray-200 rounded-lg px-3 py-2.5 text-sm focus:outline-none focus:border-amber-400 focus:ring-1 focus:ring-amber-400">
        </div>
      </div>
      <div class="flex justify-end">
        <button type="submit" class="bg-slate-700 hover:bg-slate-600 text-white text-sm font-semibold px-5 py-2.5 rounded-lg transition-colors">
          Change Password
        </button>
      </div>
    </form>
  </div>

</div>
<?php require_once __DIR__ . '/../partials/footer.php'; ?>
