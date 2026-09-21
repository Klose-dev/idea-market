<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user   = isLoggedIn() ? currentUser() : null;
$ideaId = (int)($_GET['id'] ?? 0);
if (!$ideaId) { header('Location: browse.php'); exit; }

$stmt = $pdo->prepare("
    SELECT i.*, c.name AS cat_name, c.icon AS cat_icon, c.color AS cat_color,
           u.full_name AS submitter, u.email AS submitter_email,
           a.full_name AS assigned_name
    FROM ideas i
    JOIN categories c ON i.category_id = c.id
    JOIN users u ON i.user_id = u.id
    LEFT JOIN users a ON i.assigned_to = a.id
    WHERE i.id = ?
");
$stmt->execute([$ideaId]);
$idea = $stmt->fetch();
if (!$idea) { header('Location: browse.php'); exit; }

// Increment view count
$pdo->prepare("UPDATE ideas SET view_count = view_count+1 WHERE id=?")->execute([$ideaId]);

// Check if user voted
$userVoted = false;
if ($user) {
    $v = $pdo->prepare("SELECT id FROM votes WHERE idea_id=? AND user_id=?");
    $v->execute([$ideaId, $user['id']]);
    $userVoted = (bool)$v->fetch();
}

// Comments
$comments = $pdo->prepare("SELECT cm.*, u.full_name FROM comments cm JOIN users u ON cm.user_id=u.id WHERE cm.idea_id=? AND cm.parent_id IS NULL ORDER BY cm.created_at DESC");
$comments->execute([$ideaId]);
$comments = $comments->fetchAll();

// Handle comment submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user && isset($_POST['comment'])) {
    $body = trim($_POST['comment']);
    if (strlen($body) >= 2) {
        $pdo->prepare("INSERT INTO comments (idea_id, user_id, body, created_at) VALUES (?,?,?,NOW())")->execute([$ideaId, $user['id'], $body]);
        header("Location: idea.php?id=$ideaId#comments");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($idea['title']) ?> — IdeaMarket</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/landing.css">
  <style>
    body { padding-top: 68px; background: var(--bg); }
    .idea-page { max-width: 1100px; margin: 2rem auto; padding: 0 1.5rem 4rem; display: grid; grid-template-columns: 1fr 320px; gap: 2rem; }
    .idea-body h5 { font-family: var(--font-head); font-size: .85rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .06em; margin: 1.5rem 0 .6rem; }
    .idea-body p { font-size: .9rem; line-height: 1.75; color: var(--text); }
    .comment-item { display: flex; gap: .85rem; margin-bottom: 1rem; }
    .comment-avatar { width: 36px; height: 36px; border-radius: 10px; background: linear-gradient(135deg, var(--navy), var(--blue)); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: .8rem; flex-shrink: 0; }
    .comment-body { background: var(--bg); border-radius: var(--radius-sm); padding: .85rem 1rem; flex: 1; }
    .comment-body .comment-author { font-weight: 700; font-size: .82rem; }
    .comment-body .comment-text { font-size: .85rem; color: var(--text); margin-top: .3rem; line-height: 1.5; }
    .comment-body .comment-time { font-size: .72rem; color: var(--text-muted); margin-top: .3rem; }
    @media(max-width:768px){ .idea-page{grid-template-columns:1fr;} }
  </style>
</head>
<body>
<nav class="landing-nav scrolled" style="position:fixed;">
  <div class="nav-inner">
    <a href="index.php" class="nav-brand"><div class="logo-box"><i class="fas fa-lightbulb"></i></div>IdeaMarket</a>
    <div class="nav-links">
      <a href="index.php">Home</a><a href="browse.php">Browse Ideas</a><a href="track.php">Track Idea</a>
    </div>
    <div class="nav-right">
      <div class="dark-toggle-land"><i class="fas fa-sun"></i><div class="toggle-pill" id="darkPill"></div><i class="fas fa-moon"></i></div>
      <?php if($user): ?><a href="<?=$user['role']==='admin'?'admin.php':'dashboard.php'?>" class="btn btn-primary btn-sm"><i class="fas fa-th-large"></i> Dashboard</a>
      <?php else: ?><a href="login.php" class="btn btn-outline btn-sm">Log In</a><a href="register.php" class="btn btn-primary btn-sm">Get Started</a><?php endif; ?>
    </div>
  </div>
</nav>

<!-- Breadcrumb -->
<div style="background:var(--card);border-bottom:1px solid var(--border);padding:.75rem 2rem;">
  <div style="max-width:1100px;margin:0 auto;display:flex;align-items:center;gap:.5rem;font-size:.82rem;color:var(--text-muted);">
    <a href="index.php" style="color:var(--text-muted);">Home</a>
    <i class="fas fa-chevron-right" style="font-size:.6rem;"></i>
    <a href="browse.php" style="color:var(--text-muted);">Browse Ideas</a>
    <i class="fas fa-chevron-right" style="font-size:.6rem;"></i>
    <span style="color:var(--text);font-weight:600;"><?= e(substr($idea['title'],0,40)) ?>…</span>
  </div>
</div>

<div class="idea-page">
  <!-- Main -->
  <div>
    <!-- Title Card -->
    <div class="card mb-3 fade-up">
      <div style="background:linear-gradient(135deg,var(--navy-dark),var(--navy));padding:2.5rem;border-radius:var(--radius) var(--radius) 0 0;position:relative;overflow:hidden;">
        <div style="position:absolute;top:-30px;right:-30px;width:200px;height:200px;background:rgba(255,255,255,.04);border-radius:50%;"></div>
        <div style="display:flex;align-items:center;gap:.6rem;margin-bottom:1rem;flex-wrap:wrap;">
          <?= statusBadge($idea['status']) ?>
          <?= priorityBadge($idea['priority']) ?>
          <span class="badge" style="background:rgba(255,255,255,.12);color:rgba(255,255,255,.8);font-size:.72rem;">
            <i class="fas <?= e($idea['cat_icon']) ?>"></i> <?= e($idea['cat_name']) ?>
          </span>
          <?php if ($idea['is_featured']): ?><span class="badge badge-premium"><i class="fas fa-star"></i> Featured</span><?php endif; ?>
        </div>
        <h1 style="color:#fff;font-size:1.8rem;margin-bottom:.5rem;"><?= e($idea['title']) ?></h1>
        <?php if ($idea['tagline']): ?>
          <p style="color:rgba(255,255,255,.65);font-size:1rem;"><?= e($idea['tagline']) ?></p>
        <?php endif; ?>
        <div style="display:flex;gap:1.5rem;margin-top:1.25rem;flex-wrap:wrap;">
          <span style="color:rgba(255,255,255,.6);font-size:.82rem;"><i class="fas fa-user" style="margin-right:.4rem;"></i><?= e($idea['submitter']) ?></span>
          <span style="color:rgba(255,255,255,.6);font-size:.82rem;"><i class="fas fa-calendar" style="margin-right:.4rem;"></i><?= formatDate($idea['created_at'],'F j, Y') ?></span>
          <span style="color:rgba(255,255,255,.6);font-size:.82rem;"><i class="fas fa-eye" style="margin-right:.4rem;"></i><?= number_format($idea['view_count']) ?> views</span>
          <span style="color:rgba(255,255,255,.6);font-size:.82rem;"><code style="background:rgba(255,255,255,.1);padding:.1rem .5rem;border-radius:4px;"><?= e($idea['idea_id']) ?></code></span>
        </div>
      </div>
    </div>

    <!-- Details -->
    <div class="card mb-3 fade-up">
      <div class="card-body idea-body">
        <h5><i class="fas fa-align-left me-2" style="color:var(--blue);"></i>Description</h5>
        <p><?= nl2br(e($idea['description'])) ?></p>
        <?php if ($idea['problem']): ?>
          <h5><i class="fas fa-exclamation-triangle me-2" style="color:var(--orange);"></i>Problem</h5>
          <p><?= nl2br(e($idea['problem'])) ?></p>
        <?php endif; ?>
        <?php if ($idea['solution']): ?>
          <h5><i class="fas fa-lightbulb me-2" style="color:var(--green);"></i>Solution</h5>
          <p><?= nl2br(e($idea['solution'])) ?></p>
        <?php endif; ?>
        <?php if ($idea['target_market']): ?>
          <h5><i class="fas fa-users me-2" style="color:var(--purple);"></i>Target Market</h5>
          <p><?= nl2br(e($idea['target_market'])) ?></p>
        <?php endif; ?>
        <?php if ($idea['tags']): ?>
          <h5><i class="fas fa-tags me-2" style="color:var(--text-muted);"></i>Tags</h5>
          <div><?php foreach (explode(',', $idea['tags']) as $t): ?>
            <span style="display:inline-block;background:rgba(37,99,235,.08);color:var(--blue);padding:.2rem .65rem;border-radius:20px;font-size:.75rem;font-weight:600;margin:.2rem .15rem;">#<?= e(trim($t)) ?></span>
          <?php endforeach; ?></div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Comments -->
    <div class="card fade-up" id="comments">
      <div class="card-header">
        <span class="card-title"><i class="fas fa-comments me-2" style="color:var(--blue);"></i>Discussion (<?= count($comments) ?>)</span>
      </div>
      <div class="card-body">
        <?php if ($user): ?>
          <form method="POST" class="mb-3">
            <div class="form-group">
              <textarea name="comment" class="form-control" rows="3" placeholder="Share your thoughts on this idea..." required></textarea>
            </div>
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-paper-plane"></i> Post Comment</button>
          </form>
        <?php else: ?>
          <div class="alert alert-info mb-3"><i class="fas fa-info-circle"></i> <a href="login.php" style="color:var(--blue);">Log in</a> to join the discussion.</div>
        <?php endif; ?>
        <?php if ($comments): ?>
          <?php foreach ($comments as $c): ?>
            <div class="comment-item">
              <div class="comment-avatar"><?= strtoupper(substr($c['full_name'],0,2)) ?></div>
              <div class="comment-body">
                <div class="comment-author"><?= e($c['full_name']) ?></div>
                <div class="comment-text"><?= nl2br(e($c['body'])) ?></div>
                <div class="comment-time"><i class="fas fa-clock" style="margin-right:.2rem;"></i><?= timeAgo($c['created_at']) ?></div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="empty-state" style="padding:2rem;"><i class="fas fa-comment-slash"></i><p>No comments yet. Be the first to share your thoughts!</p></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Sidebar -->
  <div>
    <!-- Vote Card -->
    <div class="card mb-3 fade-up">
      <div class="card-body text-center" style="padding:2rem;">
        <div style="font-size:3rem;margin-bottom:.5rem;">💡</div>
        <div style="font-size:2.5rem;font-weight:400;color:var(--red);"><?= number_format($idea['vote_count']) ?></div>
        <div style="font-size:.82rem;color:var(--text-muted);margin-bottom:1.25rem;">Community Votes</div>
        <button class="idea-vote-btn w-100 <?= $userVoted?'voted':'' ?>" style="justify-content:center;padding:.65rem 1rem;font-size:.9rem;"
                onclick="voteIdea(<?= $ideaId ?>, this)">
          <i class="fas fa-heart"></i>
          <span class="vote-count"><?= $idea['vote_count'] ?></span>
          <?= $userVoted ? 'Voted ✓' : 'Support This Idea' ?>
        </button>
        <?php if (!$user): ?>
          <p style="font-size:.75rem;color:var(--text-muted);margin-top:.5rem;"><a href="login.php" style="color:var(--blue);">Log in</a> to vote</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Meta Card -->
    <div class="card mb-3 fade-up">
      <div class="card-header"><span class="card-title">Idea Details</span></div>
      <div class="card-body" style="padding:1.25rem;">
        <?php
          $metas = [
            ['fa-tag','Category', $idea['cat_name']],
            ['fa-user','Submitted By', $idea['submitter']],
            ['fa-calendar','Date', formatDate($idea['created_at'],'F j, Y')],
            ['fa-barcode','Idea ID', $idea['idea_id']],
          ];
          if ($idea['assigned_name']) $metas[] = ['fa-user-check','Reviewer', $idea['assigned_name']];
          foreach ($metas as [$icon,$label,$val]):
        ?>
          <div style="display:flex;justify-content:space-between;align-items:center;padding:.6rem 0;border-bottom:1px solid var(--border);font-size:.82rem;">
            <span style="color:var(--text-muted);display:flex;align-items:center;gap:.4rem;"><i class="fas <?=$icon?>"></i><?=$label?></span>
            <span style="font-weight:600;text-align:right;"><?=e($val)?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Funding -->
    <?php if ($idea['funding_goal'] > 0): ?>
      <?php $pct = min(100, round($idea['funding_raised']/$idea['funding_goal']*100)); ?>
      <div class="card mb-3 fade-up">
        <div class="card-header"><span class="card-title"><i class="fas fa-hand-holding-usd me-2" style="color:var(--green);"></i>Funding</span></div>
        <div class="card-body">
          <div style="text-align:center;margin-bottom:1rem;">
            <div style="font-size:1.8rem;font-weight:400;color:var(--green);">$<?= number_format($idea['funding_raised']) ?></div>
            <div style="font-size:.78rem;color:var(--text-muted);">of $<?= number_format($idea['funding_goal']) ?> goal</div>
          </div>
          <div class="progress-bar" style="height:10px;margin-bottom:.5rem;">
            <div class="progress-fill" style="width:<?=$pct?>%;background:<?=$pct>=100?'var(--green)':'var(--blue)'?>;"></div>
          </div>
          <div style="text-align:center;font-size:.8rem;font-weight:700;color:var(--blue);"><?=$pct?>% Funded</div>
          <?php if ($user && $user['role']==='investor' && $idea['status']==='approved'): ?>
            <button class="btn btn-success w-100 mt-2" onclick="Modal.open('investModal')"><i class="fas fa-hand-holding-usd"></i> Invest Now</button>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <!-- Actions -->
    <div class="card fade-up">
      <div class="card-body" style="display:flex;flex-direction:column;gap:.6rem;">
        <a href="track.php?id=<?= e($idea['idea_id']) ?>" class="btn btn-outline w-100"><i class="fas fa-search"></i> Track This Idea</a>
        <a href="browse.php" class="btn btn-outline w-100"><i class="fas fa-compass"></i> Browse More Ideas</a>
        <?php if ($user && $user['id'] == ($idea['user_id']??0)): ?>
          <a href="my-ideas.php" class="btn btn-primary w-100"><i class="fas fa-edit"></i> My Ideas</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<footer style="background:var(--navy-dark);color:rgba(255,255,255,.5);text-align:center;padding:2rem;font-size:.82rem;">
  &copy; <?= date('Y') ?> IdeaMarket &nbsp;·&nbsp; <a href="index.php" style="color:#93c5fd;">Home</a> &nbsp;·&nbsp; <a href="browse.php" style="color:#93c5fd;">Browse Ideas</a>
</footer>

<div class="spinner-overlay"><div class="spinner"></div></div>
<script src="assets/js/app.js"></script>
<script src="assets/js/realtime.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
  const pill = document.getElementById('darkPill');
  if (pill) {
    pill.classList.toggle('on', document.body.classList.contains('dark-mode'));
    pill.addEventListener('click', () => {
      DarkMode.toggle();
      pill.classList.toggle('on', document.body.classList.contains('dark-mode'));
    });
  }
  setTimeout(() => {
    document.querySelectorAll('.progress-fill').forEach(b => {
      const w = b.style.width; b.style.width = '0'; setTimeout(() => b.style.width = w, 100);
    });
  }, 400);
});
</script>
</body>
</html>
