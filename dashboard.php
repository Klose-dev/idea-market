<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$user  = currentUser();
$stats = getUserStats($pdo, $user['id']);
$recentIdeas = getRecentIdeas($pdo, 6, $user['id']);
$activity    = getActivity($pdo, 8, $user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — IdeaMarket</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>

<?= renderFlash() ?>

<div class="layout-wrapper">

  <!-- ── Sidebar ──────────────────────────────── -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon"><i class="fas fa-lightbulb"></i></div>
      <div class="brand-text"><h6>IdeaMarket</h6><span>Innovation Platform</span></div>
    </div>
    <div class="sidebar-user">
      <div class="user-avatar"><?= strtoupper(substr($user['name'],0,2)) ?></div>
      <div class="user-info">
        <h6><?= e($user['name']) ?></h6>
        <span>Idea Submitter</span>
      </div>
    </div>
    <nav class="sidebar-nav">
      <span class="nav-section-label">Main Menu</span>
      <a href="dashboard.php" class="active"><i class="fas fa-th-large"></i> Dashboard</a>
      <a href="submit-idea.php"><i class="fas fa-plus-circle"></i> Submit Idea</a>
      <a href="my-ideas.php"><i class="fas fa-lightbulb"></i> My Ideas <span class="badge-count"><?= $stats['total'] ?></span></a>
      <a href="browse.php"><i class="fas fa-compass"></i> Browse Market</a>
      <a href="track.php"><i class="fas fa-search"></i> Track an Idea</a>
      <span class="nav-section-label" style="margin-top:.5rem;">Account</span>
      <a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
      <a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a>
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
    </nav>
    <div class="sidebar-footer">
      <a href="index.php"><i class="fas fa-home"></i> Back to Home</a>
    </div>
  </aside>

  <!-- ── Main Content ──────────────────────────── -->
  <main class="main-content">
    <!-- Top Header -->
    <header class="top-header">
      <div style="display:flex;align-items:center;gap:1rem;">
        <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
        <span class="page-title">IdeaMarket — Idea Dashboard</span>
      </div>
      <div class="header-right">
        <span class="header-welcome">Welcome back, <strong><?= e(explode(' ',$user['name'])[0]) ?></strong></span>
        <div class="dark-toggle">
          <i class="fas fa-moon" style="font-size:.8rem;"></i>
          <div class="toggle-switch" title="Dark Mode"></div>
        </div>
        <a href="notifications.php" style="position:relative;color:var(--text-muted);font-size:1.1rem;">
          <i class="fas fa-bell"></i>
          <span data-notification-badge style="position:absolute;top:-4px;right:-4px;background:var(--red);color:#fff;font-size:.6rem;width:16px;height:16px;border-radius:50%;display:flex;align-items:center;justify-content:center;"></span>
        </a>
        <div class="header-avatar"><?= strtoupper(substr($user['name'],0,2)) ?></div>
      </div>
    </header>

    <!-- Page Body -->
    <div class="page-body fade-in">

      <!-- Page Header -->
      <div class="page-header">
        <div class="breadcrumb">
          <a href="index.php">Home</a><i class="fas fa-chevron-right" style="font-size:.65rem;"></i> Dashboard
        </div>
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
          <div>
            <h1>My Dashboard <span style="font-size:1.5rem;">💡</span></h1>
            <p>Track your ideas, votes, and funding progress.</p>
          </div>
          <a href="submit-idea.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Submit New Idea
          </a>
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
          <div class="stat-change">Awaiting admin review</div>
          <i class="fas fa-clock stat-icon"></i>
        </div>
        <div class="stat-card green fade-up delay-3">
          <div class="stat-label">Approved</div>
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

      <!-- Charts -->
      <div class="charts-row">
        <!-- Doughnut -->
        <div class="card chart-card">
          <div class="card-header">
            <span class="card-title"><i class="fas fa-chart-pie me-2" style="color:var(--blue)"></i>My Ideas Overview</span>
          </div>
          <div class="chart-wrapper" style="height:240px;">
            <canvas id="userDoughnut"></canvas>
          </div>
        </div>
        <!-- Line Chart -->
        <div class="card chart-card">
          <div class="card-header">
            <span class="card-title"><i class="fas fa-chart-line me-2" style="color:var(--green)"></i>Submission Trend</span>
            <div class="chart-dropdown">
              <select class="chart-period-select form-control" style="padding:.3rem .6rem;font-size:.78rem;min-width:auto;">
                <option value="7">Last 7 Days</option>
                <option value="30">Last 30 Days</option>
                <option value="90">Last 3 Months</option>
              </select>
            </div>
          </div>
          <div class="chart-wrapper" style="height:240px;padding:1rem 1.5rem 1.5rem;">
            <canvas id="userLine"></canvas>
          </div>
        </div>
      </div>

      <!-- Bottom Row -->
      <div class="bottom-row">
        <!-- Recent Ideas Table -->
        <div class="card">
          <div class="card-header">
            <span class="card-title"><i class="fas fa-list me-2" style="color:var(--orange)"></i>My Recent Ideas</span>
            <a href="my-ideas.php" class="btn btn-primary btn-sm">View All</a>
          </div>
          <div class="table-wrapper">
            <table id="myIdeasTable">
              <thead>
                <tr>
                  <th>Idea ID</th>
                  <th>Title</th>
                  <th>Status</th>
                  <th>Votes</th>
                  <th>Date</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($recentIdeas): ?>
                  <?php foreach ($recentIdeas as $idea): ?>
                    <tr>
                      <td><code style="font-size:.75rem;background:var(--bg);padding:.2rem .5rem;border-radius:4px;"><?= e($idea['idea_id']) ?></code></td>
                      <td><strong><?= e(substr($idea['title'],0,35)) ?><?= strlen($idea['title'])>35?'…':'' ?></strong></td>
                      <td><?= statusBadge($idea['status']) ?></td>
                      <td><i class="fas fa-heart" style="color:var(--red);font-size:.8rem;margin-right:.3rem;"></i><?= $idea['vote_count'] ?></td>
                      <td style="color:var(--text-muted);font-size:.82rem;"><?= formatDate($idea['created_at'],'M j, Y') ?></td>
                      <td><a href="idea.php?id=<?= $idea['id'] ?>" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i> View</a></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="6">
                    <div class="empty-state">
                      <i class="fas fa-lightbulb"></i>
                      <h5>No ideas yet!</h5>
                      <p>Submit your first idea to the marketplace.</p>
                      <a href="submit-idea.php" class="btn btn-primary mt-2">Submit Idea</a>
                    </div>
                  </td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
          <a href="my-ideas.php" class="view-all-link">• View All My Ideas •</a>
        </div>

        <!-- Activity Feed -->
        <div class="card">
          <div class="card-header">
            <span class="card-title"><i class="fas fa-history me-2" style="color:var(--purple)"></i>Activity Feed</span>
            <span style="font-size:.75rem;color:var(--text-muted);">Live updates</span>
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
                    <p><strong><?= e(substr($act['idea_title'],0,30)) ?></strong> — <?= e($act['action']) ?></p>
                    <span><i class="fas fa-clock"></i> <?= timeAgo($act['timestamp']) ?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php else: ?>
              <div class="empty-state" style="padding:2rem;">
                <i class="fas fa-bell-slash"></i>
                <p>No activity yet. Submit an idea to get started!</p>
              </div>
            <?php endif; ?>
          </div>
          <a href="notifications.php" class="view-all-link">• View All Activity •</a>
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
  // Start real-time polling for this user
  RealTime.init(<?= $user['id'] ?>, false);
  initLiveSearch('searchIdeas', 'myIdeasTable');
});
</script>
</body>
</html>
