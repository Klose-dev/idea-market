<?php
/* =============================================
   API: vote_idea.php
   Toggle vote on an idea
   ============================================= */
require_once '../includes/auth.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    jsonResponse(['success' => false, 'message' => 'Please log in to vote.'], 401);
}

$input  = json_decode(file_get_contents('php://input'), true);
$ideaId = (int)($input['idea_id'] ?? $_POST['idea_id'] ?? 0);

if (!$ideaId) jsonResponse(['success' => false, 'message' => 'Invalid idea.'], 400);

// Check if already voted
$check = $pdo->prepare("SELECT id FROM votes WHERE idea_id=? AND user_id=?");
$check->execute([$ideaId, $_SESSION['user_id']]);
$existing = $check->fetch();

if ($existing) {
    // Remove vote
    $pdo->prepare("DELETE FROM votes WHERE idea_id=? AND user_id=?")->execute([$ideaId, $_SESSION['user_id']]);
    $pdo->prepare("UPDATE ideas SET vote_count = GREATEST(0, vote_count-1) WHERE id=?")->execute([$ideaId]);
    $voted = false;
} else {
    // Add vote
    $pdo->prepare("INSERT INTO votes (idea_id, user_id, created_at) VALUES (?,?,NOW())")->execute([$ideaId, $_SESSION['user_id']]);
    $pdo->prepare("UPDATE ideas SET vote_count = vote_count+1 WHERE id=?")->execute([$ideaId]);
    $voted = true;
}

// Get updated count
$countStmt = $pdo->prepare("SELECT vote_count FROM ideas WHERE id=?");
$countStmt->execute([$ideaId]);
$count = (int)$countStmt->fetchColumn();

jsonResponse(['success' => true, 'voted' => $voted, 'vote_count' => $count]);
