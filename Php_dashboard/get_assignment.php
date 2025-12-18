<?php
// get_assignment.php — Returns the latest assignment for a specific grade (for Roblox)

// ⚠ IMPORTANT: This file must be publicly accessible to Roblox.
// No session login is required.
// But we include a lightweight shared-key security check.

require_once __DIR__ . '/config.php';

$pdo = pdo();

// OPTIONAL SECURITY (recommended):
// Roblox must send ?key=YOUR_SECRET_KEY
$API_KEY = "MATHMAZE_SECURE_KEY_2025"; // <- Change to your own secret

if (!isset($_GET['key']) || $_GET['key'] !== $API_KEY) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// Grade required
$grade = intval($_GET['grade'] ?? 0);
if ($grade <= 0) {
    echo json_encode(["error" => "Invalid grade"]);
    exit;
}

// Fetch the latest assignment for this grade
$stmt = $pdo->prepare("
    SELECT id, title, questions_json
    FROM assignments
    WHERE grade_level = :g
    ORDER BY created_at DESC
    LIMIT 1
");
$stmt->execute([":g" => $grade]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode([
        "assignment" => null,
        "message" => "No assignment found for this grade"
    ]);
    exit;
}

// Return JSON to Roblox
echo json_encode([
    "assignment" => [
        "id"        => intval($row["id"]),
        "title"     => $row["title"],
        "questions" => json_decode($row["questions_json"], true)
    ]
], JSON_UNESCAPED_UNICODE);
