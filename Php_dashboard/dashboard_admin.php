<?php
// dashboard_admin.php
require_once __DIR__ . '/auth.php';
require_login();
if (!is_admin()) die("Access denied.");

$pdo = pdo();

// fetch counts
$counts = [];
foreach (['students','teachers','progress'] as $t) {
    $stmt = $pdo->query("SELECT COUNT(*) as c FROM {$t}");
    $counts[$t] = $stmt->fetchColumn();
}

// fetch teachers
$stmt = $pdo->query("SELECT id,username,full_name,grade_level,created_at FROM teachers ORDER BY grade_level");
$teachers = $stmt->fetchAll();

?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Admin Dashboard</title></head>
<body>
  <h1>Admin Dashboard</h1>
  <p>Welcome, <?=htmlspecialchars(current_user()['name'])?> — <a href="logout.php">Logout</a></p>
  <p>
    Students: <?=intval($counts['students'])?> |
    Teachers: <?=intval($counts['teachers'])?> |
    Progress records: <?=intval($counts['progress'])?>
  </p>

  <h2>Teachers</h2>
  <p><a href="register.php">Create New Teacher</a> | <a href="assignments.php">Assignments</a></p>
  <table border="1" cellpadding="6">
    <tr><th>ID</th><th>Username</th><th>Full Name</th><th>Grade</th><th>Created</th></tr>
    <?php foreach($teachers as $r): ?>
      <tr>
        <td><?=intval($r['id'])?></td>
        <td><?=htmlspecialchars($r['username'])?></td>
        <td><?=htmlspecialchars($r['full_name'])?></td>
        <td><?=intval($r['grade_level'])?></td>
        <td><?=htmlspecialchars($r['created_at'])?></td>
      </tr>
    <?php endforeach; ?>
  </table>

  <h2>Quick student preview</h2>
  <?php
  $s = $pdo->query("SELECT id,username,student_name,grade_level,teacher_id FROM students LIMIT 20")->fetchAll();
  if ($s): ?>
    <table border="1" cellpadding="6"><tr><th>ID</th><th>Username</th><th>Name</th><th>Grade</th><th>Teacher ID</th></tr>
    <?php foreach($s as $r): ?>
      <tr>
        <td><?=intval($r['id'])?></td>
        <td><?=htmlspecialchars($r['username'])?></td>
        <td><?=htmlspecialchars($r['student_name'])?></td>
        <td><?=intval($r['grade_level'])?></td>
        <td><?=($r['teacher_id']===null? 'NULL' : intval($r['teacher_id']))?></td>
      </tr>
    <?php endforeach; ?>
    </table>
  <?php else: ?>
    <p>No students yet.</p>
  <?php endif; ?>
</body>
</html>
