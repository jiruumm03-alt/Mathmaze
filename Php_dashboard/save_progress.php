<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/db.php';
$pdo = pdo();

/* --------------------------------------------------
   1. Read JSON or POST (Roblox compatible)
-------------------------------------------------- */
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) {
        $input = $json;
    }
}

/* --------------------------------------------------
   2. Detect Roblox payload
-------------------------------------------------- */
$isRoblox = isset($input['userId']);

if ($isRoblox) {
    // 🔹 Roblox → normalize to existing structure
    $student_id = intval($input['userId']);
    $username   = trim($input['username'] ?? '');
    $grade_level = $input['grade'] ?? null;
    $level      = intval($input['level'] ?? 1);
    $stage      = intval($input['stage'] ?? 0);
    $score      = intval($input['points'] ?? 0);
    $exp        = intval($input['exp'] ?? 0);
    $age        = intval($input['age'] ?? 0);

    // Auto-trust Roblox (or add API key later)
    $token = 'ROBLOX_INTERNAL';

} else {
    // 🔹 Original web / mobile payload
    $student_id = intval($input['student_id'] ?? 0);
    $token = trim($input['api_token'] ?? '');
    $grade_level = isset($input['grade_level']) ? intval($input['grade_level']) : null;
    $level = intval($input['level'] ?? 0);
    $score = intval($input['score'] ?? 0);
    $exp = intval($input['exp'] ?? 0);
    $age = intval($input['age'] ?? 0);
    $stage = intval($input['stage'] ?? 0);
}

/* --------------------------------------------------
   3. Validate
-------------------------------------------------- */
if ($student_id <= 0) {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'invalid student_id / userId']);
    exit;
}

/* --------------------------------------------------
   4. Ensure student exists (Roblox auto-create)
-------------------------------------------------- */
$stmt = $pdo->prepare("SELECT id FROM students WHERE id = :id LIMIT 1");
$stmt->execute([':id'=>$student_id]);
$exists = $stmt->fetch();

if (!$exists) {
    $insStudent = $pdo->prepare("
        INSERT INTO students (id, username, grade_level, age)
        VALUES (:id, :u, :g, :a)
    ");
    $insStudent->execute([
        ':id'=>$student_id,
        ':u'=>$username,
        ':g'=>$grade_level,
        ':a'=>$age
    ]);
}

/* --------------------------------------------------
   5. Insert progress snapshot
-------------------------------------------------- */
$ins = $pdo->prepare("
    INSERT INTO progress
    (student_id, level, stage, score, exp, date_updated)
    VALUES (:sid, :lvl, :stg, :sc, :exp, NOW())
");
$ins->execute([
    ':sid'=>$student_id,
    ':lvl'=>$level,
    ':stg'=>$stage,
    ':sc'=>$score,
    ':exp'=>$exp
]);

/* --------------------------------------------------
   6. Update student aggregate
-------------------------------------------------- */
$upd = $pdo->prepare("
    UPDATE students SET
        username = COALESCE(:u, username),
        grade_level = COALESCE(:g, grade_level),
        age = COALESCE(:a, age)
    WHERE id = :sid
");
$upd->execute([
    ':u'=>$username,
    ':g'=>$grade_level,
    ':a'=>$age,
    ':sid'=>$student_id
]);

echo json_encode([
    'ok' => true,
    'source' => $isRoblox ? 'roblox' : 'web',
    'message' => 'progress saved'
]);
