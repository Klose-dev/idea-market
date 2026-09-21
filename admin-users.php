<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireAdmin();

$user   = currentUser();
$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid    = (int)$_POST['user_id'];
    $action = $_POST['action'];
    if ($action === 'toggle_ban') {
        $stmt = $pdo->prepare("UPDATE users SET is_banned = NOT is_banned WHERE id=? AND id != ?");
        $stmt->execute([$uid, $_SESSION['user_id']]);
        jsonResponse(['success' => true]);
    }
    if ($action === 'change_role') {
        $role = in_array($_POST['role'],['user','admin','investor']) ? $_POST['role'] : 'user';
        $pdo->prepare("UPDATE users SET role=? WHERE id=? AND id != ?")->execute([$role, $uid, $_SESSION['user_id']]);
        jsonResponse(['success' => true]);
    }
}

$where  = $search ? "WHERE full_name LIKE ? OR email LIKE ?" : '';
$params = $search ? ["%$search%", "%$search%"] : [];

$total    = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$total->execute($params);
$totalCount = $total->fetchColumn();
$pg = paginate($totalCount, $perPage, $page);

$stmt = $pdo->prepare("
  SELECT u.*, (SELECT COUNT(*) FROM ideas WHERE user_id=u.id) AS idea_count,
         (SELECT COUNT(*) FROM votes WHERE user_id=u.id) AS votes_cast
  FROM users u $where ORDER BY u.created_at DESC LIMIT $perPage OFFSET {$pg['offset']}
");
$stmt->execute($params);
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Users — IdeaMarket Admin</title>
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
      <a href="admin-users.php" class="active"><i class="fas fa-users"></i> Users</a>
      <a href="admin-investments.php"><i class="fas fa-hand-holding-usd"></i> Investments</a>
      <span class="nav-section-label" style="margin-top:.5rem;">Analytics</span>
      <a href="admin-reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
      <span class="nav-section-label" style="margin-top:.5rem;">System</span>
      <a href="admin-settings.php"><i class="fas fa-cog"></i> Settings</a>
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
    </nav>
  </aside>
  <main class="main-content">
    <header class="top-header">
      <div style="display:flex;align-items:center;gap:1rem;">
        <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
        <span class="page-title">Manage Users</span>
      </div>
      <div class="header-right">
        <div class="dark-toggle"><i class="fas fa-moon" style="font-size:.8rem;"></i><div class="toggle-switch"></div></div>
        <div class="header-avatar" style="background:linear-gradient(135deg,#2563eb,#1d4ed8);">AD</div>
      </div>
    </header>

    <div class="page-body fade-in">
      <div class="page-header">
        <h1>Users <span style="font-size:1.4rem;">👥</span></h1>
        <p>Manage platform members, roles, and access.</p>
      </div>

      <!-- Filter -->
      <div class="filter-bar">
        <form method="GET" style="display:contents;">
          <div class="search-input-wrap">
            <i class="fas fa-search"></i>
            <input type="text" name="q" class="form-control" placeholder="Search by name or email..." value="<?= e($search) ?>">
          </div>
          <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
          <?php if ($search): ?><a href="admin-users.php" class="btn btn-outline">Clear</a><?php endif; ?>
        </form>
        <span style="margin-left:auto;font-size:.82rem;color:var(--text-muted);"><?= $totalCount ?> users</span>
      </div>

      <div class="card">
        <div class="card-header">
          <span class="card-title"><i class="fas fa-users me-2" style="color:var(--blue)"></i>All Members</span>
        </div>
        <div class="table-wrapper">
          <table id="usersTable">
            <thead>
              <tr><th>#</th><th>User</th><th>Email</th><th>Role</th><th>Ideas</th><th>Votes Cast</th><th>Joined</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
                <tr id="user-row-<?= $u['id'] ?>">
                  <td style="color:var(--text-muted);font-size:.8rem;"><?= $u['id'] ?></td>
                  <td>
                    <div style="display:flex;align-items:center;gap:.65rem;">
                      <div style="width:34px;height:34px;border-radius:8px;background:linear-gradient(135deg,var(--navy),var(--blue));display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.8rem;flex-shrink:0;">
                        <?= strtoupper(substr($u['full_name'],0,2)) ?>
                      </div>
                      <strong style="font-size:.875rem;"><?= e($u['full_name']) ?></strong>
                    </div>
                  </td>
                  <td style="font-size:.82rem;color:var(--text-muted);"><?= e($u['email']) ?></td>
                  <td>
                    <select class="form-control" style="padding:.3rem .6rem;font-size:.78rem;min-width:100px;"
                            onchange="changeRole(<?= $u['id'] ?>, this.value)"
                            <?= $u['id'] == $_SESSION['user_id'] ? 'disabled' : '' ?>>
                      <option value="user"     <?= $u['role']==='user'?'selected':'' ?>>User</option>
                      <option value="admin"    <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
                      <option value="investor" <?= $u['role']==='investor'?'selected':'' ?>>Investor</option>
                    </select>
                  </td>
                  <td style="text-align:center;"><span class="badge badge-review"><?= $u['idea_count'] ?></span></td>
                  <td style="text-align:center;"><i class="fas fa-heart" style="color:var(--red);font-size:.8rem;margin-right:.2rem;"></i><?= $u['votes_cast'] ?></td>
                  <td style="font-size:.78rem;color:var(--text-muted);"><?= formatDate($u['created_at'],'M j, Y') ?></td>
                  <td>
                    <?php if ($u['is_banned']): ?>
                      <span class="badge badge-closed"><i class="fas fa-ban"></i> Banned</span>
                    <?php else: ?>
                      <span class="badge badge-approved"><i class="fas fa-check"></i> Active</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($u['id'] != $_SESSION['user_id']): ?>
                      <button class="btn <?= $u['is_banned']?'btn-success':'btn-danger' ?> btn-sm"
                              onclick="toggleBan(<?= $u['id'] ?>, <?= $u['is_banned']?'0':'1' ?>)"
                              id="ban-btn-<?= $u['id'] ?>">
                        <i class="fas <?= $u['is_banned']?'fa-check-circle':'fa-ban' ?>"></i>
                        <?= $u['is_banned']?'Unban':'Ban' ?>
                      </button>
                    <?php else: ?>
                      <span style="font-size:.75rem;color:var(--text-muted);">— You —</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
        <?php if ($pg['total_pages'] > 1): ?>
          <div class="pagination" style="padding:1rem;">
            <?php for ($p=1;$p<=$pg['total_pages'];$p++): ?>
              <a href="admin-users.php?page=<?=$p?>&q=<?=e($search)?>" class="page-btn <?=$p===$page?'active':''?>"><?=$p?></a>
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
async function toggleBan(uid, ban) {
  const res  = await fetch('admin-users.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`action=toggle_ban&user_id=${uid}` });
  const data = await res.json();
  if (data.success) {
    const btn = document.getElementById('ban-btn-' + uid);
    if (ban) {
      btn.className = 'btn btn-success btn-sm';
      btn.innerHTML = '<i class="fas fa-check-circle"></i> Unban';
      btn.setAttribute('onclick', `toggleBan(${uid}, 0)`);
      Toast.show('User banned', 'warning');
    } else {
      btn.className = 'btn btn-danger btn-sm';
      btn.innerHTML = '<i class="fas fa-ban"></i> Ban';
      btn.setAttribute('onclick', `toggleBan(${uid}, 1)`);
      Toast.show('User unbanned', 'success');
    }
  }
}
async function changeRole(uid, role) {
  await fetch('admin-users.php', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:`action=change_role&user_id=${uid}&role=${role}` });
  Toast.show('Role updated to ' + role, 'success');
}
</script>
</body>
</html>
