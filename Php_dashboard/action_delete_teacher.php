<?php
require_once __DIR__ . '/auth.php';
require_login();
if (!is_admin()) die("Access denied.");

$pdo = pdo();

// FIX: Read POST instead of GET
$id = intval($_POST['id'] ?? 0);

if ($id > 0) {

    // Unassign students linked to this teacher
    $pdo->prepare("UPDATE students SET teacher_id = NULL WHERE teacher_id = ?")
        ->execute([$id]);

    // Delete teacher
    $pdo->prepare("DELETE FROM teachers WHERE id = ?")
        ->execute([$id]);
}

header("Location: dashboard_admin.php");
exit;
