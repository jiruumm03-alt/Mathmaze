<?php
// save_progress.php
require_once __DIR__ . '/auth.php';
// This endpoint accepts:
// POST JSON or form: student_id, level, score, time_spent
// Authorization: session (teacher/admin) or an API key param (api_key)

$pdo = pdo();
$input = $_POST;
if (empty($input)) {
    $body = file_get_contents('php://input');
    $json = json_decode($body, true);
    if (is_array($json)) $input = $json;
}

$student_id = isset($input['student_id']) ? intval($input['student_id']) : 0;
$level = isset($input['level']) ? intval($input['level']) : 0;
$score = isset($input['score']) ? intval($input['score']) : 0;
$time_spent = isset($input['time_spent']) ? floatval($input['time_spent']) : 0.0;

if (!$student_id) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'student_id required']);
    exit;
}

// permission: either logged in (teacher/admin) or you can implement API key check
$authorized = false;
if (is_logged_in()) {
    $authorized = is_admin() || is_teacher();
} else if (!empty($_GET['api_key']) || !empty($input['api_key'])) {
    // implement a simple key check if you want (store key in .env)
    $k = $_GET['api_key'] ?? $input['api_key'];
    $env = @file_get_contents(__DIR__ . '/.env');
    if ($env && strpos($env, "API_KEY={$k}") !== false) {
        $authorized = true;
    }
}

if (!$authorized) {
    http_response_code(403);
    echo json_encode(['ok'=>false,'error'=>'unauthorized']);
    exit;
}

// Insert a new progress row
$stmt = $pdo->prepare("INSERT INTO progress (student_id, grade_level, level, score, time_spent, date_updated)
    SELECT :sid, grade_level, :lvl, :score, :tsp, NOW() FROM students WHERE id = :sid LIMIT 1");
$stmt->execute([':sid'=>$student_id,':lvl'=>$level,':score'=>$score,':tsp'=>$time_spent]);

echo json_encode(['ok'=>true,'message'=>'saved']);
