<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$user   = currentUser();
$status = $_GET['status'] ?? '';
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

$where  = ['i.user_id = ?'];
$params = [$user['id']];
if ($status) { $where[] = 'i.status = ?'; $params[] = $status; }

$whereSQL = 'WHERE ' . implode(' AND ', $where);
$total    = $pdo->prepare("SELECT COUNT(*) FROM ideas i $whereSQL");
$total->execute($params);
$totalCount = $total->fetchColumn();
$pg = paginate($totalCount, $perPage, $page);

$stmt = $pdo->prepare("
  SELECT i.*, c.name AS cat_name, c.icon AS cat_icon
  FROM ideas i JOIN categories c ON i.category_id=c.id
  $whereSQL ORDER BY i.created_at DESC LIMIT $perPage OFFSET {$pg['offset']}
");
$stmt->execute($params);
$ideas = $stmt->fetchAll();
$userStats = getUserStats($pdo, $user['id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Ideas — IdeaMarket</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>
<div class="layout-wrapper">
  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon"><i class="fas fa-lightbulb"></i></div>
      <div class="brand-text"><h6>IdeaMarket</h6><span>Innovation Platform</span></div>
    </div>
    <div class="sidebar-user">
      <div class="user-avatar"><?= strtoupper(substr($user['name'],0,2)) ?></div>
      <div class="user-info"><h6><?= e($user['name']) ?></h6><span>Idea Submitter</span></div>
    </div>
    <nav class="sidebar-nav">
      <span class="nav-section-label">Main Menu</span>
      <a href="dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a>
      <a href="submit-idea.php"><i class="fas fa-plus-circle"></i> Submit Idea</a>
      <a href="my-ideas.php" class="active"><i class="fas fa-lightbulb"></i> My Ideas <span class="badge-count"><?= $userStats['total'] ?></span></a>
      <a href="browse.php"><i class="fas fa-compass"></i> Browse Market</a>
      <a href="track.php"><i class="fas fa-search"></i> Track an Idea</a>
      <span class="nav-section-label" style="margin-top:.5rem;">Account</span>
      <a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
    </nav>
  </aside>
  <main class="main-content">
    <header class="top-header">
      <div style="display:flex;align-items:center;gap:1rem;">
        <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
        <span class="page-title">My Ideas</span>
      </div>
      <div class="header-right">
        <div class="dark-toggle"><i class="fas fa-moon" style="font-size:.8rem;"></i><div class="toggle-switch"></div></div>
        <a href="submit-idea.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> New Idea</a>
        <div class="header-avatar"><?= strtoupper(substr($user['name'],0,2)) ?></div>
      </div>
    </header>

    <div class="page-body fade-in">
      <div class="page-header">
        <div class="breadcrumb"><a href="dashboard.php">Dashboard</a><i class="fas fa-chevron-right" style="font-size:.65rem;"></i> My Ideas</div>
        <h1>My Ideas 💡</h1>
        <p>Track and manage all the ideas you've submitted to the marketplace.</p>
      </div>

      <!-- Mini stats -->
      <div class="stat-cards" style="grid-template-columns:repeat(5,1fr);margin-bottom:1.5rem;">
        <?php foreach ([['Total','total','blue','fa-lightbulb'],['Pending','pending','orange','fa-clock'],['Under Review','review','blue','fa-search'],['Approved','approved','green','fa-check-circle'],['Funded','funded','purple','fa-rocket']] as [$lbl,$key,$col,$icon]): ?>
          <a href="my-ideas.php?status=<?= $key==='total'?'':$key ?>" class="stat-card <?= $col ?>" style="cursor:pointer;text-decoration:none;<?= $status===$key||($key==='total'&&!$status)?'box-shadow:0 0 0 3px var(--blue)':'' ?>">
            <div class="stat-label"><?= $lbl ?></div>
            <div class="stat-value" style="font-size:1.6rem;"><?= $userStats[$key] ?></div>
            <i class="fas <?= $icon ?> stat-icon"></i>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="card">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-list me-2" style="color:var(--blue)"></i>
            <?= $status ? ucfirst($status) . ' Ideas' : 'All My Ideas' ?>
            <span style="font-size:.78rem;color:var(--text-muted);font-weight:400;margin-left:.5rem;">(<?= $totalCount ?>)</span>
          </span>
          <a href="submit-idea.php" class="btn btn-primary btn-sm"><i class="fas fa-plus"></i> Submit New</a>
        </div>
        <div class="table-wrapper">
          <table id="myIdeasTable">
            <thead>
              <tr>
                <th>Idea ID</th><th>Title</th><th>Category</th><th>Status</th>
                <th>Priority</th><th>Votes</th><th>Date</th><th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($ideas): ?>
                <?php foreach ($ideas as $idea): ?>
                  <tr>
                    <td><code style="font-size:.72rem;background:var(--bg);padding:.2rem .4rem;border-radius:4px;"><?= e($idea['idea_id']) ?></code></td>
                    <td style="max-width:180px;font-weight:600;"><?= e(substr($idea['title'],0,32)) ?><?= strlen($idea['title'])>32?'…':'' ?></td>
                    <td style="font-size:.8rem;color:var(--text-muted);"><i class="fas <?= e($idea['cat_icon']) ?>" style="margin-right:.3rem;"></i><?= e($idea['cat_name']) ?></td>
                    <td><?= statusBadge($idea['status']) ?></td>
                    <td><?= priorityBadge($idea['priority']) ?></td>
                    <td><i class="fas fa-heart" style="color:var(--red);font-size:.8rem;margin-right:.2rem;"></i><?= $idea['vote_count'] ?></td>
                    <td style="font-size:.78rem;color:var(--text-muted);"><?= formatDate($idea['created_at'],'M j, Y') ?></td>
                    <td>
                      <div style="display:flex;gap:.35rem;">
                        <a href="idea.php?id=<?= $idea['id'] ?>" class="btn btn-outline btn-sm" title="View"><i class="fas fa-eye"></i></a>
                        <a href="track.php?id=<?= e($idea['idea_id']) ?>" class="btn btn-primary btn-sm" title="Track"><i class="fas fa-search"></i></a>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="8">
                  <div class="empty-state">
                    <i class="fas fa-lightbulb"></i>
                    <h5><?= $status ? "No $status ideas" : 'No ideas yet!' ?></h5>
                    <p>Submit your first idea to the marketplace.</p>
                    <a href="submit-idea.php" class="btn btn-primary mt-2"><i class="fas fa-plus"></i> Submit Idea</a>
                  </div>
                </td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <?php if ($pg['total_pages'] > 1): ?>
          <div class="pagination" style="padding:1rem;">
            <?php for ($p=1;$p<=$pg['total_pages'];$p++): ?>
              <a href="my-ideas.php?page=<?=$p?>&status=<?=e($status)?>" class="page-btn <?=$p===$page?'active':''?>"><?=$p?></a>
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
<script>initLiveSearch('searchMyIdeas','myIdeasTable');</script>
</body>
</html>
