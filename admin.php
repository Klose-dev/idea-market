<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireAdmin();

$user     = currentUser();
$stats    = getAdminStats($pdo);
$recent   = getRecentIdeas($pdo, 8);
$activity = getActivity($pdo, 10);

// Recent users
$recentUsers = $pdo->query("SELECT id, full_name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard — IdeaMarket</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>

<div class="layout-wrapper">

  <!-- ── Admin Sidebar ────────────────────────── -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon"><i class="fas fa-crown" style="color:#f59e0b;"></i></div>
      <div class="brand-text"><h6>Admin Panel</h6><span>IdeaMarket</span></div>
    </div>
    <div class="sidebar-user">
      <div class="user-avatar" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);">AD</div>
      <div class="user-info">
        <h6><?= e($user['name']) ?></h6>
        <span style="color:var(--gold);font-size:.7rem;"><i class="fas fa-crown" style="font-size:.65rem;"></i> Administrator</span>
      </div>
    </div>
    <nav class="sidebar-nav">
      <span class="nav-section-label">Management</span>
      <a href="admin.php" class="active"><i class="fas fa-th-large"></i> Dashboard</a>
      <a href="admin-ideas.php"><i class="fas fa-lightbulb"></i> Manage Ideas <span class="badge-count"><?= $stats['pending'] ?></span></a>
      <a href="admin-users.php"><i class="fas fa-users"></i> Users</a>
      <a href="admin-investments.php"><i class="fas fa-hand-holding-usd"></i> Investments</a>
      <a href="admin-categories.php"><i class="fas fa-tags"></i> Categories</a>
      <span class="nav-section-label" style="margin-top:.5rem;">Analytics</span>
      <a href="admin-reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
      <a href="admin-activity.php"><i class="fas fa-history"></i> Activity Log</a>
      <span class="nav-section-label" style="margin-top:.5rem;">System</span>
      <a href="admin-settings.php"><i class="fas fa-cog"></i> Settings</a>
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
    </nav>
    <div class="sidebar-footer">
      <a href="index.php"><i class="fas fa-home"></i> View Live Site</a>
    </div>
  </aside>

  <!-- ── Main Content ──────────────────────────── -->
  <main class="main-content">

    <!-- Top Header -->
    <header class="top-header">
      <div style="display:flex;align-items:center;gap:1rem;">
        <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
        <span class="page-title">IdeaMarket Admin Dashboard</span>
      </div>
      <div class="header-right">
        <span class="header-welcome">Welcome, <strong>Admin</strong></span>
        <div class="dark-toggle">
          <i class="fas fa-moon" style="font-size:.8rem;"></i>
          <div class="toggle-switch" title="Dark Mode"></div>
        </div>
        <a href="admin-ideas.php?status=pending" style="position:relative;color:var(--text-muted);font-size:1.1rem;" data-tooltip="<?= $stats['pending'] ?> pending ideas">
          <i class="fas fa-bell"></i>
          <?php if($stats['pending'] > 0): ?>
            <span style="position:absolute;top:-4px;right:-4px;background:var(--red);color:#fff;font-size:.6rem;width:16px;height:16px;border-radius:50%;display:flex;align-items:center;justify-content:center;"><?= $stats['pending'] ?></span>
          <?php endif; ?>
        </a>
        <div class="header-avatar" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);">AD</div>
      </div>
    </header>

    <!-- Page Body -->
    <div class="page-body fade-in">

      <!-- Page Header -->
      <div class="page-header">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
          <div>
            <h1>Overview Dashboard <span style="font-size:1.4rem;">📊</span></h1>
            <p>Real-time marketplace statistics and activity.</p>
          </div>
          <div style="display:flex;gap:.75rem;">
            <a href="admin-reports.php" class="btn btn-outline btn-sm"><i class="fas fa-download"></i> Export Report</a>
            <a href="admin-ideas.php" class="btn btn-primary btn-sm"><i class="fas fa-tasks"></i> Review Ideas</a>
          </div>
        </div>
      </div>

      <!-- Stat Cards -->
      <div class="stat-cards">
        <div class="stat-card blue fade-up delay-1">
          <div class="stat-label">Total Ideas</div>
          <div class="stat-value" data-stat="total"><?= $stats['total'] ?></div>
          <div class="stat-change">All time submissions</div>
          <i class="fas fa-lightbulb stat-icon"></i>
        </div>
        <div class="stat-card orange fade-up delay-2">
          <div class="stat-label">Pending Review</div>
          <div class="stat-value" data-stat="pending"><?= $stats['pending'] ?></div>
          <div class="stat-change"><span class="up"><i class="fas fa-arrow-up"></i> Action required</span></div>
          <i class="fas fa-clock stat-icon"></i>
        </div>
        <div class="stat-card green fade-up delay-3">
          <div class="stat-label">Approved Ideas</div>
          <div class="stat-value" data-stat="approved"><?= $stats['approved'] ?></div>
          <div class="stat-change">Live on marketplace</div>
          <i class="fas fa-check-circle stat-icon"></i>
        </div>
        <div class="stat-card purple fade-up delay-4">
          <div class="stat-label">Funded Ideas</div>
          <div class="stat-value" data-stat="funded"><?= $stats['funded'] ?></div>
          <div class="stat-change">Successfully funded</div>
          <i class="fas fa-rocket stat-icon"></i>
        </div>
      </div>

      <!-- Secondary Stats Row -->
      <div class="stat-cards" style="grid-template-columns:repeat(4,1fr);margin-bottom:1.75rem;">
        <div class="stat-card blue">
          <div class="stat-label">Total Users</div>
          <div class="stat-value" style="font-size:1.6rem;"><?= $stats['users'] ?></div>
          <i class="fas fa-users stat-icon"></i>
        </div>
        <div class="stat-card red">
          <div class="stat-label">Under Review</div>
          <div class="stat-value" style="font-size:1.6rem;"><?= $stats['review'] ?></div>
          <i class="fas fa-search stat-icon"></i>
        </div>
        <div class="stat-card green">
          <div class="stat-label">Total Votes</div>
          <div class="stat-value" style="font-size:1.6rem;">–</div>
          <i class="fas fa-heart stat-icon"></i>
        </div>
        <div class="stat-card orange">
          <div class="stat-label">Closed</div>
          <div class="stat-value" style="font-size:1.6rem;"><?= $stats['closed'] ?></div>
          <i class="fas fa-archive stat-icon"></i>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="charts-row">
        <!-- Doughnut -->
        <div class="card chart-card">
          <div class="card-header">
            <span class="card-title"><i class="fas fa-chart-pie me-2" style="color:var(--blue)"></i>Ideas Overview</span>
          </div>
          <div class="chart-wrapper" style="height:260px;">
            <canvas id="doughnutChart"></canvas>
          </div>
        </div>
        <!-- Line Chart -->
        <div class="card chart-card">
          <div class="card-header">
            <span class="card-title"><i class="fas fa-chart-line me-2" style="color:var(--green)"></i>Ideas Trend</span>
            <div class="chart-dropdown">
              <select class="chart-period-select form-control" style="padding:.3rem .6rem;font-size:.78rem;min-width:auto;">
                <option value="7">Last 7 Days</option>
                <option value="30">Last 30 Days</option>
                <option value="90">Last 3 Months</option>
              </select>
            </div>
          </div>
          <div class="chart-wrapper" style="height:260px;padding:1rem 1.5rem 1.5rem;">
            <canvas id="lineChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Bottom Row -->
      <div class="bottom-row">
        <!-- Recent Ideas Table -->
        <div class="card">
          <div class="card-header">
            <span class="card-title"><i class="fas fa-list me-2" style="color:var(--orange)"></i>Recent Ideas</span>
            <a href="admin-ideas.php" class="btn btn-primary btn-sm"><i class="fas fa-eye"></i> View All</a>
          </div>
          <div class="table-wrapper">
            <table>
              <thead>
                <tr>
                  <th>Idea ID</th>
                  <th>Title</th>
                  <th>Submitter</th>
                  <th>Status</th>
                  <th>Priority</th>
                  <th>Votes</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($recent as $idea): ?>
                  <tr>
                    <td><code style="font-size:.72rem;background:var(--bg);padding:.2rem .4rem;border-radius:4px;"><?= e($idea['idea_id']) ?></code></td>
                    <td style="max-width:180px;"><strong><?= e(substr($idea['title'],0,28)) ?><?= strlen($idea['title'])>28?'…':'' ?></strong></td>
                    <td style="font-size:.82rem;color:var(--text-muted);"><?= e($idea['full_name']) ?></td>
                    <td><?= statusBadge($idea['status']) ?></td>
                    <td><?= priorityBadge($idea['priority']) ?></td>
                    <td><i class="fas fa-heart" style="color:var(--red);font-size:.8rem;margin-right:.3rem;"></i><?= $idea['vote_count'] ?></td>
                    <td>
                      <div style="display:flex;gap:.4rem;">
                        <a href="idea.php?id=<?= $idea['id'] ?>" class="btn btn-outline btn-sm" title="View"><i class="fas fa-eye"></i></a>
                        <a href="admin-review.php?id=<?= $idea['id'] ?>" class="btn btn-primary btn-sm" title="Review"><i class="fas fa-check"></i></a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <a href="admin-ideas.php" class="view-all-link">• View All Ideas •</a>
        </div>

        <!-- Activity Feed -->
        <div class="card">
          <div class="card-header">
            <span class="card-title"><i class="fas fa-history me-2" style="color:var(--purple)"></i>Live Activity</span>
            <span style="font-size:.72rem;color:var(--green);font-weight:600;"><i class="fas fa-circle" style="font-size:.5rem;margin-right:.3rem;"></i>Live</span>
          </div>
          <div class="activity-feed">
            <?php if ($activity): ?>
              <?php foreach ($activity as $act): ?>
                <?php
                  $dotColor = match($act['status'] ?? '') {
                    'pending' => 'orange', 'review' => 'blue', 'approved' => 'green',
                    'funded' => 'purple', 'closed' => 'red', default => 'blue'
                  };
                ?>
                <div class="activity-item" data-activity-id="<?= $act['id'] ?>">
                  <span class="activity-dot <?= $dotColor ?>"></span>
                  <div class="activity-content">
                    <p><strong><?= e(substr($act['idea_title'],0,28)) ?></strong> — <?= e($act['action']) ?></p>
                    <span><i class="fas fa-user" style="margin-right:.3rem;"></i><?= e($act['full_name']??'System') ?> &nbsp;·&nbsp; <i class="fas fa-clock"></i> <?= timeAgo($act['timestamp']) ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="empty-state" style="padding:2rem;">
                <i class="fas fa-bell-slash"></i><p>No recent activity.</p>
              </div>
            <?php endif; ?>
          </div>
          <a href="admin-activity.php" class="view-all-link">• View Full Log •</a>
        </div>
      </div>

      <!-- Recent Users -->
      <div class="card" style="margin-top:1.25rem;">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-users me-2" style="color:var(--blue)"></i>Recent Registrations</span>
          <a href="admin-users.php" class="btn btn-outline btn-sm">Manage Users</a>
        </div>
        <div class="table-wrapper">
          <table>
            <thead>
              <tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Action</th></tr>
            </thead>
            <tbody>
              <?php foreach ($recentUsers as $u): ?>
                <tr>
                  <td style="color:var(--text-muted);font-size:.82rem;"><?= $u['id'] ?></td>
                  <td><strong><?= e($u['full_name']) ?></strong></td>
                  <td style="color:var(--text-muted);font-size:.82rem;"><?= e($u['email']) ?></td>
                  <td><span class="badge <?= $u['role']==='admin' ? 'badge-funded' : 'badge-review' ?>"><?= ucfirst($u['role']) ?></span></td>
                  <td style="color:var(--text-muted);font-size:.82rem;"><?= formatDate($u['created_at']) ?></td>
                  <td><a href="admin-users.php?edit=<?= $u['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-edit"></i></a></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div><!-- /page-body -->
  </main>
</div>

<div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99;"></div>
<div class="spinner-overlay"><div class="spinner"></div></div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="assets/js/app.js"></script>
<script src="assets/js/charts.js"></script>
<script src="assets/js/realtime.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  RealTime.init(<?= $user['id'] ?>, true);
  // Pass real stats to doughnut
  initAdminDoughnut([<?= $stats['pending'] ?>, <?= $stats['review'] ?>, <?= $stats['approved'] ?>, <?= $stats['funded'] ?>, <?= $stats['closed'] ?>]);
  initAdminLine();
});
</script>
</body>
</html>
