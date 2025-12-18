<?php
// student_report.php — printable student report (teacher or admin)
require_once __DIR__ . '/auth.php';
require_login();

$pdo = pdo();
$current = current_user();
$isAdmin = is_admin();
$isTeacher = is_teacher();

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { die("Invalid student id."); }

// Permission: teacher can only view assigned students (or students in their grade)
if ($isTeacher && !$isAdmin) {
    $stmt = $pdo->prepare("SELECT teacher_id, grade_level FROM students WHERE id = :id LIMIT 1");
    $stmt->execute([':id'=>$id]);
    $sinfo = $stmt->fetch();
    if (!$sinfo) die("Student not found.");
    $allowed = ($sinfo['teacher_id'] == $current['id']) || ($sinfo['grade_level'] == ($current['grade_level'] ?? 0));
    if (!$allowed) die("Access denied.");
}

// fetch student
$stmt = $pdo->prepare("SELECT id, username, student_name, grade_level, puzzles_completed, items, maze_sections, created_at FROM students WHERE id=:id LIMIT 1");
$stmt->execute([':id'=>$id]);
$student = $stmt->fetch();
if (!$student) die("Student not found.");

// progress rows (limit 200)
$prs = $pdo->prepare("SELECT level,score,time_spent,date_updated,grade_level FROM progress WHERE student_id = :sid ORDER BY date_updated DESC LIMIT 200");
$prs->execute([':sid'=>$id]);
$progress = $prs->fetchAll();

// Allow simple print view if ?print=1
$autoPrint = isset($_GET['print']) && $_GET['print'] == '1';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Student Report — <?=htmlspecialchars($student['student_name'] ?: $student['username'])?></title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<style>
body{font-family:Arial,Helvetica,sans-serif;padding:20px;color:#111}
.header{display:flex;justify-content:space-between;align-items:center;margin-bottom:18px}
.card{background:#fff;padding:18px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,.05)}
table{width:100%;border-collapse:collapse;margin-top:12px}
th,td{padding:8px;border-bottom:1px solid #eee;text-align:left}
.print-btn{padding:8px 12px;border-radius:6px;background:#4f46e5;color:white;border:none;cursor:pointer}
@media print { .no-print{display:none} }
</style>
</head>
<body>

<div class="header">
  <div>
    <h2>Student Report</h2>
    <div><strong><?=htmlspecialchars($student['student_name'] ?: $student['username'])?></strong></div>
    <div>Grade: <?=intval($student['grade_level'])?> — Username: <?=htmlspecialchars($student['username'])?></div>
  </div>
  <div class="no-print">
    <button class="print-btn" onclick="window.print()">Print</button>
    <a href="dashboard_teacher.php" class="print-btn" style="background:#6b7280;text-decoration:none;margin-left:8px">Back</a>
  </div>
</div>

<div class="card">
  <h3>Summary</h3>
  <p>Joined: <?=htmlspecialchars($student['created_at'])?> | Puzzles completed: <?=intval($student['puzzles_completed'] ?? 0)?></p>

  <h4>Recent Progress</h4>
  <?php if ($progress): ?>
    <table>
      <thead><tr><th>Date</th><th>Level</th><th>Score</th><th>Time Spent (s)</th></tr></thead>
      <tbody>
      <?php foreach ($progress as $p): ?>
        <tr>
          <td><?=htmlspecialchars($p['date_updated'])?></td>
          <td><?=intval($p['level'])?></td>
          <td><?=intval($p['score'])?></td>
          <td><?=floatval($p['time_spent'])?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php else: ?>
    <p>No progress records.</p>
  <?php endif; ?>
</div>

<div class="card" style="margin-top:12px">
  <h3>Progress Chart</h3>
  <canvas id="studentLine" width="800" height="250"></canvas>
</div>

<script>
// Prepare score-over-time data
var dataRows = <?php echo json_encode(array_reverse($progress), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>;
var dates = [], scores = [];
for (var i=0;i<dataRows.length;i++){
  dates.push(dataRows[i].date_updated);
  scores.push(parseInt(dataRows[i].score) || 0);
}

(function(){
  var ctx = document.getElementById('studentLine').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: {
      labels: dates,
      datasets: [{
        label: 'Score',
        data: scores,
        fill: false,
        tension: 0.2
      }]
    },
    options: {responsive:true,scales:{x:{display:true},y:{display:true}}}
  });
})();

// Auto print when ?print=1
<?php if ($autoPrint): ?>
  window.addEventListener('load', function(){ window.print(); setTimeout(function(){ window.close(); }, 500); });
<?php endif; ?>
</script>

</body>
</html>
