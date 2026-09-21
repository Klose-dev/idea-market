<?php
/* =============================================
   IDEA MARKET — HELPER FUNCTIONS
   includes/functions.php
   ============================================= */
require_once __DIR__ . '/db.php';

/* ── Sanitize output ───────────────────────── */
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

/* ── Status Badge HTML ─────────────────────── */
function statusBadge(string $status): string {
    $map = [
        'pending'    => ['badge-pending',  'fa-clock',        'Pending'],
        'review'     => ['badge-review',   'fa-search',       'Under Review'],
        'approved'   => ['badge-approved', 'fa-check-circle', 'Approved'],
        'funded'     => ['badge-funded',   'fa-rocket',       'Funded'],
        'closed'     => ['badge-closed',   'fa-times-circle', 'Closed'],
    ];
    [$cls, $icon, $label] = $map[$status] ?? ['badge-review', 'fa-question', ucfirst($status)];
    return "<span class=\"badge $cls\"><i class=\"fas $icon\"></i> $label</span>";
}

/* ── Priority Badge HTML ───────────────────── */
function priorityBadge(string $priority): string {
    $map = [
        'high'   => 'badge-high',
        'medium' => 'badge-medium',
        'low'    => 'badge-low',
    ];
    $cls = $map[$priority] ?? 'badge-low';
    return "<span class=\"badge $cls\">" . ucfirst($priority) . "</span>";
}

/* ── Category Icon ─────────────────────────── */
function categoryIcon(string $cat): string {
    $icons = [
        'technology'  => 'fa-microchip',
        'social'      => 'fa-users',
        'health'      => 'fa-heartbeat',
        'education'   => 'fa-graduation-cap',
        'environment' => 'fa-leaf',
        'finance'     => 'fa-chart-line',
        'arts'        => 'fa-palette',
        'other'       => 'fa-lightbulb',
    ];
    return $icons[strtolower($cat)] ?? 'fa-lightbulb';
}

/* ── Generate unique Idea ID ───────────────── */
function generateIdeaId(): string {
    return 'IDEA-' . strtoupper(substr(md5(uniqid(rand(), true)), 0, 8));
}

/* ── Get dashboard stats (admin) ───────────── */
function getAdminStats(PDO $pdo): array {
    $stats = [];
    $rows = $pdo->query("
        SELECT
            COUNT(*) AS total,
            SUM(status='pending')  AS pending,
            SUM(status='review')   AS review,
            SUM(status='approved') AS approved,
            SUM(status='funded')   AS funded,
            SUM(status='closed')   AS closed
        FROM ideas
    ")->fetch();
    return [
        'total'    => $rows['total']    ?? 0,
        'pending'  => $rows['pending']  ?? 0,
        'review'   => $rows['review']   ?? 0,
        'approved' => $rows['approved'] ?? 0,
        'funded'   => $rows['funded']   ?? 0,
        'closed'   => $rows['closed']   ?? 0,
        'users'    => $pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn() ?? 0,
    ];
}

/* ── Get user stats ────────────────────────── */
function getUserStats(PDO $pdo, int $userId): array {
    $rows = $pdo->prepare("
        SELECT
            COUNT(*) AS total,
            SUM(status='pending')  AS pending,
            SUM(status='review')   AS review,
            SUM(status='approved') AS approved,
            SUM(status='funded')   AS funded
        FROM ideas WHERE user_id = ?
    ");
    $rows->execute([$userId]);
    return $rows->fetch() ?: ['total'=>0,'pending'=>0,'review'=>0,'approved'=>0,'funded'=>0];
}

/* ── Recent Ideas ──────────────────────────── */
function getRecentIdeas(PDO $pdo, int $limit = 8, int $userId = 0): array {
    if ($userId) {
        $stmt = $pdo->prepare("SELECT i.*, u.full_name FROM ideas i JOIN users u ON i.user_id=u.id WHERE i.user_id=? ORDER BY i.created_at DESC LIMIT ?");
        $stmt->execute([$userId, $limit]);
    } else {
        $stmt = $pdo->prepare("SELECT i.*, u.full_name FROM ideas i JOIN users u ON i.user_id=u.id ORDER BY i.created_at DESC LIMIT ?");
        $stmt->execute([$limit]);
    }
    return $stmt->fetchAll();
}

/* ── Activity log ──────────────────────────── */
function logActivity(PDO $pdo, int $ideaId, string $action, int $performedBy, string $status = ''): void {
    $pdo->prepare("INSERT INTO activity_log (idea_id, action, performed_by, status, timestamp) VALUES (?,?,?,?,NOW())")
        ->execute([$ideaId, $action, $performedBy, $status]);
}

/* ── Get recent activity ───────────────────── */
function getActivity(PDO $pdo, int $limit = 10, int $userId = 0): array {
    if ($userId) {
        $stmt = $pdo->prepare("SELECT al.*, i.title AS idea_title FROM activity_log al JOIN ideas i ON al.idea_id=i.id WHERE i.user_id=? ORDER BY al.timestamp DESC LIMIT ?");
        $stmt->execute([$userId, $limit]);
    } else {
        $stmt = $pdo->prepare("SELECT al.*, i.title AS idea_title, u.full_name FROM activity_log al JOIN ideas i ON al.idea_id=i.id JOIN users u ON al.performed_by=u.id ORDER BY al.timestamp DESC LIMIT ?");
        $stmt->execute([$limit]);
    }
    return $stmt->fetchAll();
}

/* ── Format date (Africa/Douala timezone) ───── */
function formatDate(string $date, string $fmt = 'M j, Y'): string {
    $dt = new DateTime($date, new DateTimeZone('Africa/Douala'));
    return $dt->format($fmt);
}

/* ── Format date with time (WAT) ───────────── */
function formatDateTime(string $date): string {
    $dt = new DateTime($date, new DateTimeZone('Africa/Douala'));
    return $dt->format('M j, Y · H:i') . ' WAT';
}

/* ── Time ago (WAT-aware) ──────────────────── */
function timeAgo(string $dateStr): string {
    $tz   = new DateTimeZone('Africa/Douala');
    $now  = new DateTime('now', $tz);
    $then = new DateTime($dateStr, $tz);
    $diff = $now->getTimestamp() - $then->getTimestamp();
    if ($diff < 0)      return 'just now';
    if ($diff < 60)     return 'just now';
    if ($diff < 3600)   return floor($diff / 60) . ' min ago';
    if ($diff < 86400)  return floor($diff / 3600) . ' hr ago';
    if ($diff < 604800) return floor($diff / 86400) . ' d ago';
    return formatDate($dateStr, 'M j, Y');
}

/* ── Current WAT time string ───────────────── */
function nowWAT(string $fmt = 'Y-m-d H:i:s'): string {
    return (new DateTime('now', new DateTimeZone('Africa/Douala')))->format($fmt);
}

/* ── Paginate ──────────────────────────────── */
function paginate(int $total, int $perPage, int $current): array {
    $pages = max(1, (int)ceil($total / $perPage));
    return ['total_pages' => $pages, 'current' => max(1, min($current, $pages)), 'offset' => ($current - 1) * $perPage];
}

/* ── AJAX response helper ──────────────────── */
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/* ── Flash message ─────────────────────────── */
function setFlash(string $type, string $msg): void { $_SESSION['flash'] = compact('type','msg'); }
function getFlash(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }
function renderFlash(): string {
    $f = getFlash();
    if (!$f) return '';
    $icons = ['success'=>'fa-check-circle','error'=>'fa-times-circle','info'=>'fa-info-circle','warning'=>'fa-exclamation-triangle'];
    $icon = $icons[$f['type']] ?? 'fa-info-circle';
    return "<div class=\"alert alert-{$f['type']}\"><i class=\"fas $icon\"></i> " . e($f['msg']) . "</div>";
}
