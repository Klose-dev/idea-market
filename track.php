<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user   = isLoggedIn() ? currentUser() : null;
$idea   = null;
$error  = null;
$ideaId = trim($_GET['id'] ?? $_POST['idea_id'] ?? '');

if ($ideaId) {
    $stmt = $pdo->prepare("
        SELECT i.*, c.name AS cat_name, c.icon AS cat_icon, c.color AS cat_color,
               u.full_name AS submitter, a.full_name AS assigned_name
        FROM ideas i
        JOIN categories c ON i.category_id = c.id
        JOIN users u ON i.user_id = u.id
        LEFT JOIN users a ON i.assigned_to = a.id
        WHERE i.idea_id = ?
        LIMIT 1
    ");
    $stmt->execute([strtoupper($ideaId)]);
    $idea = $stmt->fetch();
    if (!$idea) $error = "No idea found with ID <strong>" . e($ideaId) . "</strong>. Please check and try again.";

    // Fetch activity for this idea
    if ($idea) {
        $actStmt = $pdo->prepare("SELECT al.*, u.full_name FROM activity_log al JOIN users u ON al.performed_by=u.id WHERE al.idea_id=? ORDER BY al.timestamp DESC LIMIT 20");
        $actStmt->execute([$idea['id']]);
        $timeline = $actStmt->fetchAll();
    }
}

$statusSteps = ['pending','review','approved','funded'];
$currentStep = array_search($idea['status'] ?? 'pending', $statusSteps);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Track Idea — IdeaMarket</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/landing.css">
  <style>
    body { padding-top: 68px; background: var(--bg); }
    .track-hero { background: linear-gradient(135deg, var(--navy-dark), var(--navy)); padding: 4rem 2rem; text-align: center; }
    .track-hero h1 { color: #fff; font-family: var(--font-head); font-size: 2.2rem; margin-bottom: .5rem; }
    .track-hero p { color: rgba(255,255,255,.6); margin-bottom: 2rem; }
    .track-form { display: flex; gap: 1rem; justify-content: center; max-width: 520px; margin: 0 auto; }
    .track-form input { flex: 1; background: rgba(255,255,255,.1); border: 2px solid rgba(255,255,255,.2); color: #fff; border-radius: var(--radius-sm); padding: .75rem 1.25rem; font-size: 1rem; font-family: var(--font-body); }
    .track-form input::placeholder { color: rgba(255,255,255,.5); }
    .track-form input:focus { outline: none; border-color: rgba(255,255,255,.5); background: rgba(255,255,255,.15); }
    .track-container { max-width: 900px; margin: 2rem auto; padding: 0 1.5rem 4rem; }

    /* Progress Stepper */
    .status-stepper { display: flex; align-items: center; margin: 2rem 0; }
    .step { display: flex; flex-direction: column; align-items: center; flex: 1; position: relative; }
    .step:not(:last-child)::after { content: ''; position: absolute; top: 20px; left: 50%; width: 100%; height: 3px; background: var(--border); z-index: 0; }
    .step.done:not(:last-child)::after, .step.active:not(:last-child)::after { background: var(--blue); }
    .step-dot { width: 42px; height: 42px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1rem; font-weight: 700; z-index: 1; border: 3px solid var(--border); background: var(--card); color: var(--text-muted); transition: var(--transition); }
    .step.done .step-dot { background: var(--blue); border-color: var(--blue); color: #fff; }
    .step.active .step-dot { background: var(--orange); border-color: var(--orange); color: #fff; box-shadow: 0 0 0 6px rgba(249,115,22,.15); }
    .step-label { font-size: .72rem; font-weight: 600; text-align: center; margin-top: .5rem; color: var(--text-muted); max-width: 80px; line-height: 1.3; }
    .step.done .step-label, .step.active .step-label { color: var(--text); font-weight: 700; }

    /* Idea Detail Card */
    .idea-detail-grid { display: grid; grid-template-columns: 1fr 320px; gap: 1.5rem; }
    .detail-section { margin-bottom: 1.5rem; }
    .detail-section h5 { font-family: var(--font-head); font-size: .9rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .06em; margin-bottom: .75rem; }
    .detail-section p { font-size: .9rem; line-height: 1.7; color: var(--text); }
    .meta-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
    .meta-item { background: var(--bg); border-radius: var(--radius-sm); padding: .85rem 1rem; }
    .meta-item .meta-label { font-size: .72rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; letter-spacing: .06em; margin-bottom: .25rem; }
    .meta-item .meta-value { font-size: .9rem; font-weight: 600; color: var(--text); }

    /* Timeline */
    .timeline { display: flex; flex-direction: column; gap: 0; }
    .timeline-item { display: flex; gap: 1rem; padding: .85rem 0; position: relative; }
    .timeline-item:not(:last-child)::after { content: ''; position: absolute; left: 11px; top: 36px; bottom: -10px; width: 2px; background: var(--border); }
    .timeline-dot { width: 24px; height: 24px; border-radius: 50%; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: .65rem; color: #fff; margin-top: .1rem; }
    .timeline-dot.blue   { background: var(--blue);   }
    .timeline-dot.orange { background: var(--orange); }
    .timeline-dot.green  { background: var(--green);  }
    .timeline-dot.purple { background: var(--purple); }
    .timeline-dot.red    { background: var(--red);    }
    .timeline-content .tl-action { font-size: .85rem; font-weight: 600; color: var(--text); }
    .timeline-content .tl-meta { font-size: .75rem; color: var(--text-muted); margin-top: .1rem; }

    @media (max-width: 768px) { .idea-detail-grid { grid-template-columns: 1fr; } .status-stepper { flex-direction: column; gap: 1rem; align-items: flex-start; } .step::after { display: none; } }
  </style>
</head>
<body>

<!-- Navbar -->
<nav class="landing-nav scrolled" style="position:fixed;">
  <div class="nav-inner">
    <a href="index.php" class="nav-brand">
      <div class="logo-box"><i class="fas fa-lightbulb"></i></div>
      IdeaMarket
    </a>
    <div class="nav-links">
      <a href="index.php">Home</a>
      <a href="browse.php">Browse Ideas</a>
      <a href="track.php" class="active">Track Idea</a>
      <a href="about.php">About</a>
    </div>
    <div class="nav-right">
      <div class="dark-toggle-land">
        <i class="fas fa-sun"></i>
        <div class="toggle-pill" id="darkPill"></div>
        <i class="fas fa-moon"></i>
      </div>
      <?php if ($user): ?>
        <a href="<?= $user['role']==='admin'?'admin.php':'dashboard.php' ?>" class="btn btn-primary btn-sm"><i class="fas fa-th-large"></i> Dashboard</a>
      <?php else: ?>
        <a href="login.php" class="btn btn-outline btn-sm">Log In</a>
        <a href="register.php" class="btn btn-primary btn-sm">Get Started</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Hero -->
<div class="track-hero">
  <h1><i class="fas fa-search" style="color:var(--orange);margin-right:.6rem;"></i>Track Your Idea</h1>
  <p>Enter your Idea ID below to see real-time status, reviews, and progress.</p>
  <form method="POST" class="track-form">
    <input type="text" name="idea_id" placeholder="e.g. IDEA-A1B2C3D4" value="<?= e($ideaId) ?>" maxlength="20" required>
    <button type="submit" class="btn btn-primary btn-lg"><i class="fas fa-search"></i> Track</button>
  </form>
</div>

<!-- Results -->
<div class="track-container">

  <?php if ($error): ?>
    <div class="alert alert-error fade-up">
      <i class="fas fa-times-circle"></i> <?= $error ?>
    </div>
    <div class="card card-body text-center" style="padding:2.5rem;">
      <i class="fas fa-lightbulb" style="font-size:3rem;color:var(--border);margin-bottom:1rem;"></i>
      <h5>Can't find your idea?</h5>
      <p class="text-muted mb-3">Double-check your Idea ID or browse our marketplace.</p>
      <div style="display:flex;gap:1rem;justify-content:center;">
        <a href="browse.php" class="btn btn-outline"><i class="fas fa-compass"></i> Browse Ideas</a>
        <?php if (!$user): ?>
          <a href="register.php" class="btn btn-primary"><i class="fas fa-plus"></i> Submit an Idea</a>
        <?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($idea): ?>
    <!-- Status Stepper -->
    <div class="card card-body mb-3 fade-up">
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;flex-wrap:wrap;gap:.5rem;">
        <div>
          <h4 style="margin-bottom:.2rem;"><?= e($idea['title']) ?></h4>
          <code style="background:var(--bg);padding:.3rem .75rem;border-radius:6px;font-size:.82rem;color:var(--navy);"><?= e($idea['idea_id']) ?></code>
        </div>
        <div style="display:flex;align-items:center;gap:.75rem;">
          <?= statusBadge($idea['status']) ?>
          <?= priorityBadge($idea['priority']) ?>
        </div>
      </div>

      <!-- Progress Steps -->
      <div class="status-stepper">
        <?php
          $steps = [
            ['pending',  'fa-clock',        'Submitted'],
            ['review',   'fa-search',       'Under Review'],
            ['approved', 'fa-check-circle', 'Approved'],
            ['funded',   'fa-rocket',       'Funded'],
          ];
          foreach ($steps as $i => [$val, $icon, $label]):
            $isDone   = $currentStep > $i;
            $isActive = $currentStep === $i;
            $cls      = $isDone ? 'done' : ($isActive ? 'active' : '');
        ?>
          <div class="step <?= $cls ?>">
            <div class="step-dot"><i class="fas <?= $isDone ? 'fa-check' : $icon ?>"></i></div>
            <span class="step-label"><?= $label ?></span>
          </div>
        <?php endforeach; ?>
      </div>

      <?php if ($idea['status'] === 'closed'): ?>
        <div class="alert alert-error" style="margin-top:1rem;"><i class="fas fa-times-circle"></i> This idea has been closed.</div>
      <?php endif; ?>
    </div>

    <!-- Detail Grid -->
    <div class="idea-detail-grid">
      <!-- Main Details -->
      <div>
        <div class="card card-body mb-3 fade-up">
          <div class="detail-section">
            <h5><i class="fas fa-info-circle me-2" style="color:var(--blue);"></i>About This Idea</h5>
            <p><?= nl2br(e($idea['description'])) ?></p>
          </div>

          <?php if ($idea['problem']): ?>
          <div class="detail-section">
            <h5><i class="fas fa-exclamation-triangle me-2" style="color:var(--orange);"></i>Problem Statement</h5>
            <p><?= nl2br(e($idea['problem'])) ?></p>
          </div>
          <?php endif; ?>

          <?php if ($idea['solution']): ?>
          <div class="detail-section">
            <h5><i class="fas fa-lightbulb me-2" style="color:var(--green);"></i>Proposed Solution</h5>
            <p><?= nl2br(e($idea['solution'])) ?></p>
          </div>
          <?php endif; ?>

          <?php if ($idea['target_market']): ?>
          <div class="detail-section">
            <h5><i class="fas fa-users me-2" style="color:var(--purple);"></i>Target Market</h5>
            <p><?= nl2br(e($idea['target_market'])) ?></p>
          </div>
          <?php endif; ?>

          <!-- Meta Grid -->
          <div class="meta-grid">
            <div class="meta-item">
              <div class="meta-label">Category</div>
              <div class="meta-value"><i class="fas <?= e($idea['cat_icon']) ?>" style="color:<?= e($idea['cat_color']) ?>;margin-right:.4rem;"></i><?= e($idea['cat_name']) ?></div>
            </div>
            <div class="meta-item">
              <div class="meta-label">Submitted By</div>
              <div class="meta-value"><?= e($idea['submitter']) ?></div>
            </div>
            <div class="meta-item">
              <div class="meta-label">Date Submitted</div>
              <div class="meta-value"><?= formatDate($idea['created_at'], 'F j, Y') ?></div>
            </div>
            <div class="meta-item">
              <div class="meta-label">Community Votes</div>
              <div class="meta-value"><i class="fas fa-heart" style="color:var(--red);margin-right:.3rem;"></i><?= number_format($idea['vote_count']) ?></div>
            </div>
            <?php if ($idea['assigned_name']): ?>
            <div class="meta-item">
              <div class="meta-label">Assigned Reviewer</div>
              <div class="meta-value"><?= e($idea['assigned_name']) ?></div>
            </div>
            <?php endif; ?>
            <?php if ($idea['funding_goal'] > 0): ?>
            <div class="meta-item" style="grid-column:span 2;">
              <div class="meta-label">Funding Progress</div>
              <?php $pct = min(100, round($idea['funding_raised']/$idea['funding_goal']*100)); ?>
              <div style="display:flex;align-items:center;gap:.75rem;margin-top:.4rem;">
                <div class="progress-bar" style="flex:1;height:8px;">
                  <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $pct>=100?'var(--green)':'var(--blue)' ?>;"></div>
                </div>
                <span style="font-weight:700;font-size:.85rem;">$<?= number_format($idea['funding_raised']) ?> / $<?= number_format($idea['funding_goal']) ?> (<?= $pct ?>%)</span>
              </div>
            </div>
            <?php endif; ?>
          </div>

          <!-- Tags -->
          <?php if ($idea['tags']): ?>
          <div style="margin-top:1rem;">
            <?php foreach (explode(',', $idea['tags']) as $tag): ?>
              <span style="display:inline-block;background:rgba(37,99,235,.08);color:var(--blue);padding:.2rem .65rem;border-radius:20px;font-size:.75rem;font-weight:600;margin:.2rem .15rem;">#<?= e(trim($tag)) ?></span>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>

        <!-- Actions -->
        <div style="display:flex;gap:.75rem;flex-wrap:wrap;">
          <a href="browse.php" class="btn btn-outline"><i class="fas fa-compass"></i> Browse More Ideas</a>
          <?php if ($user): ?>
            <a href="submit-idea.php" class="btn btn-primary"><i class="fas fa-plus"></i> Submit Your Idea</a>
          <?php else: ?>
            <a href="register.php" class="btn btn-primary"><i class="fas fa-user-plus"></i> Join &amp; Vote</a>
          <?php endif; ?>
        </div>
      </div>

      <!-- Timeline Sidebar -->
      <div>
        <div class="card">
          <div class="card-header">
            <span class="card-title"><i class="fas fa-history me-2" style="color:var(--purple);"></i>Activity Timeline</span>
          </div>
          <div class="card-body" style="padding:1.25rem;">
            <?php if (!empty($timeline)): ?>
              <div class="timeline">
                <?php foreach ($timeline as $tl):
                  $dotColor = match($tl['status'] ?? '') {
                    'pending'  => 'orange', 'review'  => 'blue',
                    'approved' => 'green',  'funded'  => 'purple',
                    'closed'   => 'red',    default   => 'blue'
                  };
                ?>
                  <div class="timeline-item">
                    <div class="timeline-dot <?= $dotColor ?>"><i class="fas fa-circle" style="font-size:.4rem;"></i></div>
                    <div class="timeline-content">
                      <div class="tl-action"><?= e($tl['action']) ?></div>
                      <div class="tl-meta">
                        <i class="fas fa-user" style="margin-right:.2rem;"></i><?= e($tl['full_name']) ?>
                        &nbsp;·&nbsp; <?= timeAgo($tl['timestamp']) ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="empty-state" style="padding:1.5rem;">
                <i class="fas fa-history" style="font-size:2rem;"></i>
                <p>No activity logged yet.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <!-- No search yet -->
  <?php if (!$ideaId && !$idea): ?>
    <div class="card card-body text-center fade-up" style="padding:3.5rem 2rem;">
      <i class="fas fa-search" style="font-size:3.5rem;color:var(--border);margin-bottom:1rem;display:block;"></i>
      <h4 style="margin-bottom:.5rem;">Enter Your Idea ID</h4>
      <p class="text-muted mb-3">Your Idea ID was sent to you when you submitted your idea.<br>It looks like <code>IDEA-A1B2C3D4</code></p>
      <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
        <a href="browse.php" class="btn btn-outline"><i class="fas fa-compass"></i> Browse All Ideas</a>
        <a href="<?= $user ? 'my-ideas.php' : 'register.php' ?>" class="btn btn-primary">
          <i class="fas fa-<?= $user ? 'lightbulb' : 'rocket' ?>"></i> <?= $user ? 'My Ideas' : 'Get Started' ?>
        </a>
      </div>
    </div>
  <?php endif; ?>

</div><!-- /track-container -->

<footer style="background:var(--navy-dark);color:rgba(255,255,255,.5);text-align:center;padding:2rem;font-size:.82rem;">
  &copy; <?= date('Y') ?> IdeaMarket &nbsp;·&nbsp; <a href="index.php" style="color:#93c5fd;">Home</a> &nbsp;·&nbsp; <a href="login.php" style="color:#93c5fd;">Login</a>
</footer>

<div class="spinner-overlay"><div class="spinner"></div></div>

<script src="assets/js/app.js"></script>
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
  // Animate progress bars
  setTimeout(() => {
    document.querySelectorAll('.progress-fill').forEach(bar => {
      const w = bar.style.width; bar.style.width = '0'; setTimeout(() => bar.style.width = w, 100);
    });
  }, 300);
});
</script>
</body>
</html>
