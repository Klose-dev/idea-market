<?php
/* =============================================
   IDEA MARKET — AUTH & SESSION
   includes/auth.php
   ============================================= */
require_once __DIR__ . '/db.php';

session_start();

/* ── Helper functions ──────────────────────── */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function requireLogin(string $redirect = 'login.php'): void {
    if (!isLoggedIn()) {
        header("Location: $redirect");
        exit;
    }
}

function requireAdmin(): void {
    if (!isAdmin()) {
        header('Location: dashboard.php');
        exit;
    }
}

function currentUser(): array {
    return [
        'id'        => $_SESSION['user_id']   ?? null,
        'name'      => $_SESSION['user_name'] ?? '',
        'email'     => $_SESSION['user_email'] ?? '',
        'role'      => $_SESSION['role']       ?? 'user',
        'initials'  => strtoupper(substr($_SESSION['user_name'] ?? 'U', 0, 2)),
    ];
}

/* ── Register ──────────────────────────────── */
function register(PDO $pdo, string $name, string $email, string $password, string $confirm = ''): array {
    // Validate name
    if (strlen(trim($name)) < 2)
        return ['success' => false, 'message' => 'Full name must be at least 2 characters.'];

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        return ['success' => false, 'message' => 'Please enter a valid email address.'];

    // Validate password length
    if (strlen($password) < 8)
        return ['success' => false, 'message' => 'Password must be at least 8 characters.'];

    // Validate password confirmation (server-side guard)
    if ($confirm !== '' && $password !== $confirm)
        return ['success' => false, 'message' => 'Passwords do not match. Please try again.'];

    // Check for duplicate email
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch())
        return ['success' => false, 'message' => 'This email address is already registered. Try logging in.'];

    // Insert new user
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password_hash, role, created_at) VALUES (?, ?, ?, 'user', NOW())");
    $stmt->execute([trim($name), strtolower(trim($email)), $hash]);

    return ['success' => true, 'message' => 'Account created successfully! You can now log in.'];
}

/* ── Login ─────────────────────────────────── */
function login(PDO $pdo, string $email, string $password): array {
    // Sanitize
    $email = strtolower(trim($email));

    if (empty($email) || empty($password))
        return ['success' => false, 'message' => 'Please enter your email and password.'];

    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        return ['success' => false, 'message' => 'Please enter a valid email address.'];

    // Fetch user — check banned separately for better error message
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        return ['success' => false, 'message' => 'No account found with that email address.'];
    }

    if ((int)$user['is_banned'] === 1) {
        return ['success' => false, 'message' => 'This account has been suspended. Please contact support.'];
    }

    if (!password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'message' => 'Incorrect password. Please try again.'];
    }

    // Regenerate session ID to prevent fixation
    session_regenerate_id(true);

    // Store user info in session
    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['user_name']  = $user['full_name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['role']       = $user['role'];

    // Update last login timestamp in WAT
    $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);

    return ['success' => true, 'role' => $user['role']];
}

/* ── Logout ────────────────────────────────── */
function logout(): void {
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

/* ── CSRF ──────────────────────────────────── */
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
