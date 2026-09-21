<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireAdmin();
$user = currentUser();
$flash = null;

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name  = trim($_POST['name'] ?? '');
        $icon  = trim($_POST['icon'] ?? 'fa-lightbulb');
        $color = trim($_POST['color'] ?? '#2563eb');
        $slug  = strtolower(preg_replace('/[^a-z0-9]+/', '-', $name));
        if (strlen($name) >= 2) {
            try {
                $pdo->prepare("INSERT INTO categories (name,slug,icon,color) VALUES (?,?,?,?)")->execute([$name,$slug,$icon,$color]);
                $flash = ['type'=>'success','msg'=>"Category '$name' added successfully."];
            } catch (Exception $e) {
                $flash = ['type'=>'error','msg'=>'Category name or slug already exists.'];
            }
        } else { $flash = ['type'=>'error','msg'=>'Category name must be at least 2 characters.']; }
    }
    if ($action === 'delete') {
        $id = (int)$_POST['cat_id'];
        $count = $pdo->prepare("SELECT COUNT(*) FROM ideas WHERE category_id=?"); $count->execute([$id]);
        if ($count->fetchColumn() > 0) { jsonResponse(['success'=>false,'message'=>'Cannot delete — ideas are assigned to this category.']); }
        $pdo->prepare("DELETE FROM categories WHERE id=?")->execute([$id]);
        jsonResponse(['success'=>true]);
    }
    if ($action === 'update') {
        $id    = (int)$_POST['cat_id'];
        $name  = trim($_POST['name']??'');
        $icon  = trim($_POST['icon']??'fa-lightbulb');
        $color = trim($_POST['color']??'#2563eb');
        $pdo->prepare("UPDATE categories SET name=?,icon=?,color=? WHERE id=?")->execute([$name,$icon,$color,$id]);
        jsonResponse(['success'=>true]);
    }
}

$categories = $pdo->query("
    SELECT c.*, (SELECT COUNT(*) FROM ideas WHERE category_id=c.id) AS idea_count
    FROM categories c ORDER BY c.name ASC
")->fetchAll();

$icons = ['fa-microchip','fa-users','fa-heartbeat','fa-graduation-cap','fa-leaf','fa-chart-line','fa-palette','fa-lightbulb','fa-rocket','fa-globe','fa-home','fa-car','fa-briefcase','fa-music','fa-camera','fa-code'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Categories — IdeaMarket Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<div class="layout-wrapper">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon"><i class="fas fa-crown" style="color:#f59e0b;"></i></div>
      <div class="brand-text"><h6>Admin Panel</h6><span>IdeaMarket</span></div>
    </div>
    <div class="sidebar-user">
      <div class="user-avatar" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);">AD</div>
      <div class="user-info"><h6><?= e($user['name']) ?></h6><span style="color:var(--gold);font-size:.7rem;"><i class="fas fa-crown" style="font-size:.65rem;"></i> Administrator</span></div>
    </div>
    <nav class="sidebar-nav">
      <span class="nav-section-label">Management</span>
      <a href="admin.php"><i class="fas fa-th-large"></i> Dashboard</a>
      <a href="admin-ideas.php"><i class="fas fa-lightbulb"></i> Manage Ideas</a>
      <a href="admin-users.php"><i class="fas fa-users"></i> Users</a>
      <a href="admin-investments.php"><i class="fas fa-hand-holding-usd"></i> Investments</a>
      <a href="admin-categories.php" class="active"><i class="fas fa-tags"></i> Categories</a>
      <span class="nav-section-label" style="margin-top:.5rem;">Analytics</span>
      <a href="admin-reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
      <a href="admin-activity.php"><i class="fas fa-history"></i> Activity Log</a>
      <span class="nav-section-label" style="margin-top:.5rem;">System</span>
      <a href="admin-settings.php"><i class="fas fa-cog"></i> Settings</a>
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
    </nav>
  </aside>
  <main class="main-content">
    <header class="top-header">
      <div style="display:flex;align-items:center;gap:1rem;">
        <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
        <span class="page-title">Categories</span>
      </div>
      <div class="header-right">
        <div class="dark-toggle"><i class="fas fa-moon" style="font-size:.8rem;"></i><div class="toggle-switch"></div></div>
        <div class="header-avatar" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);">AD</div>
      </div>
    </header>
    <div class="page-body fade-in">
      <div class="page-header">
        <h1>Idea Categories <span style="font-size:1.4rem;">🏷️</span></h1>
        <p>Manage the categories that ideas are organised into on the marketplace.</p>
      </div>
      <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><i class="fas fa-<?= $flash['type']==='success'?'check-circle':'exclamation-circle' ?>"></i> <?= e($flash['msg']) ?></div>
      <?php endif; ?>
      <div style="display:grid;grid-template-columns:1fr 1.6fr;gap:1.5rem;align-items:start;">
        <!-- Add Category Form -->
        <div class="card">
          <div class="card-header"><span class="card-title"><i class="fas fa-plus-circle me-2" style="color:var(--blue)"></i>Add New Category</span></div>
          <div class="card-body">
            <form method="POST">
              <input type="hidden" name="action" value="add">
              <div class="form-group">
                <label class="form-label">Category Name <span style="color:var(--red)">*</span></label>
                <input type="text" name="name" class="form-control" placeholder="e.g. Healthcare" required>
              </div>
              <div class="form-group">
                <label class="form-label">Font Awesome Icon Class</label>
                <input type="text" name="icon" class="form-control" value="fa-lightbulb" placeholder="fa-microchip">
                <small style="color:var(--text-muted);font-size:.75rem;">Visit fontawesome.com to find icon names</small>
              </div>
              <div class="form-group">
                <label class="form-label">Colour</label>
                <div style="display:flex;gap:.75rem;align-items:center;">
                  <input type="color" name="color" value="#2563eb" style="width:50px;height:36px;border:2px solid var(--border);border-radius:8px;cursor:pointer;padding:2px;">
                  <span style="font-size:.82rem;color:var(--text-muted);">Pick a colour for this category</span>
                </div>
              </div>
              <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus"></i> Add Category</button>
            </form>
          </div>
        </div>
        <!-- Categories Table -->
        <div class="card">
          <div class="card-header">
            <span class="card-title"><i class="fas fa-tags me-2" style="color:var(--orange)"></i>All Categories</span>
            <span style="font-size:.8rem;color:var(--text-muted);"><?= count($categories) ?> categories</span>
          </div>
          <div class="table-wrapper">
            <table>
              <thead><tr><th>Icon</th><th>Name</th><th>Slug</th><th>Ideas</th><th>Action</th></tr></thead>
              <tbody>
                <?php foreach ($categories as $cat): ?>
                  <tr id="cat-<?= $cat['id'] ?>">
                    <td><i class="fas <?= e($cat['icon']) ?>" style="font-size:1.2rem;color:<?= e($cat['color']) ?>;width:28px;text-align:center;"></i></td>
                    <td><strong><?= e($cat['name']) ?></strong></td>
                    <td><code style="font-size:.75rem;background:var(--bg);padding:.15rem .4rem;border-radius:4px;"><?= e($cat['slug']) ?></code></td>
                    <td><span class="badge badge-review"><?= $cat['idea_count'] ?></span></td>
                    <td>
                      <?php if ($cat['idea_count'] == 0): ?>
                        <button class="btn btn-danger btn-sm" onclick="deleteCategory(<?= $cat['id'] ?>, '<?= e(addslashes($cat['name'])) ?>')">
                          <i class="fas fa-trash"></i>
                        </button>
                      <?php else: ?>
                        <span style="font-size:.75rem;color:var(--text-muted);" data-tooltip="Has ideas — cannot delete">🔒 In use</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>
<div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99;"></div>
<div class="spinner-overlay"><div class="spinner"></div></div>
<script src="assets/js/app.js"></script>
<script>
async function deleteCategory(id, name) {
  if (!confirm(`Delete category "${name}"? This cannot be undone.`)) return;
  const res  = await fetch('admin-categories.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`action=delete&cat_id=${id}` });
  const data = await res.json();
  if (data.success) { document.getElementById('cat-'+id)?.remove(); Toast.show('Category deleted', 'success'); }
  else Toast.show(data.message || 'Delete failed', 'error');
}
</script>
</body>
</html>
