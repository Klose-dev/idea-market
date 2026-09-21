<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireAdmin();
$user  = currentUser();
$stats = getAdminStats($pdo);

// Ideas per category
$catStats = $pdo->query("
    SELECT c.name, c.color, c.icon, COUNT(i.id) AS idea_count,
           COALESCE(SUM(i.vote_count),0) AS total_votes
    FROM categories c LEFT JOIN ideas i ON i.category_id=c.id
    GROUP BY c.id ORDER BY idea_count DESC
")->fetchAll();

// Top voted ideas
$topIdeas = $pdo->query("
    SELECT i.*, u.full_name, c.name AS cat_name
    FROM ideas i JOIN users u ON i.user_id=u.id JOIN categories c ON i.category_id=c.id
    WHERE i.status IN ('approved','funded')
    ORDER BY i.vote_count DESC LIMIT 10
")->fetchAll();

// Recent registrations per week
$regStats = $pdo->query("
    SELECT DATE(created_at) AS day, COUNT(*) AS cnt
    FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at) ORDER BY day ASC
")->fetchAll();

// Ideas submitted per day (last 30 days)
$ideaDaily = $pdo->query("
    SELECT DATE(created_at) AS day, COUNT(*) AS cnt
    FROM ideas WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at) ORDER BY day ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports — IdeaMarket Admin</title>
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
      <a href="admin-reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a>
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
        <span class="page-title">Reports & Analytics</span>
      </div>
      <div class="header-right">
        <div class="dark-toggle"><i class="fas fa-moon" style="font-size:.8rem;"></i><div class="toggle-switch"></div></div>
        <div class="header-avatar" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);">AD</div>
      </div>
    </header>

    <div class="page-body fade-in">
      <div class="page-header">
        <h1>Reports <span style="font-size:1.4rem;">📈</span></h1>
        <p>Platform analytics, idea performance, and growth metrics.</p>
      </div>

      <!-- Summary stat cards -->
      <div class="stat-cards" style="grid-template-columns:repeat(4,1fr);margin-bottom:1.75rem;">
        <div class="stat-card blue"><div class="stat-label">Total Ideas</div><div class="stat-value"><?= $stats['total'] ?></div><i class="fas fa-lightbulb stat-icon"></i></div>
        <div class="stat-card green"><div class="stat-label">Approved</div><div class="stat-value"><?= $stats['approved'] ?></div><i class="fas fa-check-circle stat-icon"></i></div>
        <div class="stat-card purple"><div class="stat-label">Funded</div><div class="stat-value"><?= $stats['funded'] ?></div><i class="fas fa-rocket stat-icon"></i></div>
        <div class="stat-card orange"><div class="stat-label">Total Users</div><div class="stat-value"><?= $stats['users'] ?></div><i class="fas fa-users stat-icon"></i></div>
      </div>

      <!-- Charts row -->
      <div class="charts-row" style="margin-bottom:1.75rem;">
        <div class="card chart-card">
          <div class="card-header"><span class="card-title"><i class="fas fa-tags me-2" style="color:var(--blue)"></i>Ideas by Category</span></div>
          <div style="padding:1.25rem 1.5rem;height:260px;"><canvas id="categoryChart"></canvas></div>
        </div>
        <div class="card chart-card">
          <div class="card-header"><span class="card-title"><i class="fas fa-chart-line me-2" style="color:var(--green)"></i>Ideas Submitted — Last 30 Days</span></div>
          <div style="padding:1.25rem 1.5rem;height:260px;"><canvas id="ideasDailyChart"></canvas></div>
        </div>
      </div>

      <!-- Top voted ideas + Category breakdown -->
      <div class="bottom-row">
        <div class="card">
          <div class="card-header"><span class="card-title"><i class="fas fa-trophy me-2" style="color:var(--gold)"></i>Top Voted Ideas</span></div>
          <div class="table-wrapper">
            <table>
              <thead><tr><th>Rank</th><th>Idea</th><th>Category</th><th>Status</th><th>Votes</th></tr></thead>
              <tbody>
                <?php foreach ($topIdeas as $i => $idea): ?>
                  <tr>
                    <td>
                      <span style="font-family:var(--font-head);font-size:1rem;font-weight:800;color:<?= $i===0?'#f59e0b':($i===1?'#94a3b8':($i===2?'#b45309':'var(--text-muted)')) ?>;">#<?= $i+1 ?></span>
                    </td>
                    <td><a href="idea.php?id=<?= $idea['id'] ?>" style="font-weight:600;color:var(--text);"><?= e(substr($idea['title'],0,32)) ?><?= strlen($idea['title'])>32?'…':'' ?></a><br><span style="font-size:.72rem;color:var(--text-muted);"><?= e($idea['full_name']) ?></span></td>
                    <td style="font-size:.8rem;color:var(--text-muted);"><?= e($idea['cat_name']) ?></td>
                    <td><?= statusBadge($idea['status']) ?></td>
                    <td><strong style="color:var(--red);">❤ <?= number_format($idea['vote_count']) ?></strong></td>
                  </tr>
                <?php endforeach; ?>
                <?php if (!$topIdeas): ?>
                  <tr><td colspan="5"><div class="empty-state"><i class="fas fa-trophy"></i><p>No approved ideas yet.</p></div></td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card">
          <div class="card-header"><span class="card-title"><i class="fas fa-layer-group me-2" style="color:var(--purple)"></i>Category Breakdown</span></div>
          <div class="card-body">
            <?php foreach ($catStats as $cat): ?>
              <?php $pct = $stats['total'] > 0 ? round($cat['idea_count']/$stats['total']*100) : 0; ?>
              <div style="margin-bottom:1rem;">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:.3rem;">
                  <span style="font-size:.82rem;font-weight:600;display:flex;align-items:center;gap:.4rem;">
                    <i class="fas <?= e($cat['icon']) ?>" style="color:<?= e($cat['color']) ?>;width:16px;"></i>
                    <?= e($cat['name']) ?>
                  </span>
                  <span style="font-size:.78rem;color:var(--text-muted);"><?= $cat['idea_count'] ?> ideas · ❤ <?= number_format($cat['total_votes']) ?></span>
                </div>
                <div class="progress-bar">
                  <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= e($cat['color']) ?>;"></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>
<div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99;"></div>
<div class="spinner-overlay"><div class="spinner"></div></div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="assets/js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  // Category bar chart
  const catCtx = document.getElementById('categoryChart');
  if (catCtx) {
    new Chart(catCtx, {
      type: 'bar',
      data: {
        labels: <?= json_encode(array_column($catStats,'name')) ?>,
        datasets: [{
          label: 'Ideas',
          data: <?= json_encode(array_column($catStats,'idea_count')) ?>,
          backgroundColor: <?= json_encode(array_column($catStats,'color')) ?>,
          borderRadius: 6, borderSkipped: false
        }]
      },
      options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{x:{grid:{display:false}},y:{beginAtZero:true,grid:{color:'rgba(100,116,139,.08)'}}} }
    });
  }
  // Daily ideas line chart
  const dailyCtx = document.getElementById('ideasDailyChart');
  if (dailyCtx) {
    new Chart(dailyCtx, {
      type: 'line',
      data: {
        labels: <?= json_encode(array_map(fn($r) => date('M j', strtotime($r['day'])), $ideaDaily)) ?>,
        datasets: [{
          label: 'Ideas Submitted',
          data: <?= json_encode(array_column($ideaDaily,'cnt')) ?>,
          borderColor: '#2563eb', backgroundColor: 'rgba(37,99,235,.08)', fill: true,
          tension: .4, pointRadius: 4, borderWidth: 2.5
        }]
      },
      options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}, scales:{x:{grid:{display:false},ticks:{maxTicksLimit:8}},y:{beginAtZero:true,grid:{color:'rgba(100,116,139,.08)'}}} }
    });
  }
  // Animate progress bars
  setTimeout(() => { document.querySelectorAll('.progress-fill').forEach(b => { const w=b.style.width; b.style.width='0'; setTimeout(()=>b.style.width=w,100); }); }, 300);
});
</script>
</body>
</html>
