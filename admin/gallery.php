<?php
require_once dirname(__DIR__) . '/includes/auth.php';
require_login();
require_once dirname(__DIR__) . '/includes/db.php';

$active_page = 'gallery';
$message = '';

$upload_dir = dirname(__DIR__) . '/public/uploads/gallery/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';

    if ($action === 'upload' && isset($_FILES['image'])) {
        $file = $_FILES['image'];
        $allowed = ['image/jpeg','image/png','image/webp','image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($file['type'], $allowed)) {
            $message = '❌ Only JPG, PNG, WebP, GIF allowed.';
        } elseif ($file['size'] > $max_size) {
            $message = '❌ File too large (max 5MB).';
        } else {
            $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid('img_') . '.' . strtolower($ext);
            $dest     = $upload_dir . $filename;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $pdo->prepare("INSERT INTO gallery (filename,caption,category,sort_order)
                               VALUES (?,?,?,?)")
                    ->execute([$filename,
                               htmlspecialchars(trim($_POST['caption'] ?? '')),
                               htmlspecialchars(trim($_POST['category'] ?? '')),
                               (int)($_POST['sort_order'] ?? 0)]);
                $message = '✅ Image uploaded.';
            } else {
                $message = '❌ Upload failed. Check folder permissions.';
            }
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $row = $pdo->prepare("SELECT filename FROM gallery WHERE id=?");
        $row->execute([$id]);
        $row = $row->fetch();
        if ($row) {
            @unlink($upload_dir . $row['filename']);
            $pdo->prepare("DELETE FROM gallery WHERE id=?")->execute([$id]);
            $message = 'Image deleted.';
        }
    }
}

$images = $pdo ? $pdo->query("SELECT * FROM gallery ORDER BY sort_order, created_at DESC")->fetchAll() : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>Gallery — Ambozy Admin</title>
  <link rel="stylesheet" href="/assets/css/admin.css">
  <style>
    .gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:16px;margin-top:20px}
    .gallery-item{background:var(--s3);border:1px solid var(--bd);border-radius:var(--rl);overflow:hidden;position:relative}
    .gallery-item img{width:100%;height:140px;object-fit:cover;display:block}
    .gallery-item-info{padding:10px 12px}
    .gallery-item-caption{font-size:.8rem;color:var(--wh);font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .gallery-item-cat{font-family:var(--fm);font-size:.68rem;color:var(--gm);margin-top:2px}
    .gallery-item-del{position:absolute;top:6px;right:6px;background:rgba(0,0,0,.7);border:none;color:#f87171;border-radius:3px;padding:3px 7px;font-size:.72rem;cursor:pointer}
    .gallery-item-del:hover{background:rgba(239,68,68,.3)}
    .upload-zone{border:2px dashed var(--bd2);border-radius:var(--rl);padding:28px;text-align:center;cursor:pointer;transition:.2s}
    .upload-zone:hover{border-color:var(--o)}
  </style>
</head>
<body>
<div class="admin-layout">
  <?php require_once __DIR__ . '/partials/sidebar.php'; ?>
  <div>
    <div class="topbar"><div class="topbar-title">Gallery</div></div>
    <div class="main-content">
      <?php if ($message): ?><div class="alert <?= str_starts_with($message,'❌')?'alert-danger':'alert-success' ?>"><?= $message ?></div><?php endif; ?>

      <!-- Upload Form -->
      <div class="card" style="max-width:700px;margin-bottom:24px">
        <div class="card-header"><div class="card-title">Upload Image</div></div>
        <div class="card-body">
          <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="upload">
            <div class="upload-zone" onclick="document.getElementById('imageFile').click()">
              <div style="font-size:2rem;margin-bottom:8px">🖼️</div>
              <div style="font-weight:600;color:var(--wh)">Click to select image</div>
              <div class="text-sm text-muted">JPG, PNG, WebP or GIF — max 5MB</div>
              <input type="file" id="imageFile" name="image" accept="image/*" style="display:none" onchange="this.closest('form').querySelector('.fn').textContent=this.files[0]?.name||''">
            </div>
            <div class="fn text-sm text-muted mt-16"></div>
            <div class="form-row mt-16">
              <div class="form-group">
                <label class="form-label">Caption</label>
                <input class="form-control" name="caption" placeholder="Branded polo shirts for ChildFund">
              </div>
              <div class="form-group">
                <label class="form-label">Category</label>
                <select class="form-control" name="category">
                  <option value="">General</option>
                  <option>Merchandise</option><option>Signage</option>
                  <option>Packaging</option><option>Outdoor</option>
                  <option>Awards</option><option>Stationery</option>
                </select>
              </div>
            </div>
            <button class="btn btn-primary">Upload Image</button>
          </form>
        </div>
      </div>

      <!-- Gallery Grid -->
      <?php if ($images): ?>
      <div class="card-title" style="margin-bottom:12px"><?= count($images) ?> image<?= count($images)!==1?'s':'' ?></div>
      <div class="gallery-grid">
        <?php foreach ($images as $img): ?>
        <div class="gallery-item">
          <img src="/public/uploads/gallery/<?= htmlspecialchars($img['filename']) ?>"
               alt="<?= htmlspecialchars($img['caption']) ?>"
               onerror="this.style.height='60px'">
          <div class="gallery-item-info">
            <div class="gallery-item-caption"><?= htmlspecialchars($img['caption'] ?: $img['filename']) ?></div>
            <div class="gallery-item-cat"><?= htmlspecialchars($img['category'] ?: 'General') ?></div>
          </div>
          <form method="POST" onsubmit="return confirm('Delete this image?')">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $img['id'] ?>">
            <button class="gallery-item-del">✕</button>
          </form>
        </div>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="text-muted" style="text-align:center;padding:40px">No images yet. Upload your first one above.</div>
      <?php endif; ?>

    </div>
  </div>
</div>
</body>
</html>
