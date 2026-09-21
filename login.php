<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
if (isLoggedIn()) { header('Location: ' . (isAdmin() ? 'admin.php' : 'dashboard.php')); exit; }

$flash = getFlash();
$tab   = $_GET['tab'] ?? 'login';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'login') {
        $result = login($pdo, trim($_POST['email'] ?? ''), $_POST['password'] ?? '');
        if ($result['success']) {
            setFlash('success', 'Welcome back!');
            header('Location: ' . ($result['role']==='admin' ? 'admin.php' : 'dashboard.php'));
            exit;
        }
        $flash = ['type' => 'error', 'msg' => $result['message']];
        $tab = 'login';
    } elseif ($action === 'register') {
        $result = register(
            $pdo,
            trim($_POST['full_name']         ?? ''),
            trim($_POST['email']             ?? ''),
            $_POST['password']               ?? '',
            $_POST['confirm_password']       ?? ''
        );
        if ($result['success']) {
            setFlash('success', $result['message']);
            header('Location: login.php?tab=login');
            exit;
        }
        $flash = ['type' => 'error', 'msg' => $result['message']];
        $tab = 'register';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login / Register — IdeaMarket</title>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;500;600;700;800&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <link rel="stylesheet" href="assets/css/main.css">
  <style>
    body { min-height: 100vh; background: linear-gradient(135deg, #050d1f 0%, #0f2347 50%, #1a3a6b 100%); display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 2rem 1rem; }
    .auth-card { width: 440px; max-width: 100%; background: var(--card); border-radius: var(--radius-lg); box-shadow: 0 24px 80px rgba(0,0,0,.4); overflow: hidden; }
    .auth-header { background: linear-gradient(135deg, var(--navy-dark), var(--navy)); padding: 2rem 2rem 1.5rem; text-align: center; }
    .auth-header .logo { width: 52px; height: 52px; background: linear-gradient(135deg, var(--blue), #60a5fa); border-radius: 14px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; color: #fff; margin: 0 auto 1rem; box-shadow: 0 8px 24px rgba(37,99,235,.4); }
    .auth-header h2 { font-family: var(--font-head); color: #fff; font-size: 1.4rem; }
    .auth-header p { color: rgba(255,255,255,.55); font-size: .85rem; margin-top: .3rem; }
    .auth-tabs { display: flex; background: var(--bg); border-bottom: 2px solid var(--border); }
    .auth-tab { flex: 1; padding: .85rem; text-align: center; font-weight: 600; font-size: .88rem; cursor: pointer; background: none; border: none; color: var(--text-muted); transition: var(--transition); font-family: var(--font-body); border-bottom: 2px solid transparent; margin-bottom: -2px; }
    .auth-tab.active { color: var(--blue); border-bottom-color: var(--blue); background: var(--card); }
    .auth-body { padding: 2rem; }
    .auth-pane { display: none; } .auth-pane.active { display: block; animation: fadeUp .3s ease; }
    .divider { display: flex; align-items: center; gap: 1rem; margin: 1.2rem 0; }
    .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: var(--border); }
    .divider span { font-size: .78rem; color: var(--text-muted); white-space: nowrap; }
    .back-link { display: block; text-align: center; margin-top: 1.5rem; font-size: .82rem; color: rgba(255,255,255,.6); }
    .back-link a { color: #93c5fd; }
    .pw-wrap { position: relative; }
    .pw-wrap .toggle-pw { position: absolute; right: .9rem; top: 50%; transform: translateY(-50%); background: none; border: none; color: var(--text-muted); cursor: pointer; font-size: .9rem; }
    .input-icon { position: relative; }
    .input-icon i { position: absolute; left: .9rem; top: 50%; transform: translateY(-50%); color: var(--text-muted); font-size: .9rem; }
    .input-icon .form-control { padding-left: 2.5rem; }
  </style>
</head>
<body>

<div class="auth-card">
  <!-- Header -->
  <div class="auth-header">
    <div class="logo"><i class="fas fa-lightbulb"></i></div>
    <h2>Welcome to IdeaMarket</h2>
    <p>The marketplace where great ideas find their future</p>
  </div>

  <!-- Tabs -->
  <div class="auth-tabs">
    <button class="auth-tab <?= $tab==='login'?'active':'' ?>" data-auth="login">
      <i class="fas fa-sign-in-alt me-1"></i> Login
    </button>
    <button class="auth-tab <?= $tab==='register'?'active':'' ?>" data-auth="register">
      <i class="fas fa-user-plus me-1"></i> Register
    </button>
  </div>

  <div class="auth-body">

    <?php if ($flash): ?>
      <div class="alert alert-<?= $flash['type'] ?>">
        <i class="fas <?= $flash['type']==='success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
        <?= htmlspecialchars($flash['msg']) ?>
      </div>
    <?php endif; ?>

    <!-- Login Pane -->
    <div class="auth-pane <?= $tab==='login'?'active':'' ?>" id="pane-login">
      <form method="POST" id="loginForm" novalidate>
        <input type="hidden" name="action" value="login">
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-icon">
            <i class="fas fa-envelope"></i>
            <input type="email" name="email" class="form-control" placeholder="you@example.com" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
          <span class="form-error">Please enter a valid email.</span>
        </div>
        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-icon pw-wrap">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" id="loginPw" class="form-control" placeholder="••••••••" required>
            <button type="button" class="toggle-pw" onclick="togglePw('loginPw',this)"><i class="fas fa-eye"></i></button>
          </div>
          <span class="form-error">Password is required.</span>
        </div>
        <div style="display:flex;justify-content:flex-end;margin-bottom:1.2rem;">
          <a href="#" style="font-size:.82rem;color:var(--blue);">Forgot password?</a>
        </div>
        <button type="submit" class="btn btn-primary w-100 btn-lg">
          <i class="fas fa-sign-in-alt"></i> Log In
        </button>
        <div class="divider"><span>OR</span></div>
        <button type="button" class="btn btn-outline w-100" onclick="switchTab('register')">
          <i class="fas fa-user-plus"></i> Create New Account
        </button>
      </form>
    </div>

    <!-- Register Pane -->
    <div class="auth-pane <?= $tab==='register'?'active':'' ?>" id="pane-register">
      <form method="POST" id="registerForm" novalidate>
        <input type="hidden" name="action" value="register">
        <div class="form-group">
          <label class="form-label">Full Name</label>
          <div class="input-icon">
            <i class="fas fa-user"></i>
            <input type="text" name="full_name" class="form-control" placeholder="John Doe" required
                   value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
          </div>
          <span class="form-error">Full name is required.</span>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address</label>
          <div class="input-icon">
            <i class="fas fa-envelope"></i>
            <input type="email" name="email" class="form-control" placeholder="you@example.com" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
          <span class="form-error">Valid email required.</span>
        </div>
        <div class="form-group">
          <label class="form-label">Password <span style="font-weight:400;color:var(--text-muted);font-size:.78rem;">(min. 8 characters)</span></label>
          <div class="input-icon pw-wrap">
            <i class="fas fa-lock"></i>
            <input type="password" name="password" id="regPw" class="form-control" placeholder="••••••••" required minlength="8">
            <button type="button" class="toggle-pw" onclick="togglePw('regPw',this)"><i class="fas fa-eye"></i></button>
          </div>
          <span class="form-error">Password must be at least 8 characters.</span>
        </div>
        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <div class="input-icon pw-wrap">
            <i class="fas fa-lock"></i>
            <input type="password" name="confirm_password" id="confPw" class="form-control" placeholder="••••••••" required>
            <button type="button" class="toggle-pw" onclick="togglePw('confPw',this)"><i class="fas fa-eye"></i></button>
          </div>
          <span class="form-error">Passwords do not match.</span>
        </div>
        <div class="form-group" style="margin-top:.5rem;">
          <label style="display:flex;align-items:flex-start;gap:.6rem;cursor:pointer;font-size:.82rem;color:var(--text-muted);">
            <input type="checkbox" required style="margin-top:.2rem;"> 
            I agree to the <a href="#" style="color:var(--blue);">&nbsp;Terms of Service</a>&nbsp;and&nbsp;<a href="#" style="color:var(--blue);">Privacy Policy</a>
          </label>
        </div>
        <button type="submit" class="btn btn-primary w-100 btn-lg">
          <i class="fas fa-rocket"></i> Create Account
        </button>
        <div class="divider"><span>OR</span></div>
        <button type="button" class="btn btn-outline w-100" onclick="switchTab('login')">
          <i class="fas fa-sign-in-alt"></i> Already Have an Account
        </button>
      </form>
    </div>

  </div><!-- /auth-body -->
</div><!-- /auth-card -->

<p class="back-link"><a href="index.php"><i class="fas fa-arrow-left me-1"></i>Back to IdeaMarket</a></p>

<script src="assets/js/app.js"></script>
<script>
/* ── Tab switching ─────────────────────────── */
function switchTab(tab) {
  document.querySelectorAll('.auth-tab').forEach(t => t.classList.toggle('active', t.dataset.auth === tab));
  document.querySelectorAll('.auth-pane').forEach(p => p.classList.toggle('active', p.id === 'pane-' + tab));
}
document.querySelectorAll('.auth-tab').forEach(btn => {
  btn.addEventListener('click', () => switchTab(btn.dataset.auth));
});

/* ── Password show/hide toggle ─────────────── */
function togglePw(id, btn) {
  const inp  = document.getElementById(id);
  const icon = btn.querySelector('i');
  if (!inp) return;
  inp.type   = inp.type === 'password' ? 'text' : 'password';
  icon.className = inp.type === 'text' ? 'fas fa-eye-slash' : 'fas fa-eye';
}

/* ── Show field error ──────────────────────── */
function showError(input, msg) {
  input.classList.add('error');
  // Find the next .form-error sibling — walk up to .form-group then look inside
  const group = input.closest('.form-group');
  if (!group) return;
  const errSpan = group.querySelector('.form-error');
  if (errSpan) { errSpan.textContent = msg; errSpan.style.display = 'block'; }
}
function clearError(input) {
  input.classList.remove('error');
  const group = input.closest('.form-group');
  if (!group) return;
  const errSpan = group.querySelector('.form-error');
  if (errSpan) { errSpan.style.display = 'none'; }
}

/* ── Login form validation ─────────────────── */
document.getElementById('loginForm').addEventListener('submit', function(e) {
  let valid = true;
  const email = this.querySelector('[name="email"]');
  const pw    = this.querySelector('[name="password"]');

  clearError(email); clearError(pw);

  if (!email.value.trim()) {
    showError(email, 'Please enter your email address.'); valid = false;
  } else if (!/\S+@\S+\.\S+/.test(email.value)) {
    showError(email, 'Please enter a valid email address.'); valid = false;
  }
  if (!pw.value) {
    showError(pw, 'Please enter your password.'); valid = false;
  }
  if (!valid) e.preventDefault();
});

/* ── Register form validation ──────────────── */
document.getElementById('registerForm').addEventListener('submit', function(e) {
  let valid = true;
  const name    = this.querySelector('[name="full_name"]');
  const email   = this.querySelector('[name="email"]');
  const pw      = this.querySelector('[name="password"]');
  const conf    = this.querySelector('[name="confirm_password"]');
  const terms   = this.querySelector('[type="checkbox"]');

  clearError(name); clearError(email); clearError(pw); clearError(conf);

  if (!name.value.trim() || name.value.trim().length < 2) {
    showError(name, 'Full name must be at least 2 characters.'); valid = false;
  }
  if (!email.value.trim()) {
    showError(email, 'Please enter your email address.'); valid = false;
  } else if (!/\S+@\S+\.\S+/.test(email.value)) {
    showError(email, 'Please enter a valid email address.'); valid = false;
  }
  if (!pw.value || pw.value.length < 8) {
    showError(pw, 'Password must be at least 8 characters.'); valid = false;
  }
  if (!conf.value) {
    showError(conf, 'Please confirm your password.'); valid = false;
  } else if (pw.value !== conf.value) {
    showError(conf, 'Passwords do not match. Please try again.'); valid = false;
  }
  if (terms && !terms.checked) {
    alert('Please accept the Terms of Service and Privacy Policy to continue.');
    valid = false;
  }
  if (!valid) e.preventDefault();
});

/* ── Clear error on input ──────────────────── */
document.querySelectorAll('.form-control').forEach(input => {
  input.addEventListener('input', function() { clearError(this); });
});
</script>
</body>
</html>
