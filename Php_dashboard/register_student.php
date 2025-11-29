<?php
// api/register_student.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

$pdo = pdo();

// Expect POST JSON or form: username, student_name (optional), age, grade_level, roblox_userid (optional)
$input = $_POST;
if (empty($input)) {
    $body = file_get_contents('php://input');
    $json = json_decode($body, true);
    if (is_array($json)) $input = $json;
}

$username = trim($input['username'] ?? '');
$student_name = trim($input['student_name'] ?? $username);
$age = isset($input['age']) ? intval($input['age']) : null;
$grade = isset($input['grade_level']) ? intval($input['grade_level']) : null;
$roblox_userid = isset($input['roblox_userid']) ? intval($input['roblox_userid']) : null;

if ($username === '' || !$grade) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'username and grade_level required']);
    exit;
}

// Check if username already exists
$stmt = $pdo->prepare("SELECT id, api_token FROM students WHERE username = :u LIMIT 1");
$stmt->execute([':u'=>$username]);
$existing = $stmt->fetch();

if ($existing) {
    // If exists, return student_id and token (idempotent)
    echo json_encode(['ok'=>true,'message'=>'already_exists','student_id'=>intval($existing['id']),'api_token'=>$existing['api_token']]);
    exit;
}

// create a secure random token
$token = bin2hex(random_bytes(32));

$stmt = $pdo->prepare("INSERT INTO students (username, password, student_name, grade_level, created_at, api_token) VALUES (:u, '', :n, :g, NOW(), :tk)");
$stmt->execute([':u'=>$username, ':n'=>$student_name, ':g'=>$grade, ':tk'=>$token]);
$student_id = (int)$pdo->lastInsertId();

// optional tie to roblox_accounts
if ($roblox_userid) {
    try {
        $ins = $pdo->prepare("INSERT IGNORE INTO roblox_accounts (roblox_userid, student_id) VALUES (:rid, :sid)");
        $ins->execute([':rid'=>$roblox_userid, ':sid'=>$student_id]);
    } catch (Exception $e) { /* ignore */ }
}

echo json_encode(['ok'=>true,'student_id'=>$student_id,'api_token'=>$token]);
