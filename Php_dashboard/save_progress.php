<?php
// api/save_game_progress.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . 'db.php';
$pdo = pdo();

$input = $_POST;
if (empty($input)) {
    $body = file_get_contents('php://input');
    $json = json_decode($body, true);
    if (is_array($json)) $input = $json;
}

$student_id = isset($input['student_id']) ? intval($input['student_id']) : 0;
$token = trim($input['api_token'] ?? '');
$grade_level = isset($input['grade_level']) ? intval($input['grade_level']) : null;
$level = isset($input['level']) ? intval($input['level']) : 0;
$score = isset($input['score']) ? intval($input['score']) : 0;
$time_spent = isset($input['time_spent']) ? floatval($input['time_spent']) : 0.0;
$puzzles_completed = isset($input['puzzles_completed']) ? intval($input['puzzles_completed']) : 0;
$items = isset($input['items']) ? $input['items'] : []; // expect JSON/array
$coins = isset($input['coins']) ? intval($input['coins']) : 0;
$maze_sections = isset($input['maze_sections']) ? $input['maze_sections'] : [];

if (!$student_id || $token === '') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'student_id and api_token required']);
    exit;
}

// Validate token
$stmt = $pdo->prepare("SELECT id FROM students WHERE id=:id AND api_token = :tk LIMIT 1");
$stmt->execute([':id'=>$student_id, ':tk'=>$token]);
if (!$stmt->fetch()) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'unauthorized']);
    exit;
}

// Insert progress record
$ins = $pdo->prepare("INSERT INTO progress (student_id, grade_level, level, score, time_spent, date_updated)
    VALUES (:sid, :g, :lvl, :score, :tsp, NOW())");
$ins->execute([':sid'=>$student_id, ':g'=>$grade_level, ':lvl'=>$level, ':score'=>$score, ':tsp'=>$time_spent]);

// Update students aggregated fields (items/coins/puzzles/maze sections)
try {
    $items_json = json_encode($items, JSON_UNESCAPED_UNICODE);
    $maze_json = json_encode($maze_sections, JSON_UNESCAPED_UNICODE);

    $upd = $pdo->prepare("UPDATE students SET 
        grade_level = COALESCE(:g, grade_level),
        puzzles_completed = GREATEST(COALESCE(puzzles_completed,0), :pc),
        items = :items,
        coins = :coins,
        maze_sections = :maze
      WHERE id = :sid");
    $upd->execute([
      ':g' => $grade_level,
      ':pc' => $puzzles_completed,
      ':items' => $items_json,
      ':coins' => $coins,
      ':maze' => $maze_json,
      ':sid' => $student_id
    ]);
} catch (Exception $e) {
    // ignore JSON issues but still respond ok
}

echo json_encode(['ok'=>true,'message'=>'saved']);
