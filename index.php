<?php
require_once 'includes/auth.php';
$user = isLoggedIn() ? currentUser() : null;

$totalIdeas     = 247;
$fundedIdeas    = 38;
$totalVotes     = 14800;
$totalInvestors = 512;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>IdeaMarket — Where Ideas Find Their Future</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body>

<!-- Navbar -->
<nav class="landing-nav" id="mainNav">
  <div class="nav-inner">
    <a href="index.php" class="nav-brand">
      <div class="logo-box"><i class="fas fa-lightbulb"></i></div>
      IdeaMarket
    </a>
    <div class="nav-links">
      <a href="index.php" class="active">Home</a>
      <a href="browse.php">Browse Ideas</a>
      <a href="index.php#how-it-works">How It Works</a>
      <a href="index.php#categories">Categories</a>
      <a href="about.php">About</a>
    </div>
    <div class="nav-right">
      <div class="dark-toggle-land" title="Toggle dark mode">
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

<!-- Hero -->
<section class="hero">
  <div class="hero-content">
    <div class="hero-badge" style="font-family:'Times New Roman', serif;"><i class="fas fa-rocket"></i> The Future of Innovation is Here</div>
    <h1 style="font-family:'Times New Roman', serif;">Where <span class="highlight">Ideas</span><br>Find Their Future</h1>
    <p>Submit your ideas, get community feedback, attract investors, and turn your vision into reality on the world's most transparent idea marketplace.</p>
    <div class="hero-ctas">
      <a href="<?= isLoggedIn() ? 'dashboard.php' : 'register.php' ?>" class="btn btn-primary btn-lg">
        <i class="fas fa-lightbulb"></i> Submit Your Idea
      </a>
      <a href="browse.php" class="btn-outline-white">
        <i class="fas fa-compass"></i> Browse Ideas
      </a>
    </div>
  </div>
</section>

<!-- Features -->
<section class="features-section">
  <div class="section-label"><span>Why IdeaMarket</span></div>
  <h2 class="section-title">Built for Bold Thinkers</h2>
  <p class="section-sub">A transparent, community-driven platform where every idea gets a fair chance to shine and find real support.</p>
  <div class="features-grid">
    <div class="feature-card">
      <div class="feature-icon blue"><i class="fas fa-shield-alt"></i></div>
      <h4>Transparent by Design</h4>
      <p>Every idea gets a unique Idea ID. Track its journey from submission through review, approval, and funding — all in real time with full visibility.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon orange"><i class="fas fa-bolt"></i></div>
      <h4>Fast Community Validation</h4>
      <p>Get instant feedback through community voting and expert reviews. The best ideas rise to the top quickly with our smart ranking algorithm.</p>
    </div>
    <div class="feature-card">
      <div class="feature-icon green"><i class="fas fa-hand-holding-usd"></i></div>
      <h4>Direct Investor Access</h4>
      <p>Connect directly with investors who are actively looking for the next big thing. Pitch, negotiate, and secure funding all in one place.</p>
    </div>
  </div>
</section>

<!-- How It Works -->
<section class="howit-section" id="how-it-works">
  <div style="max-width:1100px;margin:0 auto;">
    <div class="section-label"><span>The Process</span></div>
    <h2 class="section-title">From Idea to Reality</h2>
    <p class="section-sub">Six simple steps to take your idea from a spark to a fully funded project.</p>
    <div class="steps-container">
      <div class="step-item">
        <div class="step-num">1</div>
        <div class="step-content">
          <h4>Register &amp; Build Your Profile</h4>
          <p>Create your free account as an Idea Submitter or Investor. Set up your profile, link your expertise, and join the community of innovators.</p>
          <span class="step-tag">Free Account</span>
        </div>
      </div>
      <div class="step-item">
        <div class="step-num">2</div>
        <div class="step-content">
          <h4>Submit Your Idea</h4>
          <p>Fill out the structured idea submission form — describe the problem, your solution, target market, and funding goal. Add supporting documents or visuals.</p>
          <span class="step-tag">Idea Form</span>
        </div>
      </div>
      <div class="step-item">
        <div class="step-num">3</div>
        <div class="step-content">
          <h4>Receive Your Unique Idea ID</h4>
          <p>Get a permanent tracking ID (e.g., IDEA-A1B2C3D4) instantly. Share it publicly or use it to track your idea's progress at any time.</p>
          <span class="step-tag">e.g. IDEA-A1B2C3D4</span>
        </div>
      </div>
      <div class="step-item">
        <div class="step-num">4</div>
        <div class="step-content">
          <h4>Community Votes &amp; Expert Review</h4>
          <p>The community votes on ideas. High-voted ideas get assigned to expert reviewers who assess feasibility, market potential, and innovation.</p>
          <span class="step-tag">Crowd + Expert</span>
        </div>
      </div>
      <div class="step-item">
        <div class="step-num">5</div>
        <div class="step-content">
          <h4>Investor Matching &amp; Funding</h4>
          <p>Approved ideas are listed on the funding board. Investors browse, pledge, and connect directly with idea owners to negotiate terms.</p>
          <span class="step-tag">Funding Board</span>
        </div>
      </div>
      <div class="step-item">
        <div class="step-num">6</div>
        <div class="step-content">
          <h4>Launch &amp; Grow</h4>
          <p>Funded ideas get a dedicated project page. Track milestones, report progress, and build your team — all inside IdeaMarket.</p>
          <span class="step-tag">Dashboard</span>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Categories -->
<section class="categories-section" id="categories">
  <div style="max-width:1100px;margin:0 auto;">
    <div class="section-label"><span>Explore</span></div>
    <h2 class="section-title">Browse by Category</h2>
    <p class="section-sub">From cutting-edge tech to social innovation — find ideas in every domain.</p>
    <div class="categories-grid">
      <a href="browse.php?cat=technology" class="category-pill">
        <i class="fas fa-microchip" style="color:#2563eb"></i>
        <span>Technology</span><span class="cat-count">42 ideas</span>
      </a>
      <a href="browse.php?cat=social" class="category-pill">
        <i class="fas fa-users" style="color:#f97316"></i>
        <span>Social</span><span class="cat-count">28 ideas</span>
      </a>
      <a href="browse.php?cat=health" class="category-pill">
        <i class="fas fa-heartbeat" style="color:#dc2626"></i>
        <span>Health</span><span class="cat-count">35 ideas</span>
      </a>
      <a href="browse.php?cat=education" class="category-pill">
        <i class="fas fa-graduation-cap" style="color:#7c3aed"></i>
        <span>Education</span><span class="cat-count">19 ideas</span>
      </a>
      <a href="browse.php?cat=environment" class="category-pill">
        <i class="fas fa-leaf" style="color:#16a34a"></i>
        <span>Environment</span><span class="cat-count">24 ideas</span>
      </a>
      <a href="browse.php?cat=finance" class="category-pill">
        <i class="fas fa-chart-line" style="color:#f59e0b"></i>
        <span>Finance</span><span class="cat-count">31 ideas</span>
      </a>
      <a href="browse.php?cat=arts" class="category-pill">
        <i class="fas fa-palette" style="color:#ec4899"></i>
        <span>Arts &amp; Media</span><span class="cat-count">16 ideas</span>
      </a>
      <a href="browse.php?cat=other" class="category-pill">
        <i class="fas fa-lightbulb" style="color:#64748b"></i>
        <span>Other</span><span class="cat-count">52 ideas</span>
      </a>
    </div>
  </div>
</section>

<!-- CTA Banner -->
<div class="cta-banner">
  <div style="position:relative;z-index:1;">
    <h2>Ready to Launch Your Idea?</h2>
    <p>Join thousands of innovators already on IdeaMarket. Submit for free and let the world decide your idea's potential.</p>
    <div class="btn-group">
      <a href="register.php" class="btn btn-primary btn-lg"><i class="fas fa-rocket"></i> Start for Free</a>
      <a href="browse.php" class="btn-outline-white"><i class="fas fa-eye"></i> Browse Ideas</a>
    </div>
  </div>
</div>

<!-- Footer -->
<footer class="landing-footer">
  <div class="footer-inner">
    <div class="footer-top">
      <div class="footer-brand">
        <h5><i class="fas fa-lightbulb me-2" style="color:var(--orange)"></i>IdeaMarket</h5>
        <p>The world's most transparent idea marketplace — where innovation meets opportunity and bold ideas find real support.</p>
      </div>
      <div class="footer-col">
        <h6>Platform</h6>
        <a href="browse.php">Browse Ideas</a>
        <a href="register.php">Submit an Idea</a>
        <a href="track.php">Track an Idea</a>
        <a href="register.php?role=investor">Become Investor</a>
      </div>
      <div class="footer-col">
        <h6>Company</h6>
        <a href="about.php">About Us</a>
        <a href="#">Blog</a>
        <a href="#">Press Kit</a>
        <a href="#">Careers</a>
      </div>
      <div class="footer-col">
        <h6>Support</h6>
        <a href="#">Help Center</a>
        <a href="#">Terms of Service</a>
        <a href="#">Privacy Policy</a>
        <a href="#">Contact</a>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> IdeaMarket. All rights reserved.</span>
      <div class="social-links">
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-linkedin"></i></a>
        <a href="#"><i class="fab fa-github"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
      </div>
    </div>
  </div>
</footer>

<div id="sidebarOverlay" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:99;"></div>
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
});
</script>
</body>
</html>
