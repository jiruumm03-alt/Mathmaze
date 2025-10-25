<?php
// dashboard.php - shows progress for the logged-in teacher's grade
session_start();
if (!isset($_SESSION['teacher'])) {
  header('Location: login.php');
  exit();
}
require 'db.php';
$grade = intval($_SESSION['grade']);
$studentTable = "grade" . $grade . "_students";
$progressTable = "grade" . $grade . "_progress";

$sql = "SELECT s.full_name, s.username, p.level, p.score, p.time_spent, p.date_updated 
        FROM `$progressTable` p JOIN `$studentTable` s ON p.student_id = s.id
        ORDER BY p.date_updated DESC LIMIT 500";
$result = $conn->query($sql);
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Dashboard - Grade <?= $grade ?></title>
<link rel="stylesheet" href="styles.css"></head><body>
<div class="box">
  <h2>MathMaze Teacher Dashboard - Grade <?= $grade ?></h2>
  <p>Welcome, <?= htmlspecialchars($_SESSION['teacher']) ?> | <a href="logout.php">Logout</a></p>
  <table>
  <tr><th>Student</th><th>Level</th><th>Score</th><th>Time</th><th>Date</th></tr>
  <?php while($row = $result->fetch_assoc()) { ?>
    <tr>
      <td><?= htmlspecialchars($row['full_name'] ?: $row['username']) ?></td>
      <td><?= $row['level'] ?></td>
      <td><?= $row['score'] ?></td>
      <td><?= $row['time_spent'] ?></td>
      <td><?= $row['date_updated'] ?></td>
    </tr>
  <?php } ?>
  </table>
</div>
</body></html>
