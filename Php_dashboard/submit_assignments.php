<?php
// submit_assignment.php — Receives student assignment answers from Roblox

require_once __DIR__ . '/config.php';
$pdo = pdo();

// SECURITY KEY — Roblox must include this in the request
$API_KEY = "MATHMAZE_SECURE_KEY_2025"; // CHANGE THIS

if (!isset($_POST['key']) || $_POST['key'] !== $API_KEY) {
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

// Required fields
$student_id     = intval($_POST["student_id"] ?? 0);
$assignment_id  = intval($_POST["assignment_id"] ?? 0);
$answers_json   = $_POST["answers_json"] ?? "[]";
$score          = floatval($_POST["score"] ?? 0);
$time_spent     = floatval($_POST["time_spent"] ?? 0);

if ($student_id <= 0 || $assignment_id <= 0) {
    echo json_encode(["error" => "Missing student_id or assignment_id"]);
    exit;
}

// Validate answers JSON
$decoded = json_decode($answers_json, true);
if (!is_array($decoded)) {
    echo json_encode(["error" => "Invalid answers_json"]);
    exit;
}

// Optional: prevent duplicate submissions
$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM student_assignments
    WHERE student_id = :sid AND assignment_id = :aid
");
$stmt->execute([
    ":sid" => $student_id,
    ":aid" => $assignment_id
]);
$exists = $stmt->fetchColumn();

if ($exists > 0) {
    echo json_encode(["error" => "Already submitted"]);
    exit;
}

// Insert submission
try {
    $stmt = $pdo->prepare("
        INSERT INTO student_assignments
            (student_id, assignment_id, answers_json, score, time_spent, submitted_at)
        VALUES
            (:sid, :aid, :ans, :score, :time, NOW())
    ");

    $stmt->execute([
        ":sid"  => $student_id,
        ":aid"  => $assignment_id,
        ":ans"  => json_encode($decoded, JSON_UNESCAPED_UNICODE),
        ":score"=> $score,
        ":time" => $time_spent
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Submission recorded successfully"
    ]);

} catch (Exception $e) {
    echo json_encode([
        "error" => "DB error",
        "details" => $e->getMessage()
    ]);
}
