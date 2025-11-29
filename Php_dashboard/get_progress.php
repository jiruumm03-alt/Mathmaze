<?php
// api/get_game_progress.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';
$pdo = pdo();

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$token = trim($_GET['api_token'] ?? '');

if (!$student_id || $token === '') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'student_id and api_token required']);
    exit;
}

// Validate token
$stmt = $pdo->prepare("SELECT id, student_name, grade_level, puzzles_completed, items, coins, maze_sections FROM students WHERE id=:id AND api_token = :tk LIMIT 1");
$stmt->execute([':id'=>$student_id, ':tk'=>$token]);
$student = $stmt->fetch();
if (!$student) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'unauthorized']);
    exit;
}

$limit = 20;
$stmt = $pdo->prepare("SELECT level,score,time_spent,date_updated,grade_level FROM progress WHERE student_id = :sid ORDER BY date_updated DESC LIMIT :lim");
$stmt->bindValue(':sid', $student_id, PDO::PARAM_INT);
$stmt->bindValue(':lim', (int)$limit, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

echo json_encode(['ok'=>true,'student'=>$student,'progress'=>$rows]);
