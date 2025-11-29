<?php
// get_progress.php
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json');
$pdo = pdo();

$sid = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
if (!$sid) {
    echo json_encode(['ok'=>false,'error'=>'student_id required']); exit;
}

// permission: teacher/admin or API key
if (!is_logged_in()) {
    // optional API key check
    $k = $_GET['api_key'] ?? null;
    $env = @file_get_contents(__DIR__ . '/.env');
    if (!($env && $k && strpos($env,"API_KEY={$k}") !== false)) {
        http_response_code(403);
        echo json_encode(['ok'=>false,'error'=>'unauthorized']); exit;
    }
}

$stmt = pdo()->prepare("SELECT * FROM progress WHERE student_id = :sid ORDER BY date_updated DESC");
$stmt->execute([':sid'=>$sid]);
$rows = $stmt->fetchAll();

echo json_encode(['ok'=>true,'data'=>$rows]);
