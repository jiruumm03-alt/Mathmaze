<?php
// api/register_student.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

$pdo = pdo();

// Accept POST or JSON
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
    echo json_encode(['ok'=>false,'error'=>'username and grade_level required']);
    exit;
}

// Check if username already exists
$stmt = $pdo->prepare("SELECT id, api_token FROM students WHERE username = :u LIMIT 1");
$stmt->execute([':u'=>$username]);
$existing = $stmt->fetch();

if ($existing) {
    echo json_encode([
        'ok'=>true,
        'message'=>'already_exists',
        'student_id'=>intval($existing['id']),
        'api_token'=>$existing['api_token']
    ]);
    exit;
}

// Auto-find teacher for this grade
$teacher_id = null;
$findTeacher = $pdo->prepare("SELECT id FROM teachers WHERE grade_level = :g LIMIT 1");
$findTeacher->execute([':g'=>$grade]);
$tRow = $findTeacher->fetch();
if ($tRow) {
    $teacher_id = intval($tRow['id']);
}

// Create secure API token
$token = bin2hex(random_bytes(32));

// Insert new student
$stmt = $pdo->prepare("
    INSERT INTO students 
    (username, password, student_name, grade_level, teacher_id, created_at, api_token)
    VALUES (:u, '', :n, :g, :t, NOW(), :tk)
");

$stmt->execute([
    ':u'=>$username,
    ':n'=>$student_name,
    ':g'=>$grade,
    ':t'=>$teacher_id,
    ':tk'=>$token
]);

$student_id = (int)$pdo->lastInsertId();

// Optional tie to roblox_accounts
if ($roblox_userid) {
    try {
        $ins = $pdo->prepare("INSERT IGNORE INTO roblox_accounts (roblox_userid, student_id) VALUES (:rid, :sid)");
        $ins->execute([':rid'=>$roblox_userid, ':sid'=>$student_id]);
    } catch (Exception $e) {}
}

echo json_encode([
    'ok'=>true,
    'student_id'=>$student_id,
    'api_token'=>$token,
    'teacher_id'=>$teacher_id
]);
