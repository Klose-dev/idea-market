<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();
$user = currentUser();

// Mark all as read
if (isset($_GET['mark_all'])) {
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$user['id']]);
    header('Location: notifications.php'); exit;
}
// Mark single as read
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['mark_read'])) {
    $nid = (int)$_POST['notif_id'];
    $pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?")->execute([$nid,$user['id']]);
    jsonResponse(['success'=>true]);
}

$page    = max(1,(int)($_GET['page']??1));
$perPage = 20;
$total   = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=?");
$total->execute([$user['id']]);
$totalCount = $total->fetchColumn();
$pg = paginate($totalCount,$perPage,$page);

$stmt = $pdo->prepare("SELECT n.*, i.title AS idea_title, i.id AS idea_db_id FROM notifications n LEFT JOIN ideas i ON n.idea_id=i.id WHERE n.user_id=? ORDER BY n.created_at DESC LIMIT $perPage OFFSET {$pg['offset']}");
$stmt->execute([$user['id']]);
$notifs = $stmt->fetchAll();

$unread = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
$unread->execute([$user['id']]); $unreadCount = $unread->fetchColumn();

$typeIcons = ['status_change'=>['fa-sync-alt','blue'],'vote'=>['fa-heart','red'],'comment'=>['fa-comment','green'],'approval'=>['fa-check-circle','green'],'rejection'=>['fa-times-circle','red'],'funded'=>['fa-rocket','purple']];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications — IdeaMarket</title>
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
      <a href="my-ideas.php"><i class="fas fa-lightbulb"></i> My Ideas</a>
      <a href="browse.php"><i class="fas fa-compass"></i> Browse Market</a>
      <a href="track.php"><i class="fas fa-search"></i> Track an Idea</a>
      <span class="nav-section-label" style="margin-top:.5rem;">Account</span>
      <a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
      <a href="notifications.php" class="active"><i class="fas fa-bell"></i> Notifications <?php if($unreadCount>0): ?><span class="badge-count"><?= $unreadCount ?></span><?php endif; ?></a>
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
    </nav>
  </aside>
  <main class="main-content">
    <header class="top-header">
      <div style="display:flex;align-items:center;gap:1rem;">
        <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
        <span class="page-title">Notifications</span>
      </div>
      <div class="header-right">
        <div class="dark-toggle"><i class="fas fa-moon" style="font-size:.8rem;"></i><div class="toggle-switch"></div></div>
        <div class="header-avatar"><?= strtoupper(substr($user['name'],0,2)) ?></div>
      </div>
    </header>
    <div class="page-body fade-in">
      <div class="page-header">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
          <div>
            <h1>Notifications 🔔</h1>
            <p><?= $unreadCount > 0 ? "<strong style='color:var(--orange)'>$unreadCount unread</strong> notification".($unreadCount>1?'s':'') : 'All notifications read' ?></p>
          </div>
          <?php if ($unreadCount > 0): ?>
            <a href="notifications.php?mark_all=1" class="btn btn-outline btn-sm"><i class="fas fa-check-double"></i> Mark All as Read</a>
          <?php endif; ?>
        </div>
      </div>

      <div class="card">
        <?php if ($notifs): ?>
          <?php foreach ($notifs as $n):
            [$icon,$color] = $typeIcons[$n['type']] ?? ['fa-bell','blue'];
            $isUnread = !(bool)$n['is_read'];
          ?>
            <div id="notif-<?= $n['id'] ?>" style="display:flex;align-items:flex-start;gap:1rem;padding:1.1rem 1.5rem;border-bottom:1px solid var(--border);<?= $isUnread?'background:rgba(37,99,235,.03);':'' ?> transition:background .3s;">
              <div style="width:38px;height:38px;border-radius:10px;background:rgba(0,0,0,.04);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                <i class="fas <?= $icon ?>" style="color:var(--<?= $color ?>);font-size:1rem;"></i>
              </div>
              <div style="flex:1;min-width:0;">
                <p style="font-size:.875rem;color:var(--text);line-height:1.5;margin-bottom:.25rem;">
                  <?php if ($isUnread): ?><span style="display:inline-block;width:8px;height:8px;background:var(--blue);border-radius:50%;margin-right:.4rem;vertical-align:middle;"></span><?php endif; ?>
                  <?= e($n['message']) ?>
                </p>
                <div style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                  <span style="font-size:.75rem;color:var(--text-muted);"><i class="fas fa-clock me-1"></i><?= timeAgo($n['created_at']) ?></span>
                  <?php if ($n['idea_title']): ?>
                    <a href="idea.php?id=<?= $n['idea_db_id'] ?>" style="font-size:.75rem;color:var(--blue);font-weight:600;"><i class="fas fa-lightbulb me-1"></i><?= e(substr($n['idea_title'],0,30)) ?><?= strlen($n['idea_title'])>30?'…':'' ?></a>
                  <?php endif; ?>
                </div>
              </div>
              <?php if ($isUnread): ?>
                <button class="btn btn-outline btn-sm" style="flex-shrink:0;" onclick="markRead(<?= $n['id'] ?>)">
                  <i class="fas fa-check"></i>
                </button>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
          <?php if ($pg['total_pages'] > 1): ?>
            <div class="pagination" style="padding:1rem;">
              <?php for ($p=1;$p<=$pg['total_pages'];$p++): ?>
                <a href="notifications.php?page=<?=$p?>" class="page-btn <?=$p===$page?'active':''?>"><?=$p?></a>
              <?php endfor; ?>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="empty-state" style="padding:4rem;">
            <i class="fas fa-bell-slash"></i>
            <h5>No notifications yet</h5>
            <p>You will receive notifications when your idea status changes or you get community votes.</p>
            <a href="submit-idea.php" class="btn btn-primary mt-3"><i class="fas fa-lightbulb"></i> Submit an Idea</a>
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
async function markRead(id) {
  const res  = await fetch('notifications.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`mark_read=1&notif_id=${id}` });
  const data = await res.json();
  if (data.success) {
    const el = document.getElementById('notif-'+id);
    if (el) { el.style.background=''; el.querySelector('button')?.remove(); el.querySelector('span[style*="border-radius:50%"]')?.remove(); }
    Toast.show('Marked as read', 'success', 2000);
  }
}
</script>
</body>
</html>
