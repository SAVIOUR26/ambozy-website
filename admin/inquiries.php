<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_login();
require_once dirname(__DIR__) . '/includes/db.php';

$active_page = 'inquiries';
$message = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $pdo) {
    $id = (int)($_POST['id'] ?? 0);
    if ($_POST['action'] === 'update_status' && $id) {
        $status = in_array($_POST['status'], ['new','read','replied','closed'])
                  ? $_POST['status'] : 'new';
        $pdo->prepare("UPDATE inquiries SET status=? WHERE id=?")->execute([$status, $id]);
        $message = 'Status updated.';
    }
    if ($_POST['action'] === 'save_notes' && $id) {
        $notes = htmlspecialchars(trim($_POST['notes'] ?? ''), ENT_QUOTES, 'UTF-8');
        $pdo->prepare("UPDATE inquiries SET notes=? WHERE id=?")->execute([$notes, $id]);
        $message = 'Notes saved.';
    }
}

// View single inquiry
$view_id = (int)($_GET['view'] ?? 0);
$viewing = null;
if ($view_id && $pdo) {
    $viewing = $pdo->prepare("SELECT * FROM inquiries WHERE id=?");
    $viewing->execute([$view_id]);
    $viewing = $viewing->fetch();
    // Auto-mark as read
    if ($viewing && $viewing['status'] === 'new') {
        $pdo->prepare("UPDATE inquiries SET status='read' WHERE id=?")->execute([$view_id]);
        $viewing['status'] = 'read';
    }
}

// Filters
$status_filter = $_GET['status'] ?? '';
$where = $status_filter ? "WHERE status = " . $pdo?->quote($status_filter) : '';
$inquiries = $pdo ? $pdo->query("SELECT * FROM inquiries $where ORDER BY created_at DESC")->fetchAll() : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Inquiries — Ambozy Admin</title>
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<div class="admin-layout">
  <?php require_once __DIR__ . '/partials/sidebar.php'; ?>

  <div>
    <div class="topbar">
      <div class="topbar-title">
        <?= $viewing ? 'Inquiry #' . $viewing['id'] : 'Inquiries' ?>
      </div>
      <div class="topbar-actions">
        <?php if ($viewing): ?>
        <a href="/admin/inquiries.php" class="btn btn-ghost btn-sm">← All Inquiries</a>
        <?php else: ?>
        <a href="?status=" class="btn btn-ghost btn-sm <?= !$status_filter?'btn-primary':'' ?>">All</a>
        <a href="?status=new" class="btn btn-ghost btn-sm <?= $status_filter==='new'?'btn-primary':'' ?>">New</a>
        <a href="?status=replied" class="btn btn-ghost btn-sm <?= $status_filter==='replied'?'btn-primary':'' ?>">Replied</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="main-content">
      <?php if ($message): ?>
      <div class="alert alert-success"><?= $message ?></div>
      <?php endif; ?>

      <?php if ($viewing): ?>
      <!-- ── Single Inquiry View ── -->
      <div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start">
        <div class="card">
          <div class="card-header">
            <div>
              <div class="card-title"><?= htmlspecialchars($viewing['name']) ?></div>
              <div class="text-muted text-sm"><?= date('d M Y, H:i', strtotime($viewing['created_at'])) ?></div>
            </div>
            <span class="badge badge-<?= $viewing['status'] ?>"><?= $viewing['status'] ?></span>
          </div>
          <div class="card-body">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px">
              <?php
              $fields = [
                'Email'   => $viewing['email'],
                'Phone'   => $viewing['phone'] ?: '—',
                'Company' => $viewing['company'] ?: '—',
                'Service' => $viewing['service'] ?: '—',
                'Budget'  => $viewing['budget'] ?: '—',
              ];
              foreach ($fields as $label => $val): ?>
              <div>
                <div class="text-muted text-sm" style="margin-bottom:2px"><?= $label ?></div>
                <div style="color:var(--wh);font-weight:500"><?= htmlspecialchars($val) ?></div>
              </div>
              <?php endforeach; ?>
            </div>
            <div style="background:var(--s3);border-radius:var(--r);padding:16px;border-left:3px solid var(--o)">
              <div class="text-muted text-sm" style="margin-bottom:8px">Message</div>
              <div style="white-space:pre-wrap;line-height:1.7"><?= htmlspecialchars($viewing['message']) ?></div>
            </div>

            <!-- Reply via email link -->
            <div class="mt-24">
              <a href="mailto:<?= htmlspecialchars($viewing['email']) ?>?subject=Re: Your Ambozy Graphics Inquiry&body=Dear <?= urlencode($viewing['name']) ?>,"
                 class="btn btn-primary">✉ Reply via Email</a>
              <a href="https://wa.me/<?= preg_replace('/[^0-9]/','',$viewing['phone']) ?>?text=Hello+<?= urlencode($viewing['name']) ?>%2C+thank+you+for+your+inquiry."
                 target="_blank" class="btn btn-ghost" style="margin-left:8px">WhatsApp Reply</a>
            </div>
          </div>
        </div>

        <!-- Side panel -->
        <div style="display:flex;flex-direction:column;gap:16px">
          <div class="card">
            <div class="card-header"><div class="card-title">Update Status</div></div>
            <div class="card-body">
              <form method="POST">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" value="<?= $viewing['id'] ?>">
                <select name="status" class="form-control" style="margin-bottom:12px">
                  <?php foreach (['new','read','replied','closed'] as $s): ?>
                  <option value="<?= $s ?>" <?= $viewing['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn btn-primary" style="width:100%;justify-content:center">Update</button>
              </form>
            </div>
          </div>
          <div class="card">
            <div class="card-header"><div class="card-title">Notes</div></div>
            <div class="card-body">
              <form method="POST">
                <input type="hidden" name="action" value="save_notes">
                <input type="hidden" name="id" value="<?= $viewing['id'] ?>">
                <textarea name="notes" class="form-control" style="min-height:80px;margin-bottom:10px"><?= htmlspecialchars($viewing['notes'] ?? '') ?></textarea>
                <button class="btn btn-ghost" style="width:100%;justify-content:center">Save Notes</button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <?php else: ?>
      <!-- ── Inquiries List ── -->
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>#</th><th>Name</th><th>Service</th><th>Budget</th><th>Date</th><th>Status</th><th></th></tr>
            </thead>
            <tbody>
              <?php if ($inquiries): foreach ($inquiries as $row): ?>
              <tr>
                <td class="text-muted"><?= $row['id'] ?></td>
                <td>
                  <strong style="color:var(--wh)"><?= htmlspecialchars($row['name']) ?></strong>
                  <div class="text-sm text-muted"><?= htmlspecialchars($row['email']) ?></div>
                </td>
                <td><?= htmlspecialchars($row['service'] ?: '—') ?></td>
                <td class="text-muted text-sm"><?= htmlspecialchars($row['budget'] ?: '—') ?></td>
                <td class="text-muted text-sm"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                <td><span class="badge badge-<?= $row['status'] ?>"><?= $row['status'] ?></span></td>
                <td><a href="?view=<?= $row['id'] ?>" class="btn btn-ghost btn-sm">View →</a></td>
              </tr>
              <?php endforeach; else: ?>
              <tr><td colspan="7" class="text-muted" style="text-align:center;padding:40px">No inquiries found.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>
</body>
</html>
