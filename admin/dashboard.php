<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_login();
require_once dirname(__DIR__) . '/includes/db.php';

$active_page = 'dashboard';

// Stats
$total_inquiries = $new_inquiries = $replied = 0;
$recent = [];
if ($pdo) {
    $total_inquiries = $pdo->query("SELECT COUNT(*) FROM inquiries")->fetchColumn();
    $new_inquiries   = $pdo->query("SELECT COUNT(*) FROM inquiries WHERE status='new'")->fetchColumn();
    $replied         = $pdo->query("SELECT COUNT(*) FROM inquiries WHERE status='replied'")->fetchColumn();
    $recent          = $pdo->query("SELECT * FROM inquiries ORDER BY created_at DESC LIMIT 8")->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Dashboard — Ambozy Admin</title>
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<div class="admin-layout">
  <?php require_once __DIR__ . '/partials/sidebar.php'; ?>

  <div>
    <div class="topbar">
      <div class="topbar-title">Dashboard</div>
      <div class="topbar-actions">
        <a href="/admin/inquiries.php" class="btn btn-primary btn-sm">View Inquiries</a>
      </div>
    </div>

    <div class="main-content">

      <!-- Stat cards -->
      <div class="stats-grid">
        <div class="stat-card orange">
          <div class="stat-icon">📬</div>
          <div>
            <div class="stat-num"><?= $new_inquiries ?></div>
            <div class="stat-label">New Inquiries</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">📋</div>
          <div>
            <div class="stat-num"><?= $total_inquiries ?></div>
            <div class="stat-label">Total Inquiries</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">✅</div>
          <div>
            <div class="stat-num"><?= $replied ?></div>
            <div class="stat-label">Replied</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-icon">🎨</div>
          <div>
            <div class="stat-num">10</div>
            <div class="stat-label">Services</div>
          </div>
        </div>
      </div>

      <!-- Recent inquiries -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">Recent Inquiries</div>
          <a href="/admin/inquiries.php" class="btn btn-ghost btn-sm">View All</a>
        </div>
        <div class="table-wrap">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Name</th>
                <th>Service</th>
                <th>Date</th>
                <th>Status</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              <?php if ($recent): foreach ($recent as $row): ?>
              <tr>
                <td class="text-muted"><?= $row['id'] ?></td>
                <td>
                  <strong style="color:var(--wh)"><?= htmlspecialchars($row['name']) ?></strong>
                  <div class="text-sm text-muted"><?= htmlspecialchars($row['email']) ?></div>
                </td>
                <td><?= htmlspecialchars($row['service'] ?: '—') ?></td>
                <td class="text-muted text-sm"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                <td><span class="badge badge-<?= $row['status'] ?>"><?= $row['status'] ?></span></td>
                <td><a href="/admin/inquiries.php?view=<?= $row['id'] ?>" class="btn btn-ghost btn-sm btn-icon">→</a></td>
              </tr>
              <?php endforeach; else: ?>
              <tr><td colspan="6" class="text-muted" style="text-align:center;padding:32px">No inquiries yet.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- /main-content -->
  </div>
</div>
</body>
</html>
