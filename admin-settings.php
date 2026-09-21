<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireAdmin();
$user  = currentUser();
$flash = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id=?");
        $stmt->execute([$user['id']]);
        $hash = $stmt->fetchColumn();
        if (!password_verify($current, $hash)) {
            $flash = ['type'=>'error','msg'=>'Current password is incorrect.'];
        } elseif (strlen($new) < 8) {
            $flash = ['type'=>'error','msg'=>'New password must be at least 8 characters.'];
        } elseif ($new !== $confirm) {
            $flash = ['type'=>'error','msg'=>'New passwords do not match.'];
        } else {
            $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([password_hash($new, PASSWORD_DEFAULT), $user['id']]);
            $flash = ['type'=>'success','msg'=>'Password changed successfully.'];
        }
    }
}

// Platform stats summary
$stats = getAdminStats($pdo);
$dbSize = $pdo->query("SELECT ROUND(SUM(data_length+index_length)/1024/1024,2) AS size FROM information_schema.tables WHERE table_schema='".DB_NAME."'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Settings — IdeaMarket Admin</title>
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
      <a href="admin-categories.php"><i class="fas fa-tags"></i> Categories</a>
      <span class="nav-section-label" style="margin-top:.5rem;">Analytics</span>
      <a href="admin-reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
      <a href="admin-activity.php"><i class="fas fa-history"></i> Activity Log</a>
      <span class="nav-section-label" style="margin-top:.5rem;">System</span>
      <a href="admin-settings.php" class="active"><i class="fas fa-cog"></i> Settings</a>
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
    </nav>
  </aside>
  <main class="main-content">
    <header class="top-header">
      <div style="display:flex;align-items:center;gap:1rem;">
        <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
        <span class="page-title">Settings</span>
      </div>
      <div class="header-right">
        <div class="dark-toggle"><i class="fas fa-moon" style="font-size:.8rem;"></i><div class="toggle-switch"></div></div>
        <div class="header-avatar" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);">AD</div>
      </div>
    </header>
    <div class="page-body fade-in">
      <div class="page-header">
        <h1>Settings <span style="font-size:1.4rem;">⚙️</span></h1>
        <p>Platform configuration, account settings, and system information.</p>
      </div>
      <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><i class="fas fa-<?= $flash['type']==='success'?'check-circle':'exclamation-circle' ?>"></i> <?= e($flash['msg']) ?></div>
      <?php endif; ?>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;">

        <!-- Change Password -->
        <div class="card">
          <div class="card-header"><span class="card-title"><i class="fas fa-lock me-2" style="color:var(--blue)"></i>Change Admin Password</span></div>
          <div class="card-body">
            <form method="POST">
              <input type="hidden" name="action" value="change_password">
              <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
              </div>
              <div class="form-group">
                <label class="form-label">New Password <span style="font-weight:400;color:var(--text-muted);font-size:.78rem;">(min. 8 characters)</span></label>
                <input type="password" name="new_password" class="form-control" placeholder="••••••••" required minlength="8">
              </div>
              <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
              </div>
              <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Password</button>
            </form>
          </div>
        </div>

        <!-- System Information -->
        <div class="card">
          <div class="card-header"><span class="card-title"><i class="fas fa-server me-2" style="color:var(--orange)"></i>System Information</span></div>
          <div class="card-body">
            <?php
              $sysInfo = [
                ['PHP Version',      phpversion()],
                ['Timezone',         TZ_LABEL . ' (Africa/Douala)'],
                ['Database',         DB_NAME],
                ['DB Host',          DB_HOST],
                ['DB Size',          $dbSize . ' MB'],
                ['Total Ideas',      $stats['total']],
                ['Total Users',      $stats['users']],
                ['Server Software',  $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'],
                ['Site URL',         SITE_URL],
              ];
            ?>
            <?php foreach ($sysInfo as [$label,$val]): ?>
              <div style="display:flex;justify-content:space-between;align-items:center;padding:.6rem 0;border-bottom:1px solid var(--border);font-size:.82rem;">
                <span style="color:var(--text-muted);font-weight:500;"><?= $label ?></span>
                <span style="font-weight:600;text-align:right;max-width:200px;word-break:break-all;"><?= e((string)$val) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Platform Config info -->
        <div class="card">
          <div class="card-header"><span class="card-title"><i class="fas fa-sliders-h me-2" style="color:var(--green)"></i>Platform Configuration</span></div>
          <div class="card-body">
            <div class="alert alert-info" style="margin-bottom:1rem;"><i class="fas fa-info-circle"></i> To change these settings, edit <code>includes/db.php</code> directly.</div>
            <?php
              $configs = [
                ['Site Name',        SITE_NAME],
                ['Max File Size',    (MAX_FILE_SIZE/1024/1024).' MB'],
                ['DB Charset',       DB_CHARSET],
                ['Polling Interval', '5 seconds (realtime.js)'],
                ['Ideas Per Page',   '12 (browse), 15 (admin)'],
              ];
            ?>
            <?php foreach ($configs as [$label,$val]): ?>
              <div style="display:flex;justify-content:space-between;padding:.55rem 0;border-bottom:1px solid var(--border);font-size:.82rem;">
                <span style="color:var(--text-muted);"><?= $label ?></span>
                <span style="font-weight:600;"><?= e($val) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="card">
          <div class="card-header"><span class="card-title"><i class="fas fa-bolt me-2" style="color:var(--purple)"></i>Quick Actions</span></div>
          <div class="card-body" style="display:flex;flex-direction:column;gap:.75rem;">
            <a href="admin-categories.php" class="btn btn-outline"><i class="fas fa-tags"></i> Manage Categories</a>
            <a href="admin-users.php" class="btn btn-outline"><i class="fas fa-users"></i> Manage Users</a>
            <a href="admin-reports.php" class="btn btn-outline"><i class="fas fa-chart-bar"></i> View Reports</a>
            <a href="admin-activity.php" class="btn btn-outline"><i class="fas fa-history"></i> View Activity Log</a>
            <a href="index.php" target="_blank" class="btn btn-navy"><i class="fas fa-external-link-alt"></i> View Live Site</a>
          </div>
        </div>

      </div>
    </div>
  </main>
</div>
<div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99;"></div>
<div class="spinner-overlay"><div class="spinner"></div></div>
<script src="assets/js/app.js"></script>
</body>
</html>
