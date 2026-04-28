<?php
// includes/admin-sidebar.php
// Usage: require_once, set $active_page before including
$active_page = $active_page ?? '';
?>
<aside class="sidebar">
  <div class="sidebar-logo">
    <a href="/admin/dashboard.php">AMB<span>◆</span>ZY</a>
    <div class="sub">Admin Panel</div>
  </div>

  <nav class="sidebar-nav">
    <div class="nav-section-label">Main</div>
    <div class="nav-item">
      <a href="/admin/dashboard.php" class="<?= $active_page==='dashboard'?'active':'' ?>">
        <span class="icon">📊</span> Dashboard
      </a>
    </div>
    <div class="nav-item">
      <a href="/admin/inquiries.php" class="<?= $active_page==='inquiries'?'active':'' ?>">
        <span class="icon">💬</span> Inquiries
        <?php
        // Show unread count badge
        if (isset($pdo)) {
            $c = $pdo->query("SELECT COUNT(*) FROM inquiries WHERE status='new'")->fetchColumn();
            if ($c > 0) echo "<span style='margin-left:auto;background:var(--o);color:#fff;font-size:.65rem;padding:2px 7px;border-radius:10px;font-family:var(--fm)'>$c</span>";
        }
        ?>
      </a>
    </div>

    <div class="nav-section-label">Content</div>
    <div class="nav-item">
      <a href="/admin/services.php" class="<?= $active_page==='services'?'active':'' ?>">
        <span class="icon">🎨</span> Services
      </a>
    </div>
    <div class="nav-item">
      <a href="/admin/gallery.php" class="<?= $active_page==='gallery'?'active':'' ?>">
        <span class="icon">🖼️</span> Gallery
      </a>
    </div>

    <div class="nav-section-label">System</div>
    <div class="nav-item">
      <a href="/admin/settings.php" class="<?= $active_page==='settings'?'active':'' ?>">
        <span class="icon">⚙️</span> Settings
      </a>
    </div>
    <div class="nav-item">
      <a href="/" target="_blank">
        <span class="icon">🌐</span> View Website
      </a>
    </div>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <strong><?= htmlspecialchars($_SESSION['admin_user'] ?? 'Admin') ?></strong>
      <a href="/admin/logout.php">Sign out</a>
    </div>
  </div>
</aside>
