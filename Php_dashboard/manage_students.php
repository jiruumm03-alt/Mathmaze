<?php
session_start();
require 'db.php';
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'super_admin') {
    header('Location: index.php');
    exit();
}

// Handle assignment form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_id'])) {
    $student_id = intval($_POST['student_id']);
    $teacher_grade = intval($_POST['teacher_grade']);
    $teacher_id = intval($_POST['teacher_id']);
    $stmt = $conn->prepare("UPDATE students SET teacher_grade = ?, teacher_id = ? WHERE id = ?");
    $stmt->bind_param('iii', $teacher_grade, $teacher_id, $student_id);
    $stmt->execute();
}

// Fetch students
$students = $conn->query("SELECT * FROM students ORDER BY grade_level, full_name");

// Fetch teachers per grade
$teachers_by_grade = [];
for ($g=3;$g<=6;$g++) {
    $tbl = $conn->prepare("SELECT id, username, full_name FROM teachers WHERE grade_level = ? ORDER BY full_name");
    $tbl->bind_param('i', $g);
    $tbl->execute();
    $res = $tbl->get_result();
    $teachers_by_grade[$g] = $res->fetch_all(MYSQLI_ASSOC);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width,initial-scale=1" />
<title>Manage Students</title>
<link rel="icon" href="favicon-32x32.png" />
<style>
body{font-family:'Segoe UI',Arial,sans-serif;margin:0;background:#f4f6f8;color:#333}
.header{background:linear-gradient(90deg,#4f46e5,#9333ea);color:white;padding:12px 20px;display:flex;justify-content:space-between}
.container{padding:80px 20px}
.card{background:white;padding:18px;border-radius:10px;box-shadow:0 2px 8px rgba(0,0,0,0.08)}
table{width:100%;border-collapse:collapse;margin-top:12px}
th,td{padding:8px;text-align:center;border-bottom:1px solid #eee}
select,input,button{padding:8px;border-radius:6px;border:1px solid #ddd}
button{background:#3b82f6;color:white;border:none;padding:8px 12px;border-radius:6px;cursor:pointer}
</style>
</head>
<body>
<div class="header"><div><img src="favicon-32x32.png" style="height:28px;vertical-align:middle"> <strong style="margin-left:8px">MathMaze Admin</strong></div><div><a href="dashboard.php" style="color:white;text-decoration:none">Dashboard</a></div></div>
<div class="container">
  <div class="card">
    <h2>Manage Students - Assign to Teacher</h2>
    <table>
      <tr><th>Student</th><th>Grade</th><th>Assigned Teacher</th><th>Action</th></tr>
      <?php while ($s = $students->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($s['full_name']) ?></td>
          <td><?= htmlspecialchars($s['grade_level']) ?></td>
          <td>
            <?php
              $tg = $s['teacher_grade']; $tid = $s['teacher_id'];
              if ($tg && $tid) {
                $tname = '';
                foreach($teachers_by_grade[$tg] as $t) if ($t['id']==$tid) $tname = $t['full_name'] ?: $t['username'];
                echo htmlspecialchars($tname ?: 'Unknown');
              } else {
                echo '<em>Unassigned</em>';
              }
            ?>
          </td>
          <td>
            <form method="post" style="display:inline">
              <input type="hidden" name="student_id" value="<?= $s['id'] ?>">
              <select name="teacher_grade" required>
                <option value="">Grade</option>
                <?php for($g=3;$g<=6;$g++): ?>
                  <option value="<?= $g ?>" <?= $s['grade_level']==$g?'selected':'' ?>><?= $g ?></option>
                <?php endfor; ?>
              </select>
              <select name="teacher_id" required>
                <option value="">Select Teacher</option>
                <?php $g = $s['grade_level']; foreach($teachers_by_grade[$g] as $t): ?>
                  <option value="<?= $t['id'] ?>" <?= ($s['teacher_id']==$t['id'])?'selected':'' ?>><?= htmlspecialchars($t['full_name'] ?: $t['username']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="submit">Save</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
    </table>
  </div>
</div>
</body>
</html>
