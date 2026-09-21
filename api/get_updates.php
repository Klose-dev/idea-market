<?php
/* =============================================
   API: get_updates.php
   Returns live dashboard stats + activity
   ============================================= */
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

$isAdmin = isset($_GET['admin']) && isAdmin();
$userId  = (int)($_GET['user_id'] ?? 0);

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$response = [];

if ($isAdmin) {
    $response['stats']    = getAdminStats($pdo);
    $response['activity'] = getActivity($pdo, 8);
} elseif ($userId) {
    $response['stats']    = getUserStats($pdo, $userId);
    $response['activity'] = getActivity($pdo, 8, $userId);
}

// Unread notifications
$notifStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
$notifStmt->execute([$_SESSION['user_id']]);
$response['unread_notifications'] = (int)$notifStmt->fetchColumn();

echo json_encode($response);
