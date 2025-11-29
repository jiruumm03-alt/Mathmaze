<?php
// dashboard_teacher.php
require_once __DIR__ . '/auth.php';
require_login();
if (!is_teacher() && !is_admin()) {
    die("Access denied.");
}

$pdo = pdo();
$current = current_user();
$teacher_id = $current['id'];
$grade = $current['grade_level'] ?? null;

// if admin viewing, optionally allow select teacher
if (is_admin() && isset($_GET['tid'])) {
    $teacher_id = intval($_GET['tid']);
    $stmt = $pdo->prepare("SELECT grade_level FROM teachers WHERE id = :id LIMIT 1");
    $stmt->execute([':id'=>$teacher_id]);
    $grade = $stmt->fetchColumn();
}

// fetch students for this teacher (by teacher_id or grade)
$students = [];
if ($teacher_id) {
    $stmt = $pdo->prepare("SELECT id,username,student_name,grade_level,teacher_id FROM students WHERE teacher_id = :tid OR grade_level = :g ORDER BY id");
    $stmt->execute([':tid'=>$teacher_id, ':g'=>$grade]);
    $students = $stmt->fetchAll();
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Teacher Dashboard</title></head>
<body>
  <h1>Teacher Dashboard</h1>
  <p>Welcome, <?=htmlspecialchars($current['name'] ?? $current['username'])?> — <a href="logout.php">Logout</a> | <a href="assignments.php">Assignments</a></p>

  <h2>My Students (Grade <?=intval($grade)?>)</h2>
  <?php if ($students): ?>
    <table border="1" cellpadding="6">
      <tr><th>ID</th><th>Username</th><th>Name</th><th>Grade</th><th>Progress</th></tr>
      <?php foreach($students as $s): 
           // fetch last progress:
           $p = pdo()->prepare("SELECT level,score,time_spent,date_updated FROM progress WHERE student_id = :sid ORDER BY date_updated DESC LIMIT 1");
           $p->execute([':sid'=>$s['id']]);
           $pr = $p->fetch();
      ?>
        <tr>
          <td><?=intval($s['id'])?></td>
          <td><?=htmlspecialchars($s['username'])?></td>
          <td><?=htmlspecialchars($s['student_name'])?></td>
          <td><?=intval($s['grade_level'])?></td>
          <td>
            <?php if($pr): ?>
              Level <?=intval($pr['level'])?> — Score <?=intval($pr['score'])?> — <?=htmlspecialchars($pr['date_updated'])?>
            <?php else: ?>
              No progress
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach;?>
    </table>
  <?php else: ?>
    <p>No students assigned.</p>
  <?php endif; ?>
</body>
</html>
