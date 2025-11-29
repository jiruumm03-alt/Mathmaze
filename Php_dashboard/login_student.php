<?php
// api/login_student.php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';

$pdo = pdo();

// Accept POST: username + api_token (token optional for first login)
$input = $_POST;
if (empty($input)) {
    $body = file_get_contents('php://input');
    $json = json_decode($body, true);
    if (is_array($json)) $input = $json;
}

$username = trim($input['username'] ?? '');
$token = trim($input['api_token'] ?? '');
$roblox_userid = isset($input['roblox_userid']) ? intval($input['roblox_userid']) : null;

if ($username === '') {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'username required']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, api_token, grade_level, student_name FROM students WHERE username = :u LIMIT 1");
$stmt->execute([':u'=>$username]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    echo json_encode(['ok'=>false,'error'=>'not_found']);
    exit;
}

// If token supplied, verify
if ($token !== '') {
    if (hash_equals($row['api_token'] ?? '', $token)) {
        // optionally tie roblox_userid
        if ($roblox_userid) {
            $ins = $pdo->prepare("INSERT IGNORE INTO roblox_accounts (roblox_userid, student_id) VALUES (:rid, :sid)");
            $ins->execute([':rid'=>$roblox_userid, ':sid'=>$row['id']]);
        }

        echo json_encode(['ok'=>true,'student_id'=>intval($row['id']),'grade_level'=>intval($row['grade_level']),'student_name'=>$row['student_name']]);
        exit;
    } else {
        http_response_code(403);
        echo json_encode(['ok'=>false,'error'=>'invalid_token']);
        exit;
    }
}

// If no token provided, return token to client for first-time storage (idempotent)
if (empty($row['api_token'])) {
    $newToken = bin2hex(random_bytes(32));
    $upd = $pdo->prepare("UPDATE students SET api_token = :tk WHERE id = :id");
    $upd->execute([':tk'=>$newToken,':id'=>$row['id']]);
    $row['api_token'] = $newToken;
}

echo json_encode(['ok'=>true,'student_id'=>intval($row['id']),'api_token'=>$row['api_token'],'grade_level'=>intval($row['grade_level']),'student_name'=>$row['student_name']]);
