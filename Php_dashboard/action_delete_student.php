<?php
require_once __DIR__ . '/auth.php';
require_login();
if (!is_admin()) die("Access denied.");

$pdo = pdo();

// FIX: Read POST instead of GET
$id = intval($_POST['id'] ?? 0);

if ($id > 0) {

    // Delete progress first
    $stmt = $pdo->prepare("DELETE FROM progress WHERE student_id = ?");
    $stmt->execute([$id]);

    // Delete student
    $stmt = $pdo->prepare("DELETE FROM students WHERE id = ?");
    $stmt->execute([$id]);
}

header("Location: dashboard_admin.php");
exit;
