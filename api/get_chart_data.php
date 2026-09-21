<?php
/* =============================================
   API: get_chart_data.php
   Returns time-series data for charts
   ============================================= */
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
if (!isLoggedIn()) { jsonResponse(['error' => 'Unauthorized'], 401); }

$period = (int)($_GET['period'] ?? 7);
$period = in_array($period, [7, 30, 90]) ? $period : 7;

$userId  = (int)($_GET['user_id'] ?? 0);
$isAdmin = isAdmin();

if ($period === 7) {
    $fmt   = '%a'; // Mon, Tue...
    $labels = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    $start = date('Y-m-d', strtotime('-6 days'));
} elseif ($period === 30) {
    $fmt   = '%b %e';
    $labels = [];
    for ($i = $period-1; $i >= 0; $i--) $labels[] = date('M j', strtotime("-$i days"));
    $start = date('Y-m-d', strtotime('-29 days'));
} else {
    $fmt   = '%b %Y';
    $labels = [];
    for ($i = 2; $i >= 0; $i--) $labels[] = date('M Y', strtotime("-$i months"));
    $start = date('Y-m-d', strtotime('-89 days'));
}

$userFilter = $userId && !$isAdmin ? "AND user_id = $userId" : '';

$statuses = ['pending','review','approved','funded'];
$result   = ['labels' => $labels];

foreach ($statuses as $status) {
    $stmt = $pdo->prepare("
        SELECT DATE(created_at) AS d, COUNT(*) AS n
        FROM ideas
        WHERE created_at >= ? AND status = ? $userFilter
        GROUP BY DATE(created_at)
        ORDER BY d ASC
    ");
    $stmt->execute([$start . ' 00:00:00', $status]);
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $data = [];
    for ($i = $period-1; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i days"));
        $data[] = (int)($rows[$day] ?? 0);
    }
    $result[$status] = $period <= 30 ? $data : array_slice($data, 0, 3);
}

jsonResponse($result);
