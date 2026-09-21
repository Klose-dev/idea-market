<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireAdmin();

$user  = currentUser();
$page  = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$search  = trim($_GET['q'] ?? '');
$status  = $_GET['status'] ?? '';

$where  = ['1=1'];
$params = [];
if ($search) { $where[] = '(i.title LIKE ? OR u.full_name LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($status) { $where[] = 'inv.status = ?'; $params[] = $status; }
$whereSQL = 'WHERE ' . implode(' AND ', $where);

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM investments inv JOIN ideas i ON inv.idea_id=i.id JOIN users u ON inv.investor_id=u.id $whereSQL");
$totalStmt->execute($params);
$total = $totalStmt->fetchColumn();
$pg    = paginate($total, $perPage, $page);

$stmt = $pdo->prepare("
    SELECT inv.*, i.title AS idea_title, i.idea_id,
           u.full_name AS investor_name, u.email AS investor_email
    FROM investments inv
    JOIN ideas i  ON inv.idea_id    = i.id
    JOIN users u  ON inv.investor_id = u.id
    $whereSQL
    ORDER BY inv.created_at DESC
    LIMIT $perPage OFFSET {$pg['offset']}
");
$stmt->execute($params);
$investments = $stmt->fetchAll();

// Summary stats
$stats = $pdo->query("
    SELECT
        COUNT(*) AS total,
        SUM(status='pledged')   AS pledged,
        SUM(status='confirmed') AS confirmed,
        SUM(status='withdrawn') AS withdrawn,
        COALESCE(SUM(CASE WHEN status='confirmed' THEN amount ELSE 0 END), 0) AS total_confirmed,
        COALESCE(SUM(amount), 0) AS total_pledged
    FROM investments
")->fetch();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $invId     = (int)$_POST['inv_id'];
    $newStatus = $_POST['status'] ?? '';
    if (in_array($newStatus, ['pledged','confirmed','withdrawn'])) {
        $pdo->prepare("UPDATE investments SET status=? WHERE id=?")->execute([$newStatus, $invId]);
        jsonResponse(['success' => true]);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Investments — IdeaMarket Admin</title>
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
      <a href="admin-investments.php" class="active"><i class="fas fa-hand-holding-usd"></i> Investments</a>
      <a href="admin-categories.php"><i class="fas fa-tags"></i> Categories</a>
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
        <span class="page-title">Investments</span>
      </div>
      <div class="header-right">
        <div class="dark-toggle"><i class="fas fa-moon" style="font-size:.8rem;"></i><div class="toggle-switch"></div></div>
        <div class="header-avatar" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);">AD</div>
      </div>
    </header>

    <div class="page-body fade-in">
      <div class="page-header">
        <h1>Investments <span style="font-size:1.4rem;">💰</span></h1>
        <p>Track all investment pledges and funding activity across the marketplace.</p>
      </div>

      <!-- Stats -->
      <div class="stat-cards" style="grid-template-columns:repeat(4,1fr);margin-bottom:1.75rem;">
        <div class="stat-card blue">
          <div class="stat-label">Total Pledges</div>
          <div class="stat-value"><?= $stats['total'] ?></div>
          <i class="fas fa-hand-holding-usd stat-icon"></i>
        </div>
        <div class="stat-card orange">
          <div class="stat-label">Pledged</div>
          <div class="stat-value"><?= $stats['pledged'] ?></div>
          <i class="fas fa-clock stat-icon"></i>
        </div>
        <div class="stat-card green">
          <div class="stat-label">Confirmed</div>
          <div class="stat-value"><?= $stats['confirmed'] ?></div>
          <i class="fas fa-check-circle stat-icon"></i>
        </div>
        <div class="stat-card purple">
          <div class="stat-label">Total Confirmed ($)</div>
          <div class="stat-value" style="font-size:1.4rem;">$<?= number_format($stats['total_confirmed'],0) ?></div>
          <i class="fas fa-dollar-sign stat-icon"></i>
        </div>
      </div>

      <!-- Filter -->
      <div class="filter-bar">
        <form method="GET" style="display:contents;">
          <div class="search-input-wrap">
            <i class="fas fa-search"></i>
            <input type="text" name="q" class="form-control" placeholder="Search by idea or investor..." value="<?= e($search) ?>">
          </div>
          <select name="status" class="form-control" style="min-width:150px;" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="pledged"   <?= $status==='pledged'?'selected':'' ?>>Pledged</option>
            <option value="confirmed" <?= $status==='confirmed'?'selected':'' ?>>Confirmed</option>
            <option value="withdrawn" <?= $status==='withdrawn'?'selected':'' ?>>Withdrawn</option>
          </select>
          <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
          <?php if ($search||$status): ?><a href="admin-investments.php" class="btn btn-outline">Reset</a><?php endif; ?>
        </form>
        <span style="margin-left:auto;font-size:.82rem;color:var(--text-muted);"><?= $total ?> records</span>
      </div>

      <div class="card">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-hand-holding-usd me-2" style="color:var(--green)"></i>All Investments</span>
        </div>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr><th>#</th><th>Idea</th><th>Investor</th><th>Amount</th><th>Status</th><th>Message</th><th>Date</th><th>Action</th></tr>
            </thead>
            <tbody>
              <?php if ($investments): ?>
                <?php foreach ($investments as $inv): ?>
                  <tr>
                    <td style="color:var(--text-muted);font-size:.8rem;"><?= $inv['id'] ?></td>
                    <td>
                      <a href="idea.php?id=<?= $inv['idea_id'] ?>" style="font-weight:600;color:var(--blue);font-size:.85rem;"><?= e(substr($inv['idea_title'],0,30)) ?><?= strlen($inv['idea_title'])>30?'…':'' ?></a>
                      <br><code style="font-size:.68rem;background:var(--bg);padding:.1rem .35rem;border-radius:4px;"><?= e($inv['idea_id']) ?></code>
                    </td>
                    <td>
                      <div style="font-weight:600;font-size:.85rem;"><?= e($inv['investor_name']) ?></div>
                      <div style="font-size:.75rem;color:var(--text-muted);"><?= e($inv['investor_email']) ?></div>
                    </td>
                    <td style="font-weight:700;color:var(--green);font-size:.95rem;">$<?= number_format($inv['amount'],2) ?></td>
                    <td>
                      <?php
                        $sc = ['pledged'=>'badge-pending','confirmed'=>'badge-approved','withdrawn'=>'badge-closed'];
                        $si = ['pledged'=>'fa-clock','confirmed'=>'fa-check-circle','withdrawn'=>'fa-times-circle'];
                        $cls = $sc[$inv['status']] ?? 'badge-review';
                        $icon = $si[$inv['status']] ?? 'fa-question';
                      ?>
                      <span class="badge <?= $cls ?>"><i class="fas <?= $icon ?>"></i> <?= ucfirst($inv['status']) ?></span>
                    </td>
                    <td style="font-size:.8rem;color:var(--text-muted);max-width:160px;"><?= e(substr($inv['message'] ?? '—',0,40)) ?></td>
                    <td style="font-size:.78rem;color:var(--text-muted);"><?= formatDate($inv['created_at'],'M j, Y') ?></td>
                    <td>
                      <select class="form-control" style="padding:.3rem .5rem;font-size:.75rem;min-width:110px;"
                              onchange="updateInvestment(<?= $inv['id'] ?>, this.value, this)">
                        <option value="pledged"   <?= $inv['status']==='pledged'?'selected':'' ?>>Pledged</option>
                        <option value="confirmed" <?= $inv['status']==='confirmed'?'selected':'' ?>>Confirmed</option>
                        <option value="withdrawn" <?= $inv['status']==='withdrawn'?'selected':'' ?>>Withdrawn</option>
                      </select>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="8">
                  <div class="empty-state"><i class="fas fa-hand-holding-usd"></i><h5>No investments yet</h5><p>Investment pledges will appear here once investors start supporting ideas.</p></div>
                </td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($pg['total_pages'] > 1): ?>
          <div class="pagination" style="padding:1rem;">
            <?php for ($p=1;$p<=$pg['total_pages'];$p++): ?>
              <a href="admin-investments.php?page=<?=$p?>&q=<?=e($search)?>&status=<?=e($status)?>" class="page-btn <?=$p===$page?'active':''?>"><?=$p?></a>
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
<script>
async function updateInvestment(id, status, sel) {
  const res  = await fetch('admin-investments.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`action=update&inv_id=${id}&status=${status}` });
  const data = await res.json();
  if (data.success) Toast.show('Investment status updated', 'success');
  else { Toast.show('Update failed', 'error'); }
}
</script>
</body>
</html>
