<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireAdmin();
$user  = currentUser();
$id    = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: admin-ideas.php'); exit; }

$stmt = $pdo->prepare("
    SELECT i.*, c.name AS cat_name, c.icon AS cat_icon, c.color AS cat_color,
           u.full_name AS submitter, u.email AS submitter_email
    FROM ideas i JOIN categories c ON i.category_id=c.id JOIN users u ON i.user_id=u.id
    WHERE i.id=?
");
$stmt->execute([$id]);
$idea = $stmt->fetch();
if (!$idea) { header('Location: admin-ideas.php'); exit; }

$activity  = getActivity($pdo, 15);
$comments  = $pdo->prepare("SELECT cm.*, u.full_name FROM comments cm JOIN users u ON cm.user_id=u.id WHERE cm.idea_id=? ORDER BY cm.created_at DESC LIMIT 10");
$comments->execute([$id]);
$comments  = $comments->fetchAll();
$reviewers = $pdo->query("SELECT id, full_name FROM users WHERE role='admin' ORDER BY full_name")->fetchAll();

// Handle POST status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus  = $_POST['status']     ?? '';
    $assignTo   = (int)($_POST['assigned_to'] ?? 0);
    $notes      = trim($_POST['notes'] ?? '');
    $isFeatured = isset($_POST['featured']) ? 1 : 0;
    if (in_array($newStatus,['pending','review','approved','funded','closed'])) {
        $pdo->prepare("UPDATE ideas SET status=?,assigned_to=?,is_featured=?,updated_at=NOW() WHERE id=?")->execute([$newStatus,$assignTo?:null,$isFeatured,$id]);
        logActivity($pdo,$id,"Status changed to $newStatus".($notes?" — Notes: $notes":''),$user['id'],$newStatus);
        setFlash('success',"Idea status updated to ".ucfirst($newStatus));
        header("Location: admin-review.php?id=$id"); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Review Idea — IdeaMarket Admin</title>
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
      <a href="admin-ideas.php" class="active"><i class="fas fa-lightbulb"></i> Manage Ideas</a>
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
  </aside>
  <main class="main-content">
    <header class="top-header">
      <div style="display:flex;align-items:center;gap:1rem;">
        <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
        <span class="page-title">Review Idea</span>
      </div>
      <div class="header-right">
        <div class="dark-toggle"><i class="fas fa-moon" style="font-size:.8rem;"></i><div class="toggle-switch"></div></div>
        <div class="header-avatar" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);">AD</div>
      </div>
    </header>
    <div class="page-body fade-in">
      <div class="breadcrumb"><a href="admin-ideas.php">Manage Ideas</a><i class="fas fa-chevron-right" style="font-size:.65rem;"></i> Review</div>
      <?= renderFlash() ?>
      <div style="display:grid;grid-template-columns:1fr 320px;gap:1.5rem;align-items:start;margin-top:1rem;">
        <!-- Idea Detail -->
        <div>
          <div class="card mb-3">
            <div style="background:linear-gradient(135deg,var(--navy-dark),var(--navy));padding:2rem;border-radius:var(--radius) var(--radius) 0 0;">
              <div style="display:flex;gap:.6rem;margin-bottom:.85rem;flex-wrap:wrap;">
                <?= statusBadge($idea['status']) ?><?= priorityBadge($idea['priority']) ?>
                <?php if ($idea['is_featured']): ?><span class="badge badge-premium"><i class="fas fa-star"></i> Featured</span><?php endif; ?>
                <span class="badge" style="background:rgba(255,255,255,.12);color:rgba(255,255,255,.8);font-size:.72rem;"><i class="fas <?= e($idea['cat_icon']) ?>"></i> <?= e($idea['cat_name']) ?></span>
              </div>
              <h2 style="font-family:var(--font-head);color:#fff;font-size:1.5rem;margin-bottom:.4rem;"><?= e($idea['title']) ?></h2>
              <?php if ($idea['tagline']): ?><p style="color:rgba(255,255,255,.65);"><?= e($idea['tagline']) ?></p><?php endif; ?>
              <div style="display:flex;gap:1.5rem;margin-top:1rem;flex-wrap:wrap;">
                <span style="color:rgba(255,255,255,.6);font-size:.82rem;"><i class="fas fa-user me-1"></i><?= e($idea['submitter']) ?></span>
                <span style="color:rgba(255,255,255,.6);font-size:.82rem;"><i class="fas fa-calendar me-1"></i><?= formatDate($idea['created_at'],'F j, Y') ?></span>
                <span style="color:rgba(255,255,255,.6);font-size:.82rem;"><i class="fas fa-heart me-1"></i><?= $idea['vote_count'] ?> votes</span>
                <code style="background:rgba(255,255,255,.1);padding:.15rem .5rem;border-radius:5px;font-size:.78rem;color:#fff;"><?= e($idea['idea_id']) ?></code>
              </div>
            </div>
            <div class="card-body">
              <?php foreach ([['Description',$idea['description'],'fa-align-left','blue'],['Problem',$idea['problem'],'fa-exclamation-triangle','orange'],['Solution',$idea['solution'],'fa-lightbulb','green'],['Target Market',$idea['target_market'],'fa-users','purple']] as [$lbl,$txt,$icon,$col]):
                if (!$txt) continue; ?>
                <div style="margin-bottom:1.5rem;">
                  <h5 style="font-family:var(--font-head);font-size:.85rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:.6rem;">
                    <i class="fas <?= $icon ?> me-2" style="color:var(--<?= $col ?>);"></i><?= $lbl ?>
                  </h5>
                  <p style="font-size:.9rem;line-height:1.7;"><?= nl2br(e($txt)) ?></p>
                </div>
              <?php endforeach; ?>
              <?php if ($idea['tags']): ?>
                <div><?php foreach (explode(',',$idea['tags']) as $t): ?>
                  <span style="display:inline-block;background:rgba(37,99,235,.08);color:var(--blue);padding:.2rem .6rem;border-radius:20px;font-size:.75rem;font-weight:600;margin:.2rem .1rem;">#<?= e(trim($t)) ?></span>
                <?php endforeach; ?></div>
              <?php endif; ?>
              <?php if ($idea['funding_goal'] > 0): ?>
                <div style="margin-top:1.25rem;padding:1rem;background:var(--bg);border-radius:var(--radius-sm);">
                  <div style="display:flex;justify-content:space-between;font-size:.85rem;margin-bottom:.4rem;">
                    <span><strong>$<?= number_format($idea['funding_raised']) ?></strong> raised</span>
                    <span>Goal: $<?= number_format($idea['funding_goal']) ?></span>
                  </div>
                  <?php $pct = min(100,round($idea['funding_raised']/$idea['funding_goal']*100)); ?>
                  <div class="progress-bar"><div class="progress-fill" style="width:<?= $pct ?>%;background:var(--blue);"></div></div>
                </div>
              <?php endif; ?>
            </div>
          </div>
          <!-- Comments -->
          <div class="card">
            <div class="card-header"><span class="card-title"><i class="fas fa-comments me-2" style="color:var(--blue)"></i>Comments (<?= count($comments) ?>)</span></div>
            <div class="card-body">
              <?php if ($comments): ?>
                <?php foreach ($comments as $c): ?>
                  <div style="display:flex;gap:.75rem;margin-bottom:1rem;">
                    <div style="width:34px;height:34px;border-radius:9px;background:var(--navy);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.78rem;flex-shrink:0;"><?= strtoupper(substr($c['full_name'],0,2)) ?></div>
                    <div style="background:var(--bg);border-radius:var(--radius-sm);padding:.75rem 1rem;flex:1;">
                      <div style="font-weight:700;font-size:.82rem;"><?= e($c['full_name']) ?> <span style="color:var(--text-muted);font-weight:400;font-size:.72rem;"><?= timeAgo($c['created_at']) ?></span></div>
                      <div style="font-size:.85rem;margin-top:.3rem;"><?= nl2br(e($c['body'])) ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <p style="color:var(--text-muted);font-size:.85rem;">No comments yet.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <!-- Review Actions -->
        <div>
          <div class="card mb-3">
            <div class="card-header"><span class="card-title"><i class="fas fa-gavel me-2" style="color:var(--orange)"></i>Review Actions</span></div>
            <div class="card-body">
              <form method="POST">
                <div class="form-group">
                  <label class="form-label">Update Status</label>
                  <select name="status" class="form-control">
                    <?php foreach (['pending','review','approved','funded','closed'] as $s): ?>
                      <option value="<?= $s ?>" <?= $idea['status']===$s?'selected':'' ?>><?= ucfirst($s === 'review' ? 'Under Review' : $s) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Assign Reviewer</label>
                  <select name="assigned_to" class="form-control">
                    <option value="">— Unassigned —</option>
                    <?php foreach ($reviewers as $r): ?>
                      <option value="<?= $r['id'] ?>" <?= $idea['assigned_to']==$r['id']?'selected':'' ?>><?= e($r['full_name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Review Notes</label>
                  <textarea name="notes" class="form-control" rows="3" placeholder="Optional notes about this decision..."></textarea>
                </div>
                <div class="form-group">
                  <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-size:.85rem;font-weight:500;">
                    <input type="checkbox" name="featured" <?= $idea['is_featured']?'checked':'' ?> style="width:16px;height:16px;">
                    <i class="fas fa-star" style="color:var(--gold);"></i> Mark as Featured
                  </label>
                </div>
                <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
                  <button type="submit" class="btn btn-primary" style="flex:1;"><i class="fas fa-save"></i> Save Changes</button>
                  <a href="admin-ideas.php" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
                </div>
              </form>
            </div>
          </div>
          <!-- Submitter info -->
          <div class="card">
            <div class="card-header"><span class="card-title"><i class="fas fa-user me-2" style="color:var(--blue)"></i>Submitter Info</span></div>
            <div class="card-body">
              <div style="display:flex;align-items:center;gap:.75rem;margin-bottom:1rem;">
                <div style="width:42px;height:42px;border-radius:10px;background:linear-gradient(135deg,var(--navy),var(--blue));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0;"><?= strtoupper(substr($idea['submitter'],0,2)) ?></div>
                <div><div style="font-weight:700;"><?= e($idea['submitter']) ?></div><div style="font-size:.78rem;color:var(--text-muted);"><?= e($idea['submitter_email']) ?></div></div>
              </div>
              <?php $userIdeas = $pdo->prepare("SELECT COUNT(*) FROM ideas WHERE user_id=?"); $userIdeas->execute([$idea['user_id']]); ?>
              <div style="font-size:.82rem;color:var(--text-muted);">Total ideas submitted: <strong style="color:var(--text);"><?= $userIdeas->fetchColumn() ?></strong></div>
              <div style="margin-top:1rem;">
                <a href="track.php?id=<?= e($idea['idea_id']) ?>" class="btn btn-outline btn-sm w-100"><i class="fas fa-search"></i> View Public Track Page</a>
              </div>
            </div>
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
setTimeout(() => { document.querySelectorAll('.progress-fill').forEach(b => { const w=b.style.width; b.style.width='0'; setTimeout(()=>b.style.width=w,100); }); }, 300);
</script>
</body>
</html>
