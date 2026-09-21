<?php require_once 'includes/auth.php'; $user = isLoggedIn() ? currentUser() : null; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About — IdeaMarket</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/main.css">
  <link rel="stylesheet" href="assets/css/landing.css">
  <style>
    body { padding-top: 68px; }
    .about-hero { background:linear-gradient(135deg,var(--navy-dark),var(--navy));padding:5rem 2rem;text-align:center; }
    .about-hero h1 { color:#fff;font-family:var(--font-head);font-size:2.8rem;margin-bottom:.75rem; }
    .about-hero p { color:rgba(255,255,255,.65);font-size:1.1rem;max-width:560px;margin:0 auto; }
    .about-section { padding:5rem 2rem; max-width:1100px; margin:0 auto; }
    .about-split { display:grid;grid-template-columns:1fr 1fr;gap:4rem;align-items:center; }
    .about-split .visual { background:linear-gradient(135deg,var(--navy-dark),var(--navy));border-radius:var(--radius-lg);aspect-ratio:1;display:flex;align-items:center;justify-content:center;font-size:6rem; }
    .value-cards { display:grid;grid-template-columns:repeat(2,1fr);gap:1.25rem;padding:5rem 2rem;max-width:1100px;margin:0 auto; }
    .value-card { background:var(--card);border-radius:var(--radius);padding:2rem;box-shadow:var(--shadow);transition:var(--transition);border-left:4px solid transparent; }
    .value-card:nth-child(1){border-left-color:var(--blue);}
    .value-card:nth-child(2){border-left-color:var(--orange);}
    .value-card:nth-child(3){border-left-color:var(--green);}
    .value-card:nth-child(4){border-left-color:var(--purple);}
    .value-card:hover{transform:translateY(-4px);box-shadow:var(--shadow-lg);}
    .value-card i { font-size:1.8rem;margin-bottom:1rem;display:block; }
    .value-card h4 { font-family:var(--font-head);margin-bottom:.5rem; }
    .value-card p { color:var(--text-muted);font-size:.875rem;line-height:1.6; }
    .team-section { background:var(--bg);padding:5rem 2rem;text-align:center; }
    .stats-banner{background:var(--navy-dark);padding:4rem 2rem;text-align:center;}
    .stats-banner-grid{display:flex;justify-content:center;gap:4rem;flex-wrap:wrap;}
    .stats-banner-item .n{font-family:var(--font-head);font-size:3rem;font-weight:800;color:#fff;}
    .stats-banner-item .l{color:rgba(255,255,255,.55);font-size:.85rem;margin-top:.25rem;}
    @media(max-width:768px){.about-split{grid-template-columns:1fr;}.value-cards{grid-template-columns:1fr;}}
  </style>
</head>
<body>
<nav class="landing-nav scrolled" style="position:fixed;">
  <div class="nav-inner">
    <a href="index.php" class="nav-brand"><div class="logo-box"><i class="fas fa-lightbulb"></i></div>IdeaMarket</a>
    <div class="nav-links">
      <a href="index.php">Home</a><a href="browse.php">Browse Ideas</a>
      <a href="index.php#how-it-works">How It Works</a><a href="about.php" class="active">About</a>
    </div>
    <div class="nav-right">
      <div class="dark-toggle-land"><i class="fas fa-sun"></i><div class="toggle-pill" id="darkPill"></div><i class="fas fa-moon"></i></div>
      <?php if($user): ?><a href="<?=$user['role']==='admin'?'admin.php':'dashboard.php'?>" class="btn btn-primary btn-sm"><i class="fas fa-th-large"></i> Dashboard</a>
      <?php else: ?><a href="login.php" class="btn btn-outline btn-sm">Log In</a><a href="register.php" class="btn btn-primary btn-sm">Get Started</a><?php endif; ?>
    </div>
  </div>
</nav>

<div class="about-hero">
  <h1>About IdeaMarket</h1>
  <p>We believe every great idea deserves a fair shot. IdeaMarket is the platform that makes that possible — transparently, democratically, and efficiently.</p>
</div>

<!-- Mission -->
<div class="about-section">
  <div class="about-split">
    <div>
      <span style="background:rgba(37,99,235,.1);color:var(--blue);padding:.3rem .9rem;border-radius:50px;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Our Mission</span>
      <h2 style="font-family:var(--font-head);font-size:2.2rem;margin:1rem 0;">Turning Ideas Into <span style="color:var(--blue);">Impact</span></h2>
      <p style="color:var(--text-muted);line-height:1.8;margin-bottom:1rem;">IdeaMarket was founded on a simple premise: the best ideas often come from unexpected places. Traditional innovation pipelines are slow, opaque, and favor those with existing connections. We're changing that.</p>
      <p style="color:var(--text-muted);line-height:1.8;margin-bottom:1.5rem;">Our platform gives every idea submitter a unique tracking ID, community visibility, expert review, and direct access to investors — all in one transparent ecosystem.</p>
      <div style="display:flex;gap:1rem;flex-wrap:wrap;">
        <a href="register.php" class="btn btn-primary"><i class="fas fa-rocket"></i> Join the Platform</a>
        <a href="browse.php" class="btn btn-outline"><i class="fas fa-compass"></i> Browse Ideas</a>
      </div>
    </div>
    <div class="visual">💡</div>
  </div>
</div>

<!-- Stats Banner -->
<div class="stats-banner">
  <div class="stats-banner-grid">
    <div class="stats-banner-item"><div class="n">247</div><div class="l">Ideas Submitted</div></div>
    <div class="stats-banner-item"><div class="n">38</div><div class="l">Ideas Funded</div></div>
    <div class="stats-banner-item"><div class="n">512+</div><div class="l">Active Members</div></div>
    <div class="stats-banner-item"><div class="n">14,800+</div><div class="l">Votes Cast</div></div>
  </div>
</div>

<!-- Values -->
<div style="background:var(--bg);padding:1rem 0;">
  <div style="max-width:1100px;margin:0 auto;padding:4rem 2rem;">
    <div class="section-label"><span>Our Values</span></div>
    <h2 class="section-title">What We Stand For</h2>
    <div class="value-cards" style="padding:0;margin-top:2.5rem;">
      <div class="value-card scroll-reveal">
        <i class="fas fa-shield-alt" style="color:var(--blue)"></i>
        <h4>Transparency</h4>
        <p>Every idea gets a permanent tracking ID. Every status change is logged. Every decision is visible. We believe full transparency builds trust with submitters, voters, and investors alike.</p>
      </div>
      <div class="value-card scroll-reveal">
        <i class="fas fa-bolt" style="color:var(--orange)"></i>
        <h4>Speed</h4>
        <p>From submission to first review in under 48 hours. Our streamlined process and real-time notifications ensure ideas never get stuck in bureaucratic limbo.</p>
      </div>
      <div class="value-card scroll-reveal">
        <i class="fas fa-balance-scale" style="color:var(--green)"></i>
        <h4>Accountability</h4>
        <p>Every reviewer is named. Every decision is time-stamped. If an idea is rejected, the submitter gets a clear explanation. Accountability at every step of the process.</p>
      </div>
      <div class="value-card scroll-reveal">
        <i class="fas fa-users" style="color:var(--purple)"></i>
        <h4>Community-Driven</h4>
        <p>The community votes on ideas before experts review them. The wisdom of the crowd surfaces the best ideas. Everyone has a voice — regardless of background or network.</p>
      </div>
    </div>
  </div>
</div>

<!-- How We Work -->
<div class="about-section" style="padding-top:3rem;">
  <div class="about-split" style="gap:3rem;">
    <div class="visual" style="font-size:4rem;">🏛️</div>
    <div>
      <span style="background:rgba(249,115,22,.1);color:var(--orange);padding:.3rem .9rem;border-radius:50px;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">How We Operate</span>
      <h2 style="font-family:var(--font-head);font-size:2rem;margin:1rem 0;">Built on Fairness &amp; Integrity</h2>
      <div style="display:flex;flex-direction:column;gap:1rem;">
        <?php foreach([
          ['fa-user-check','Open to Everyone','Anyone can register and submit ideas — no application fee, no gatekeepers.'],
          ['fa-vote-yea','Community First','Ideas are validated by the community before reaching expert reviewers.'],
          ['fa-search','Expert Review','Approved ideas are assessed by domain experts for feasibility and impact.'],
          ['fa-hand-holding-usd','Investor Access','Verified investors can pledge funding directly through the platform.'],
        ] as [$icon,$title,$desc]):?>
          <div style="display:flex;gap:.85rem;align-items:flex-start;">
            <div style="width:36px;height:36px;border-radius:10px;background:rgba(37,99,235,.08);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
              <i class="fas <?=$icon?>" style="color:var(--blue);"></i>
            </div>
            <div>
              <div style="font-weight:700;font-size:.9rem;margin-bottom:.2rem;"><?=$title?></div>
              <div style="color:var(--text-muted);font-size:.82rem;line-height:1.5;"><?=$desc?></div>
            </div>
          </div>
        <?php endforeach;?>
      </div>
    </div>
  </div>
</div>

<!-- CTA -->
<div class="cta-banner" style="margin:0 2rem 4rem;">
  <div style="position:relative;z-index:1;">
    <h2>Ready to Share Your Idea?</h2>
    <p>Join thousands of innovators already making an impact on IdeaMarket.</p>
    <div class="btn-group">
      <a href="register.php" class="btn btn-primary btn-lg"><i class="fas fa-rocket"></i> Submit for Free</a>
      <a href="browse.php" class="btn-outline-white"><i class="fas fa-eye"></i> Explore Ideas</a>
    </div>
  </div>
</div>

<footer class="landing-footer">
  <div class="footer-inner">
    <div class="footer-bottom" style="border:none;padding:0;">
      <span>&copy; <?=date('Y')?> IdeaMarket. All rights reserved.</span>
      <div class="social-links">
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-linkedin"></i></a>
        <a href="#"><i class="fab fa-github"></i></a>
      </div>
    </div>
  </div>
</footer>

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
  document.querySelectorAll('.scroll-reveal').forEach((el,i) => {
    el.style.opacity='0';el.style.transform='translateY(20px)';el.style.transition=`opacity .5s ease ${i*.1}s,transform .5s ease ${i*.1}s`;
    new IntersectionObserver(([e])=>{ if(e.isIntersecting){el.style.opacity='1';el.style.transform='none';} },{threshold:.15}).observe(el);
  });
});
</script>
</body>
</html>
