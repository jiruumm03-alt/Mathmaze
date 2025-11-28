<?php
session_start();
require 'db.php';
if (!isset($_SESSION['username']) || $_SESSION['role'] != 'super_admin') {
    header('Location: index.php');
    exit();
}

// Handle assignment form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
    $tbl = "mathmaze_db.grade{$g}_teachers";
    $res = $conn->query("SELECT id, username, full_name FROM $tbl");
    $teachers = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) $teachers[] = $r;
    }
    $teachers_by_grade[$g] = $teachers;
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Manage Students</title></head>
<body>
<h2>Manage Students - Assign to Teacher</h2>
<table border="1" cellpadding="6">
<tr><th>Student</th><th>Grade</th><th>Assigned Teacher</th><th>Action</th></tr>
<?php while ($s = $students->fetch_assoc()): ?>
<tr>
<td><?= htmlspecialchars($s['full_name']) ?></td>
<td><?= htmlspecialchars($s['grade_level']) ?></td>
<td>
<?php
$tg = $s['teacher_grade'];
$tid = $s['teacher_id'];
if ($tg && $tid) {
    // find teacher name
    $tname = '';
    foreach($teachers_by_grade[$tg] as $t) if ($t['id']==$tid) $tname = $t['full_name'] ?: $t['username'];
    echo htmlspecialchars($tname);
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
<?php
$g = $s['grade_level'];
foreach($teachers_by_grade[$g] as $t):
?>
<option value="<?= $t['id'] ?>" <?= ($s['teacher_id']==$t['id'])?'selected':'' ?>><?= htmlspecialchars($t['full_name'] ?: $t['username']) ?></option>
<?php endforeach; ?>
</select>
<button type="submit">Save</button>
</form>
</td>
</tr>
<?php endwhile; ?>
</table>
</body>
</html>
