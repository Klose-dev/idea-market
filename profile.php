<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();
$user  = currentUser();
$flash = null;

// Load full profile from DB
$profileStmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
$profileStmt->execute([$user['id']]);
$profile = $profileStmt->fetch();

$stats = getUserStats($pdo, $user['id']);
$votesCast = $pdo->prepare("SELECT COUNT(*) FROM votes WHERE user_id=?"); $votesCast->execute([$user['id']]); $votesCast = $votesCast->fetchColumn();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'update_profile') {
        $name = trim($_POST['full_name'] ?? '');
        $bio  = trim($_POST['bio']       ?? '');
        if (strlen($name) < 2) { $flash = ['type'=>'error','msg'=>'Name must be at least 2 characters.']; }
        else {
            $pdo->prepare("UPDATE users SET full_name=?, bio=? WHERE id=?")->execute([$name, $bio, $user['id']]);
            $_SESSION['user_name'] = $name;
            $flash = ['type'=>'success','msg'=>'Profile updated successfully.'];
            $profile['full_name'] = $name;
            $profile['bio'] = $bio;
        }
    }
    if ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!password_verify($current, $profile['password_hash'])) {
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Profile — IdeaMarket</title>
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
      <a href="profile.php" class="active"><i class="fas fa-user-circle"></i> My Profile</a>
      <a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a>
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
    </nav>
  </aside>
  <main class="main-content">
    <header class="top-header">
      <div style="display:flex;align-items:center;gap:1rem;">
        <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
        <span class="page-title">My Profile</span>
      </div>
      <div class="header-right">
        <div class="dark-toggle"><i class="fas fa-moon" style="font-size:.8rem;"></i><div class="toggle-switch"></div></div>
        <div class="header-avatar"><?= strtoupper(substr($user['name'],0,2)) ?></div>
      </div>
    </header>
    <div class="page-body fade-in">
      <div class="page-header">
        <div class="breadcrumb"><a href="dashboard.php">Dashboard</a><i class="fas fa-chevron-right" style="font-size:.65rem;"></i> My Profile</div>
        <h1>My Profile 👤</h1>
        <p>Manage your account details and password.</p>
      </div>
      <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><i class="fas fa-<?= $flash['type']==='success'?'check-circle':'exclamation-circle' ?>"></i> <?= e($flash['msg']) ?></div>
      <?php endif; ?>

      <div style="display:grid;grid-template-columns:300px 1fr;gap:1.5rem;align-items:start;">

        <!-- Profile Card -->
        <div class="card">
          <div style="background:linear-gradient(135deg,var(--navy-dark),var(--navy));padding:2rem;text-align:center;border-radius:var(--radius) var(--radius) 0 0;">
            <div style="width:72px;height:72px;background:linear-gradient(135deg,#f97316,#f59e0b);border-radius:18px;display:flex;align-items:center;justify-content:center;color:#fff;font-family:var(--font-head);font-size:1.6rem;font-weight:800;margin:0 auto 1rem;">
              <?= strtoupper(substr($profile['full_name'],0,2)) ?>
            </div>
            <h3 style="color:#fff;font-family:var(--font-head);font-size:1.1rem;margin-bottom:.25rem;"><?= e($profile['full_name']) ?></h3>
            <p style="color:rgba(255,255,255,.55);font-size:.8rem;"><?= e($profile['email']) ?></p>
            <span class="badge" style="background:rgba(255,255,255,.15);color:rgba(255,255,255,.85);margin-top:.5rem;"><i class="fas fa-user-circle me-1"></i><?= ucfirst($profile['role']) ?></span>
          </div>
          <div class="card-body">
            <div style="display:flex;flex-direction:column;gap:.6rem;">
              <?php
                $profileStats = [
                  ['fas fa-lightbulb','blue','Ideas Submitted',$stats['total']],
                  ['fas fa-check-circle','green','Approved Ideas',$stats['approved']],
                  ['fas fa-rocket','purple','Funded Ideas',$stats['funded']],
                  ['fas fa-heart','red','Votes Cast',$votesCast],
                  ['fas fa-calendar','orange','Member Since',formatDate($profile['created_at'],'M Y')],
                ];
              ?>
              <?php foreach ($profileStats as [$icon,$color,$label,$val]): ?>
                <div style="display:flex;align-items:center;justify-content:space-between;padding:.5rem .75rem;background:var(--bg);border-radius:var(--radius-sm);">
                  <span style="font-size:.82rem;color:var(--text-muted);display:flex;align-items:center;gap:.5rem;">
                    <i class="<?= $icon ?>" style="color:var(--<?= $color ?>);width:16px;text-align:center;"></i><?= $label ?>
                  </span>
                  <strong style="font-size:.85rem;"><?= $val ?></strong>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Edit Forms -->
        <div style="display:flex;flex-direction:column;gap:1.25rem;">

          <!-- Edit Profile -->
          <div class="card">
            <div class="card-header"><span class="card-title"><i class="fas fa-edit me-2" style="color:var(--blue)"></i>Edit Profile</span></div>
            <div class="card-body">
              <form method="POST">
                <input type="hidden" name="action" value="update_profile">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                  <div class="form-group">
                    <label class="form-label">Full Name <span style="color:var(--red)">*</span></label>
                    <input type="text" name="full_name" class="form-control" value="<?= e($profile['full_name']) ?>" required>
                  </div>
                  <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-control" value="<?= e($profile['email']) ?>" disabled style="opacity:.6;cursor:not-allowed;">
                    <small style="color:var(--text-muted);font-size:.73rem;">Email cannot be changed. Contact support.</small>
                  </div>
                </div>
                <div class="form-group">
                  <label class="form-label">Bio</label>
                  <textarea name="bio" class="form-control" rows="3" placeholder="Tell the community about yourself and your ideas..."><?= e($profile['bio'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Profile</button>
              </form>
            </div>
          </div>

          <!-- Change Password -->
          <div class="card">
            <div class="card-header"><span class="card-title"><i class="fas fa-lock me-2" style="color:var(--orange)"></i>Change Password</span></div>
            <div class="card-body">
              <form method="POST">
                <input type="hidden" name="action" value="change_password">
                <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:1rem;">
                  <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" placeholder="••••••••" required>
                  </div>
                  <div class="form-group">
                    <label class="form-label">New Password <span style="font-weight:400;font-size:.75rem;color:var(--text-muted)">(min. 8 chars)</span></label>
                    <input type="password" name="new_password" class="form-control" placeholder="••••••••" required minlength="8">
                  </div>
                  <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="••••••••" required>
                  </div>
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Update Password</button>
              </form>
            </div>
          </div>

          <!-- Account Info -->
          <div class="card">
            <div class="card-header"><span class="card-title"><i class="fas fa-info-circle me-2" style="color:var(--green)"></i>Account Information</span></div>
            <div class="card-body">
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                <?php
                  $infos = [
                    ['Account ID',    '#'.$profile['id']],
                    ['Role',          ucfirst($profile['role'])],
                    ['Status',        $profile['is_banned'] ? '⛔ Banned' : '✅ Active'],
                    ['Last Login',    $profile['last_login'] ? formatDateTime($profile['last_login']) : 'Never'],
                    ['Member Since',  formatDate($profile['created_at'],'F j, Y')],
                    ['Timezone',      TZ_LABEL],
                  ];
                ?>
                <?php foreach ($infos as [$label,$val]): ?>
                  <div style="background:var(--bg);border-radius:var(--radius-sm);padding:.75rem 1rem;">
                    <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;color:var(--text-muted);margin-bottom:.25rem;"><?= $label ?></div>
                    <div style="font-size:.875rem;font-weight:600;"><?= e($val) ?></div>
                  </div>
                <?php endforeach; ?>
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
</body>
</html>
