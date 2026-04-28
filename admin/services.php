<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_login();
require_once dirname(__DIR__) . '/includes/db.php';

$active_page = 'services';
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'save') {
        $data = [
            ':icon'  => htmlspecialchars(trim($_POST['icon']  ?? '🎨')),
            ':title' => htmlspecialchars(trim($_POST['title'] ?? '')),
            ':items' => htmlspecialchars(trim($_POST['items'] ?? '')),
            ':desc'  => htmlspecialchars(trim($_POST['description'] ?? '')),
            ':sort'  => (int)($_POST['sort_order'] ?? 0),
            ':active'=> isset($_POST['is_active']) ? 1 : 0,
        ];
        if ($id) {
            $data[':id'] = $id;
            $pdo->prepare("UPDATE services SET icon=:icon,title=:title,items=:items,description=:desc,sort_order=:sort,is_active=:active WHERE id=:id")->execute($data);
            $message = 'Service updated.';
        } else {
            $pdo->prepare("INSERT INTO services (icon,title,items,description,sort_order,is_active) VALUES (:icon,:title,:items,:desc,:sort,:active)")->execute($data);
            $message = 'Service added.';
        }
    }
    if ($action === 'delete' && $id) {
        $pdo->prepare("DELETE FROM services WHERE id=?")->execute([$id]);
        $message = 'Service deleted.';
    }
    if ($action === 'toggle' && $id) {
        $pdo->prepare("UPDATE services SET is_active = NOT is_active WHERE id=?")->execute([$id]);
    }
}

$edit_id = (int)($_GET['edit'] ?? 0);
$edit_row = null;
if ($edit_id && $pdo) {
    $edit_row = $pdo->prepare("SELECT * FROM services WHERE id=?");
    $edit_row->execute([$edit_id]);
    $edit_row = $edit_row->fetch();
}

$services = $pdo ? $pdo->query("SELECT * FROM services ORDER BY sort_order")->fetchAll() : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Services — Ambozy Admin</title>
  <link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body>
<div class="admin-layout">
  <?php require_once __DIR__ . '/partials/sidebar.php'; ?>
  <div>
    <div class="topbar">
      <div class="topbar-title">Services</div>
      <a href="?edit=new" class="btn btn-primary btn-sm">+ Add Service</a>
    </div>
    <div class="main-content">
      <?php if ($message): ?><div class="alert alert-success"><?= $message ?></div><?php endif; ?>

      <?php if ($edit_id || isset($_GET['edit'])): ?>
      <!-- ── Add / Edit Form ── -->
      <div class="card" style="max-width:600px">
        <div class="card-header">
          <div class="card-title"><?= $edit_row ? 'Edit Service' : 'Add Service' ?></div>
          <a href="/admin/services.php" class="btn btn-ghost btn-sm">Cancel</a>
        </div>
        <div class="card-body">
          <form method="POST">
            <input type="hidden" name="action" value="save">
            <?php if ($edit_row): ?><input type="hidden" name="id" value="<?= $edit_row['id'] ?>"><?php endif; ?>
            <div class="form-row">
              <div class="form-group">
                <label class="form-label">Icon (emoji)</label>
                <input class="form-control" name="icon" value="<?= htmlspecialchars($edit_row['icon'] ?? '🎨') ?>" placeholder="🎨">
              </div>
              <div class="form-group">
                <label class="form-label">Sort Order</label>
                <input class="form-control" type="number" name="sort_order" value="<?= $edit_row['sort_order'] ?? 0 ?>">
              </div>
            </div>
            <div class="form-group">
              <label class="form-label">Title *</label>
              <input class="form-control" name="title" required value="<?= htmlspecialchars($edit_row['title'] ?? '') ?>" placeholder="Branded Merchandise">
            </div>
            <div class="form-group">
              <label class="form-label">Items (comma-separated)</label>
              <input class="form-control" name="items" value="<?= htmlspecialchars($edit_row['items'] ?? '') ?>" placeholder="T-shirts, Caps, Bags">
            </div>
            <div class="form-group">
              <label class="form-label">Description</label>
              <textarea class="form-control" name="description"><?= htmlspecialchars($edit_row['description'] ?? '') ?></textarea>
            </div>
            <div class="form-group flex items-center gap-8">
              <input type="checkbox" id="is_active" name="is_active" <?= ($edit_row['is_active'] ?? 1) ? 'checked' : '' ?>>
              <label for="is_active" class="form-label mb-0">Active (show on website)</label>
            </div>
            <button class="btn btn-primary">Save Service</button>
          </form>
        </div>
      </div>

      <?php else: ?>
      <!-- ── Services Table ── -->
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead><tr><th>#</th><th>Icon</th><th>Title</th><th>Items</th><th>Order</th><th>Active</th><th>Actions</th></tr></thead>
            <tbody>
              <?php foreach ($services as $s): ?>
              <tr>
                <td class="text-muted"><?= $s['id'] ?></td>
                <td style="font-size:1.4rem"><?= $s['icon'] ?></td>
                <td style="color:var(--wh);font-weight:500"><?= htmlspecialchars($s['title']) ?></td>
                <td class="text-muted text-sm"><?= htmlspecialchars(substr($s['items'],0,60)) ?>…</td>
                <td><?= $s['sort_order'] ?></td>
                <td>
                  <form method="POST" style="display:inline">
                    <input type="hidden" name="action" value="toggle">
                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <button class="btn btn-ghost btn-sm"><?= $s['is_active'] ? '✅ Yes' : '❌ No' ?></button>
                  </form>
                </td>
                <td>
                  <a href="?edit=<?= $s['id'] ?>" class="btn btn-ghost btn-sm">Edit</a>
                  <form method="POST" style="display:inline" onsubmit="return confirm('Delete this service?')">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                    <button class="btn btn-danger btn-sm">Delete</button>
                  </form>
                </td>
              </tr>
              <?php endforeach; ?>
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
