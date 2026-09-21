<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireAdmin();
$user   = currentUser();
$page   = max(1,(int)($_GET['page']??1));
$perPage = 20;
$search  = trim($_GET['q']??'');
$status  = $_GET['status']??'';

$where  = ['1=1'];
$params = [];
if ($search) { $where[] = '(i.title LIKE ? OR u.full_name LIKE ? OR al.action LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($status) { $where[] = 'al.status = ?'; $params[] = $status; }
$whereSQL = 'WHERE '.implode(' AND ',$where);

$total = $pdo->prepare("SELECT COUNT(*) FROM activity_log al JOIN ideas i ON al.idea_id=i.id JOIN users u ON al.performed_by=u.id $whereSQL");
$total->execute($params);
$totalCount = $total->fetchColumn();
$pg = paginate($totalCount,$perPage,$page);

$stmt = $pdo->prepare("
    SELECT al.*, i.title AS idea_title, i.idea_id AS idea_code, u.full_name
    FROM activity_log al
    JOIN ideas i ON al.idea_id=i.id
    JOIN users u ON al.performed_by=u.id
    $whereSQL
    ORDER BY al.timestamp DESC
    LIMIT $perPage OFFSET {$pg['offset']}
");
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Activity Log — IdeaMarket Admin</title>
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
      <a href="admin-activity.php" class="active"><i class="fas fa-history"></i> Activity Log</a>
      <span class="nav-section-label" style="margin-top:.5rem;">System</span>
      <a href="admin-settings.php"><i class="fas fa-cog"></i> Settings</a>
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
    </nav>
  </aside>
  <main class="main-content">
    <header class="top-header">
      <div style="display:flex;align-items:center;gap:1rem;">
        <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
        <span class="page-title">Activity Log</span>
      </div>
      <div class="header-right">
        <div class="dark-toggle"><i class="fas fa-moon" style="font-size:.8rem;"></i><div class="toggle-switch"></div></div>
        <div class="header-avatar" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);">AD</div>
      </div>
    </header>
    <div class="page-body fade-in">
      <div class="page-header">
        <h1>Activity Log <span style="font-size:1.4rem;">📋</span></h1>
        <p>Complete audit trail of all actions performed on the platform.</p>
      </div>
      <div class="filter-bar">
        <form method="GET" style="display:contents;">
          <div class="search-input-wrap">
            <i class="fas fa-search"></i>
            <input type="text" name="q" class="form-control" placeholder="Search idea, user, or action..." value="<?= e($search) ?>">
          </div>
          <select name="status" class="form-control" style="min-width:150px;" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="pending"  <?= $status==='pending'?'selected':'' ?>>Pending</option>
            <option value="review"   <?= $status==='review'?'selected':'' ?>>Under Review</option>
            <option value="approved" <?= $status==='approved'?'selected':'' ?>>Approved</option>
            <option value="funded"   <?= $status==='funded'?'selected':'' ?>>Funded</option>
            <option value="closed"   <?= $status==='closed'?'selected':'' ?>>Closed</option>
          </select>
          <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
          <?php if ($search||$status): ?><a href="admin-activity.php" class="btn btn-outline">Reset</a><?php endif; ?>
        </form>
        <span style="margin-left:auto;font-size:.82rem;color:var(--text-muted);"><?= number_format($totalCount) ?> entries</span>
      </div>
      <div class="card">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-history me-2" style="color:var(--purple)"></i>All Activity</span>
          <span style="font-size:.72rem;color:var(--green);font-weight:600;"><i class="fas fa-circle" style="font-size:.5rem;margin-right:.3rem;"></i>Live</span>
        </div>
        <div class="table-wrapper">
          <table>
            <thead><tr><th>Time (WAT)</th><th>Idea</th><th>Action</th><th>Status</th><th>Performed By</th></tr></thead>
            <tbody>
              <?php if ($logs): ?>
                <?php foreach ($logs as $log):
                  $dotColor = match($log['status']??'') {
                    'pending'=>'orange','review'=>'blue','approved'=>'green','funded'=>'purple','closed'=>'red',default=>'blue'
                  };
                ?>
                  <tr>
                    <td style="font-size:.75rem;color:var(--text-muted);white-space:nowrap;"><?= formatDateTime($log['timestamp']) ?></td>
                    <td>
                      <a href="idea.php?id=<?= $log['idea_id'] ?>" style="font-weight:600;color:var(--text);font-size:.85rem;"><?= e(substr($log['idea_title'],0,28)) ?><?= strlen($log['idea_title'])>28?'…':'' ?></a>
                      <br><code style="font-size:.68rem;background:var(--bg);padding:.1rem .35rem;border-radius:4px;"><?= e($log['idea_code']) ?></code>
                    </td>
                    <td style="font-size:.82rem;"><?= e($log['action']) ?></td>
                    <td>
                      <?php if ($log['status']): ?>
                        <span class="activity-dot <?= $dotColor ?>" style="display:inline-block;width:9px;height:9px;border-radius:50%;margin-right:.4rem;vertical-align:middle;"></span>
                        <?= statusBadge($log['status']) ?>
                      <?php else: ?>
                        <span style="color:var(--text-muted);font-size:.78rem;">—</span>
                      <?php endif; ?>
                    </td>
                    <td style="font-size:.82rem;font-weight:600;"><?= e($log['full_name']) ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="5"><div class="empty-state"><i class="fas fa-history"></i><h5>No activity yet</h5><p>Activity will appear here as users interact with the platform.</p></div></td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($pg['total_pages'] > 1): ?>
          <div class="pagination" style="padding:1rem;">
            <?php for ($p=1;$p<=$pg['total_pages'];$p++): ?>
              <a href="admin-activity.php?page=<?=$p?>&q=<?=e($search)?>&status=<?=e($status)?>" class="page-btn <?=$p===$page?'active':''?>"><?=$p?></a>
            <?php endfor; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </main>
</div>
<div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99;"></div>
<div class="spinner-overlay"><div class="spinner"></div></div>
<script src="assets/js/app.js"></script>
</body>
</html>
