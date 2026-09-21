<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
requireLogin();

$user  = currentUser();
$flash = null;
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $tagline     = trim($_POST['tagline'] ?? '');
    $desc        = trim($_POST['description'] ?? '');
    $problem     = trim($_POST['problem'] ?? '');
    $solution    = trim($_POST['solution'] ?? '');
    $market      = trim($_POST['target_market'] ?? '');
    $catId       = (int)($_POST['category_id'] ?? 8);
    $priority    = $_POST['priority'] ?? 'medium';
    $fundingGoal = $_POST['funding_goal'] ? (float)$_POST['funding_goal'] : null;
    $tags        = trim($_POST['tags'] ?? '');

    if (strlen($title) < 5)   { $flash = ['type'=>'error','msg'=>'Title must be at least 5 characters.']; }
    elseif (strlen($desc) < 30) { $flash = ['type'=>'error','msg'=>'Description must be at least 30 characters.']; }
    else {
        $ideaId = generateIdeaId();
        $stmt = $pdo->prepare("
            INSERT INTO ideas (idea_id, user_id, category_id, title, tagline, description, problem, solution, target_market, funding_goal, priority, tags, status, created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,'pending',NOW())
        ");
        $stmt->execute([$ideaId, $user['id'], $catId, $title, $tagline, $desc, $problem, $solution, $market, $fundingGoal, $priority, $tags]);
        $newId = $pdo->lastInsertId();
        logActivity($pdo, $newId, 'Idea submitted to marketplace', $user['id'], 'pending');
        setFlash('success', "🎉 Your idea has been submitted! Tracking ID: <strong>$ideaId</strong>");
        header('Location: dashboard.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Submit Idea — IdeaMarket</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/main.css">
</head>
<body>

<div class="layout-wrapper">
  <!-- Sidebar -->
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
      <a href="submit-idea.php" class="active"><i class="fas fa-plus-circle"></i> Submit Idea</a>
      <a href="my-ideas.php"><i class="fas fa-lightbulb"></i> My Ideas</a>
      <a href="browse.php"><i class="fas fa-compass"></i> Browse Market</a>
      <a href="track.php"><i class="fas fa-search"></i> Track an Idea</a>
      <span class="nav-section-label" style="margin-top:.5rem;">Account</span>
      <a href="profile.php"><i class="fas fa-user-circle"></i> My Profile</a>
      <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Log Out</a>
    </nav>
  </aside>

  <!-- Main -->
  <main class="main-content">
    <header class="top-header">
      <div style="display:flex;align-items:center;gap:1rem;">
        <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
        <span class="page-title">Submit Your Idea</span>
      </div>
      <div class="header-right">
        <div class="dark-toggle"><i class="fas fa-moon" style="font-size:.8rem;"></i><div class="toggle-switch"></div></div>
        <div class="header-avatar"><?= strtoupper(substr($user['name'],0,2)) ?></div>
      </div>
    </header>

    <div class="page-body fade-in">
      <div class="page-header">
        <div class="breadcrumb">
          <a href="dashboard.php">Dashboard</a><i class="fas fa-chevron-right" style="font-size:.65rem;"></i> Submit Idea
        </div>
        <h1>Submit Your Idea 💡</h1>
        <p>Share your innovation with the world. Be clear, specific, and compelling.</p>
      </div>

      <?php if ($flash): ?>
        <div class="alert alert-<?= $flash['type'] ?>"><i class="fas fa-exclamation-circle"></i> <?= $flash['msg'] ?></div>
      <?php endif; ?>

      <div style="display:grid;grid-template-columns:1fr 320px;gap:2rem;align-items:start;">

        <!-- Form -->
        <form method="POST" id="ideaForm" novalidate>

          <!-- Basic Info -->
          <div class="card mb-3">
            <div class="card-header">
              <span class="card-title"><i class="fas fa-info-circle me-2" style="color:var(--blue)"></i>Basic Information</span>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label class="form-label">Idea Title <span style="color:var(--red);">*</span></label>
                <input type="text" name="title" class="form-control" placeholder="A clear, catchy title for your idea" required maxlength="255" value="<?= e($_POST['title']??'') ?>">
                <span class="form-error">Title is required (min 5 characters).</span>
              </div>
              <div class="form-group">
                <label class="form-label">Tagline</label>
                <input type="text" name="tagline" class="form-control" placeholder="One line that sells your idea (optional)" maxlength="255" value="<?= e($_POST['tagline']??'') ?>">
              </div>
              <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem;">
                <div class="form-group">
                  <label class="form-label">Category <span style="color:var(--red);">*</span></label>
                  <select name="category_id" class="form-control" required>
                    <?php foreach ($categories as $c): ?>
                      <option value="<?= $c['id'] ?>" <?= ($_POST['category_id']??0)==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="form-group">
                  <label class="form-label">Priority</label>
                  <select name="priority" class="form-control">
                    <option value="low" <?= ($_POST['priority']??'medium')==='low'?'selected':'' ?>>Low — Casual exploration</option>
                    <option value="medium" <?= ($_POST['priority']??'medium')==='medium'?'selected':'' ?> selected>Medium — Ready to build</option>
                    <option value="high" <?= ($_POST['priority']??'medium')==='high'?'selected':'' ?>>High — Urgent opportunity</option>
                  </select>
                </div>
              </div>
            </div>
          </div>

          <!-- Idea Details -->
          <div class="card mb-3">
            <div class="card-header">
              <span class="card-title"><i class="fas fa-align-left me-2" style="color:var(--orange)"></i>Idea Details</span>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label class="form-label">Full Description <span style="color:var(--red);">*</span></label>
                <textarea name="description" class="form-control" rows="4" placeholder="Describe your idea in full detail. What is it? How does it work?" required><?= e($_POST['description']??'') ?></textarea>
                <span class="form-error">Description is required (min 30 characters).</span>
              </div>
              <div class="form-group">
                <label class="form-label">The Problem You're Solving</label>
                <textarea name="problem" class="form-control" rows="3" placeholder="What problem does your idea address? Why is it important?"><?= e($_POST['problem']??'') ?></textarea>
              </div>
              <div class="form-group">
                <label class="form-label">Your Proposed Solution</label>
                <textarea name="solution" class="form-control" rows="3" placeholder="How specifically does your idea solve the problem?"><?= e($_POST['solution']??'') ?></textarea>
              </div>
              <div class="form-group">
                <label class="form-label">Target Market</label>
                <textarea name="target_market" class="form-control" rows="2" placeholder="Who would benefit from this idea? Who is your target audience?"><?= e($_POST['target_market']??'') ?></textarea>
              </div>
            </div>
          </div>

          <!-- Funding & Tags -->
          <div class="card mb-3">
            <div class="card-header">
              <span class="card-title"><i class="fas fa-dollar-sign me-2" style="color:var(--green)"></i>Funding &amp; Tags</span>
            </div>
            <div class="card-body">
              <div class="form-group">
                <label class="form-label">Funding Goal (USD)</label>
                <div style="position:relative;">
                  <span style="position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:var(--text-muted);font-weight:600;">$</span>
                  <input type="number" name="funding_goal" class="form-control" placeholder="0 = No funding needed" min="0" step="100" style="padding-left:1.8rem;" value="<?= e($_POST['funding_goal']??'') ?>">
                </div>
                <small style="color:var(--text-muted);font-size:.78rem;">Set to 0 or leave blank if you're not seeking investment.</small>
              </div>
              <div class="form-group">
                <label class="form-label">Tags <span style="font-weight:400;color:var(--text-muted);">(comma-separated)</span></label>
                <input type="text" name="tags" class="form-control" placeholder="e.g. AI, sustainability, mobile, B2B" value="<?= e($_POST['tags']??'') ?>">
              </div>
            </div>
          </div>

          <div style="display:flex;gap:1rem;flex-wrap:wrap;">
            <button type="submit" class="btn btn-primary btn-lg">
              <i class="fas fa-paper-plane"></i> Submit Idea
            </button>
            <a href="dashboard.php" class="btn btn-outline btn-lg">
              <i class="fas fa-times"></i> Cancel
            </a>
          </div>

        </form>

        <!-- Sidebar Guide -->
        <div>
          <div class="card mb-3">
            <div class="card-header"><span class="card-title"><i class="fas fa-map-signs me-2" style="color:var(--purple)"></i>What Happens Next</span></div>
            <div class="card-body">
              <div style="display:flex;flex-direction:column;gap:1rem;">
                <?php
                  $steps = [
                    ['fa-paper-plane','blue','Submitted','Your idea enters the queue with a unique Idea ID.'],
                    ['fa-clock','orange','Pending Review','Admin reviews your submission for quality.'],
                    ['fa-users','green','Community Votes','The community discovers and votes on your idea.'],
                    ['fa-check-circle','green','Approved','Your idea goes live on the marketplace.'],
                    ['fa-rocket','purple','Investor Matching','Investors can pledge funding to your idea.'],
                  ];
                  foreach ($steps as $i=>[$icon,$color,$label,$desc]):
                ?>
                  <div style="display:flex;gap:.75rem;align-items:flex-start;">
                    <div style="width:28px;height:28px;background:rgba(0,0,0,.05);border-radius:8px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                      <i class="fas <?= $icon ?>" style="color:var(--<?= $color ?>);font-size:.8rem;"></i>
                    </div>
                    <div>
                      <div style="font-size:.82rem;font-weight:700;"><?= $label ?></div>
                      <div style="font-size:.75rem;color:var(--text-muted);"><?= $desc ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <div class="card">
            <div class="card-header"><span class="card-title"><i class="fas fa-lightbulb me-2" style="color:var(--gold)"></i>Tips for Success</span></div>
            <div class="card-body">
              <ul style="display:flex;flex-direction:column;gap:.6rem;padding-left:0;list-style:none;">
                <?php foreach(['Be specific about the problem','Explain why now is the right time','Include your target audience','Set realistic funding goals','Add relevant tags for discovery'] as $tip): ?>
                  <li style="display:flex;gap:.5rem;font-size:.82rem;align-items:flex-start;">
                    <i class="fas fa-check-circle" style="color:var(--green);margin-top:.2rem;flex-shrink:0;"></i> <?= $tip ?>
                  </li>
                <?php endforeach; ?>
              </ul>
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
document.getElementById('ideaForm').addEventListener('submit', function(e) {
  if (!validateForm(this)) e.preventDefault();
  else Spinner.show();
});
// Character counter on description
const desc = document.querySelector('textarea[name="description"]');
if (desc) {
  desc.addEventListener('input', function() {
    const min = 30, len = this.value.length;
    const color = len < min ? 'var(--red)' : 'var(--green)';
    this.style.borderColor = len > 0 ? color : '';
  });
}
</script>
</body>
</html>
