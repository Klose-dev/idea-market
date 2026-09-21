<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';

$user = isLoggedIn() ? currentUser() : null;

// Filters
$cat    = $_GET['cat']    ?? '';
$status = $_GET['status'] ?? 'approved';
$sort   = $_GET['sort']   ?? 'votes';
$search = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

// Build query
$where = ["i.status IN ('approved','funded')"];
$params = [];
if ($cat) { $where[] = 'c.slug = ?'; $params[] = $cat; }
if ($search) { $where[] = '(i.title LIKE ? OR i.description LIKE ? OR i.tagline LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }

$whereSQL = 'WHERE ' . implode(' AND ', $where);
$orderBy = match($sort) {
  'newest' => 'i.created_at DESC',
  'oldest' => 'i.created_at ASC',
  'funded' => 'i.funding_raised DESC',
  default  => 'i.vote_count DESC',
};

$total = $pdo->prepare("SELECT COUNT(*) FROM ideas i JOIN categories c ON i.category_id=c.id $whereSQL");
$total->execute($params);
$totalCount = $total->fetchColumn();
$pagination = paginate($totalCount, $perPage, $page);

$stmt = $pdo->prepare("
  SELECT i.*, c.name AS cat_name, c.slug AS cat_slug, c.icon AS cat_icon, c.color AS cat_color, u.full_name
  FROM ideas i
  JOIN categories c ON i.category_id = c.id
  JOIN users u ON i.user_id = u.id
  $whereSQL
  ORDER BY $orderBy
  LIMIT $perPage OFFSET {$pagination['offset']}
");
$stmt->execute($params);
$ideas = $stmt->fetchAll();

// Check user votes
$userVotes = [];
if ($user) {
  $voteStmt = $pdo->prepare("SELECT idea_id FROM votes WHERE user_id = ?");
  $voteStmt->execute([$user['id']]);
  $userVotes = array_column($voteStmt->fetchAll(), 'idea_id');
}

$categories = $pdo->query("SELECT *, (SELECT COUNT(*) FROM ideas WHERE category_id=categories.id AND status IN ('approved','funded')) AS idea_count FROM categories ORDER BY name")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Browse Ideas — IdeaMarket</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/landing.css">
  <style>
    body { padding-top: 68px; }
    .browse-hero { background:linear-gradient(135deg,var(--navy-dark),var(--navy)); padding:3rem 2rem; text-align:center; }
    .browse-hero h1 { color:#fff;  font-size:2rem; margin-bottom:.5rem; }
    .browse-hero p { color:rgba(255,255,255,.6); }
    .browse-layout { max-width:1300px; margin:0 auto; padding:2rem; display:grid; grid-template-columns:240px 1fr; gap:2rem; }
    .sidebar-filters { background:var(--card); border-radius:var(--radius); padding:1.5rem; box-shadow:var(--shadow); height:fit-content; position:sticky; top:90px; }
    .filter-section { margin-bottom:1.5rem; }
    .filter-section h6 { font-size:.78rem; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:var(--text-muted); margin-bottom:.75rem; }
    .filter-option { display:flex; align-items:center; gap:.6rem; padding:.4rem .6rem; border-radius:6px; cursor:pointer; font-size:.875rem; transition:var(--transition); }
    .filter-option:hover { background:var(--bg); }
    .filter-option.active { background:rgba(37,99,235,.08); color:var(--blue); font-weight:600; }
    .filter-option i { width:18px; text-align:center; }
    .filter-count { margin-left:auto; background:var(--bg); border-radius:20px; padding:.1rem .5rem; font-size:.7rem; font-weight:700; }
    .idea-card-image { font-size:2.5rem; }
    .funding-bar { margin-top:.75rem; }
    .funding-label { display:flex; justify-content:space-between; font-size:.75rem; color:var(--text-muted); margin-bottom:.3rem; }
    @media(max-width:768px) { .browse-layout { grid-template-columns:1fr; } .sidebar-filters { display:none; } }
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
      <a href="browse.php" class="active">Browse Ideas</a>
      <a href="index.php#how-it-works">How It Works</a>
      <a href="about.php">About</a>
    </div>
    <div class="nav-right">
      <div class="dark-toggle-land">
        <i class="fas fa-sun"></i>
        <div class="toggle-pill" id="darkPill"></div>
        <i class="fas fa-moon"></i>
      </div>
      <?php if ($user): ?>
        <a href="<?= $user['role']==='admin' ? 'admin.php' : 'dashboard.php' ?>" class="btn btn-primary btn-sm">
          <i class="fas fa-th-large"></i> Dashboard
        </a>
      <?php else: ?>
        <a href="login.php" class="btn btn-outline btn-sm">Log In</a>
        <a href="register.php" class="btn btn-primary btn-sm">Get Started</a>
      <?php endif; ?>
    </div>
  </div>
</nav>

<!-- Browse Hero -->
<div class="browse-hero">
  <h1><i class="fas fa-compass" style="color:var(--orange);margin-right:.5rem;"></i>Browse the Marketplace</h1>
  <p><?= $totalCount ?> ideas available · Find something worth supporting</p>
</div>

<!-- Search Bar -->
<div style="background:var(--card);border-bottom:1px solid var(--border);padding:1rem 2rem;">
  <div style="max-width:1300px;margin:0 auto;">
    <form method="GET" style="display:flex;gap:1rem;align-items:center;flex-wrap:wrap;">
      <div style="flex:1;min-width:220px;position:relative;">
        <i class="fas fa-search" style="position:absolute;left:.9rem;top:50%;transform:translateY(-50%);color:var(--text-muted);"></i>
        <input type="text" name="q" class="form-control" placeholder="Search ideas..." value="<?= e($search) ?>" style="padding-left:2.5rem;">
      </div>
      <select name="sort" class="form-control" style="min-width:150px;" onchange="this.form.submit()">
        <option value="votes"  <?= $sort==='votes'?'selected':'' ?>>Most Voted</option>
        <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Newest First</option>
        <option value="funded" <?= $sort==='funded'?'selected':'' ?>>Most Funded</option>
      </select>
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Search</button>
      <?php if ($search || $cat): ?>
        <a href="browse.php" class="btn btn-outline">Clear Filters</a>
      <?php endif; ?>
    </form>
  </div>
</div>

<!-- Browse Layout -->
<div class="browse-layout">

  <!-- Sidebar Filters -->
  <aside class="sidebar-filters">
    <div class="filter-section">
      <h6>Category</h6>
      <a href="browse.php?sort=<?= e($sort) ?>" class="filter-option <?= !$cat?'active':'' ?>">
        <i class="fas fa-globe"></i> All Categories
        <span class="filter-count"><?= $totalCount ?></span>
      </a>
      <?php foreach ($categories as $c): ?>
        <a href="browse.php?cat=<?= e($c['slug']) ?>&sort=<?= e($sort) ?>" class="filter-option <?= $cat===$c['slug']?'active':'' ?>">
          <i class="fas <?= e($c['icon']) ?>" style="color:<?= e($c['color']) ?>;"></i>
          <?= e($c['name']) ?>
          <span class="filter-count"><?= $c['idea_count'] ?></span>
        </a>
      <?php endforeach; ?>
    </div>
    <div class="filter-section">
      <h6>Quick Links</h6>
      <?php if ($user): ?>
        <a href="submit-idea.php" class="btn btn-primary w-100"><i class="fas fa-plus"></i> Submit Idea</a>
      <?php else: ?>
        <a href="register.php" class="btn btn-primary w-100"><i class="fas fa-rocket"></i> Join &amp; Submit</a>
      <?php endif; ?>
      <a href="track.php" class="btn btn-outline w-100 mt-2"><i class="fas fa-search"></i> Track Idea</a>
    </div>
  </aside>

  <!-- Ideas Grid -->
  <div>
    <?php if ($search): ?>
      <div class="alert alert-info mb-3">
        <i class="fas fa-search"></i> Showing results for "<strong><?= e($search) ?></strong>" — <?= $totalCount ?> found
      </div>
    <?php endif; ?>

    <?php if ($ideas): ?>
      <div class="idea-grid">
        <?php foreach ($ideas as $idea): ?>
          <?php $isVoted = in_array($idea['id'], $userVotes); ?>
          <div class="idea-card">
            <!-- Image/Icon -->
            <div class="idea-card-image" style="background:linear-gradient(135deg,var(--navy-dark),var(--navy));">
              <?php if ($idea['is_featured']): ?>
                <span style="position:absolute;top:.75rem;right:.75rem;z-index:2;" class="badge badge-premium"><i class="fas fa-star"></i> Featured</span>
              <?php endif; ?>
              <i class="fas <?= e($idea['cat_icon']) ?>" style="color:<?= e($idea['cat_color']) ?>;position:relative;z-index:1;"></i>
            </div>
            <div class="idea-card-body">
              <div style="display:flex;align-items:center;gap:.5rem;margin-bottom:.6rem;flex-wrap:wrap;">
                <?= statusBadge($idea['status']) ?>
                <span class="badge" style="background:rgba(0,0,0,.05);color:var(--text-muted);font-size:.7rem;">
                  <i class="fas <?= e($idea['cat_icon']) ?>"></i> <?= e($idea['cat_name']) ?>
                </span>
              </div>
              <h5><a href="idea.php?id=<?= $idea['id'] ?>" style="color:var(--text);"><?= e($idea['title']) ?></a></h5>
              <?php if ($idea['tagline']): ?>
                <p style="color:var(--blue);font-size:.8rem;font-weight:600;margin-bottom:.4rem;"><?= e($idea['tagline']) ?></p>
              <?php endif; ?>
              <p><?= e(substr($idea['description'],0,110)) ?>…</p>
              <!-- Funding bar -->
              <?php if ($idea['funding_goal'] && $idea['funding_goal'] > 0): ?>
                <?php $pct = min(100, round($idea['funding_raised']/$idea['funding_goal']*100)); ?>
                <div class="funding-bar">
                  <div class="funding-label">
                    <span><strong>$<?= number_format($idea['funding_raised']) ?></strong> raised</span>
                    <span><?= $pct ?>%</span>
                  </div>
                  <div class="progress-bar">
                    <div class="progress-fill" style="width:<?= $pct ?>%;background:<?= $pct>=100?'var(--green)':'var(--blue)' ?>;"></div>
                  </div>
                  <div style="font-size:.72rem;color:var(--text-muted);margin-top:.25rem;">Goal: $<?= number_format($idea['funding_goal']) ?></div>
                </div>
              <?php endif; ?>
            </div>
            <div class="idea-card-footer">
              <div class="idea-meta">
                <span class="idea-meta-item"><i class="fas fa-user"></i> <?= e(explode(' ',$idea['full_name'])[0]) ?></span>
                <span class="idea-meta-item"><i class="fas fa-calendar"></i> <?= formatDate($idea['created_at'],'M Y') ?></span>
              </div>
              <div style="display:flex;align-items:center;gap:.5rem;">
                <a href="idea.php?id=<?= $idea['id'] ?>" class="btn btn-outline btn-sm">
                  <i class="fas fa-eye"></i>
                </a>
                <button class="idea-vote-btn <?= $isVoted?'voted':'' ?>"
                        onclick="voteIdea(<?= $idea['id'] ?>, this)"
                        <?= !$user?'onclick="window.location=\'login.php\'"':'' ?>>
                  <i class="fas fa-heart"></i>
                  <span class="vote-count"><?= $idea['vote_count'] ?></span>
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($pagination['total_pages'] > 1): ?>
        <div class="pagination">
          <?php for ($p=1; $p<=$pagination['total_pages']; $p++): ?>
            <a href="browse.php?page=<?= $p ?>&sort=<?= e($sort) ?>&cat=<?= e($cat) ?>&q=<?= e($search) ?>"
               class="page-btn <?= $p===$page?'active':'' ?>"><?= $p ?></a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="empty-state card card-body" style="padding:4rem;">
        <i class="fas fa-lightbulb"></i>
        <h5>No ideas found</h5>
        <p><?= $search ? "No results for \"$search\". Try different keywords." : "No ideas in this category yet." ?></p>
        <a href="submit-idea.php" class="btn btn-primary mt-3"><i class="fas fa-plus"></i> Be the First to Submit</a>
      </div>
    <?php endif; ?>
  </div>

</div><!-- /browse-layout -->

<!-- Footer -->
<footer style="background:var(--navy-dark);color:rgba(255,255,255,.5);text-align:center;padding:2rem;font-size:.82rem;">
  &copy; <?= date('Y') ?> IdeaMarket · <a href="index.php" style="color:#93c5fd;">Home</a> · <a href="login.php" style="color:#93c5fd;">Login</a>
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
});
</script>
</body>
</html>
