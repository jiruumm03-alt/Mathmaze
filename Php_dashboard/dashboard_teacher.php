<?php
// dashboard_teacher.php — teacher dashboard with analytics + printable report
require_once __DIR__ . '/auth.php';
require_login();
if (!is_teacher() && !is_admin()) die("Access denied.");

$pdo = pdo();
$current = current_user();
$teacher_id = $current['id'];
$grade = $current['grade_level'] ?? null;

// Admin can optionally view other teacher via ?tid=...
if (is_admin() && isset($_GET['tid'])) {
    $teacher_id = intval($_GET['tid']);
    $stmt = $pdo->prepare("SELECT grade_level FROM teachers WHERE id=:id LIMIT 1");
    $stmt->execute([':id'=>$teacher_id]);
    $grade = $stmt->fetchColumn();
}

// Fetch students for this teacher (by teacher_id or grade)
$stmt = $pdo->prepare("SELECT id,username,student_name,grade_level FROM students WHERE teacher_id = :tid OR grade_level = :g ORDER BY student_name");
$stmt->execute([':tid'=>$teacher_id, ':g'=>$grade]);
$students = $stmt->fetchAll();

// For each student, fetch latest progress
$student_progress = []; // keyed by student id
$student_ids = array_column($students, 'id');

if (!empty($student_ids)) {
    // Prepare a query to fetch latest progress per student
    // MySQL: select p.* where p.id = (select id from progress where student_id = p.student_id order by date_updated desc limit 1)
    // Simpler: fetch latest per student using group by with MAX(date_updated) join
    $in = implode(',', array_map('intval', $student_ids));
    $sql = "
      SELECT p1.*
      FROM progress p1
      JOIN (
        SELECT student_id, MAX(date_updated) AS mx
        FROM progress
        WHERE student_id IN ($in)
        GROUP BY student_id
      ) p2 ON p1.student_id = p2.student_id AND p1.date_updated = p2.mx
      ORDER BY p1.student_id
    ";
    $rows = $pdo->query($sql)->fetchAll();

    foreach ($rows as $r) {
        $student_progress[$r['student_id']] = $r;
    }
}

// Compute analytics
$totalStudents = count($students);
$withProgress = count($student_progress);
$sumScore = 0;
$sumLevel = 0;
$sumTime = 0.0;
$levelCounts = []; // level => count
$maxLevelSeen = 0;

foreach ($student_progress as $sp) {
    $lvl = intval($sp['level']);
    $score = intval($sp['score']);
    $tsp = floatval($sp['time_spent']);

    $sumLevel += $lvl;
    $sumScore += $score;
    $sumTime += $tsp;

    if (!isset($levelCounts[$lvl])) $levelCounts[$lvl] = 0;
    $levelCounts[$lvl] += 1;

    if ($lvl > $maxLevelSeen) $maxLevelSeen = $lvl;
}

// If no progress, ensure at least level 0 bucket exists
if (empty($levelCounts)) $levelCounts[0] = 0;

$avgScore = $withProgress ? round($sumScore / $withProgress, 2) : 0;
$avgLevel = $withProgress ? round($sumLevel / $withProgress, 2) : 0;
$totalTime = round($sumTime, 2);

// For charting, build an array from level 0..maxLevelSeen
$maxLevelToShow = max(5, $maxLevelSeen); // show up to at least 5 levels
$chartData = [];
for ($i = 0; $i <= $maxLevelToShow; $i++) {
    $chartData[$i] = $levelCounts[$i] ?? 0;
}

// CSV export support (when ?export=csv)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=progress_report_' . ($grade ?? 'all') . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['student_id','username','student_name','grade_level','latest_level','latest_score','time_spent','date_updated']);
    foreach ($students as $s) {
        $sp = $student_progress[$s['id']] ?? null;
        fputcsv($out, [
            $s['id'],
            $s['username'],
            $s['student_name'],
            $s['grade_level'],
            $sp ? $sp['level'] : '',
            $sp ? $sp['score'] : '',
            $sp ? $sp['time_spent'] : '',
            $sp ? $sp['date_updated'] : ''
        ]);
    }
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Teacher Dashboard — Progress Analytics</title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<style>
/* Inline theme + layout (matches previous pages) */
*{margin:0;padding:0;box-sizing:border-box;font-family:"Poppins",Arial,sans-serif}
body{min-height:100vh;background:#f4f6f8;color:#222;display:flex;flex-direction:column}

/* NAVBAR */
.navbar{
  display:flex;align-items:center;gap:12px;
  background:linear-gradient(135deg,#4f46e5,#9333ea);
  padding:14px 20px;color:#fff;box-shadow:0 3px 12px rgba(0,0,0,.12);
}
.navbar img{width:36px;height:36px}
.navbar h1{font-size:18px;font-weight:600}
.nav-right{margin-left:auto;display:flex;gap:10px;align-items:center}

/* WRAPPER */
.wrapper{padding:22px;flex:1;display:flex;justify-content:center}
.container{width:100%;max-width:1200px}

/* CARD */
.card{background:#fff;padding:20px;border-radius:12px;box-shadow:0 8px 26px rgba(0,0,0,.06)}

/* STATS ROW */
.stats{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px}
.stat{
  flex:1;min-width:160px;padding:12px;border-radius:10px;border:1px solid #eef2ff;background:linear-gradient(180deg,#fff,#fbfbff);
  text-align:center;
}
.stat h4{color:#4f46e5;margin-bottom:6px}
.stat p{font-weight:700;font-size:18px}

/* CHART + LIST */
.grid{display:grid;grid-template-columns: 1fr 420px;gap:18px}
@media(max-width:980px){.grid{grid-template-columns: 1fr;}}
.chart-box{padding:12px;border-radius:10px;border:1px solid #eef2ff;background:#fff}
.list-box{padding:12px;border-radius:10px;border:1px solid #eef2ff;background:#fff;max-height:520px;overflow:auto}

/* STUDENT ROW */
.student-row{display:flex;justify-content:space-between;align-items:center;padding:10px;border-bottom:1px solid #f1f5ff}
.student-left{display:flex;flex-direction:column}
.student-name{font-weight:700}
.student-meta{font-size:13px;color:#666}

/* CHART: simple SVG bar chart styles */
.s-legend{display:flex;gap:8px;flex-wrap:wrap;margin-top:8px}
.s-legend span{font-size:13px;color:#333;background:#f8f8ff;padding:6px 10px;border-radius:8px;border:1px solid #e9e6ff}

/* ACTIONS */
.actions{display:flex;gap:10px;margin-bottom:12px}
.btn{
  padding:8px 12px;border-radius:8px;background:linear-gradient(135deg,#4f46e5,#9333ea);color:#fff;text-decoration:none;font-size:14px;
}
.btn-alt{background:#fff;border:1px solid #e6e6f7;color:#333}

/* PRINTABLE REPORT */
#print-area{display:none} /* not visible in UI; used for print */
@media print {
  body *{visibility:hidden}
  #print-area, #print-area *{visibility:visible}
  #print-area{position:fixed;left:0;top:0;width:100%}
}

/* LOGOUT MODAL (re-usable) */
.modal-overlay{position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.4);display:none;justify-content:center;align-items:center;z-index:9999}
.modal-box{width:92%;max-width:360px;background:#fff;padding:20px;border-radius:12px;box-shadow:0 10px 35px rgba(0,0,0,.15)}
.modal-actions{display:flex;gap:10px;margin-top:16px}
.modal-actions .btn-confirm{flex:1;padding:10px;border:none;border-radius:8px;background:linear-gradient(135deg,#4f46e5,#9333ea);color:#fff}
.modal-actions .btn-cancel{flex:1;padding:10px;border-radius:8px;background:#f3f4f6;border:none}
</style>
</head>
<body>

<!-- NAVBAR -->
<header class="navbar">
  <img src="favicon-32x32.png" alt="" onerror="this.style.display='none'">
  <h1>MathMaze — Teacher</h1>
  <div class="nav-right">
    <div style="font-size:14px;opacity:.95"><?=htmlspecialchars($current['name'] ?? $current['username'])?></div>
    <a class="btn" href="assignments.php" style="text-decoration:none">Assignments</a>
    <a class="btn" href="#" onclick="openLogoutModal()">Logout</a>
  </div>
</header>

<!-- MAIN -->
<div class="wrapper">
  <div class="container">

    <!-- Top: actions -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
      <h2 style="font-size:20px;margin-bottom:6px">Student Progress — Grade <?=intval($grade)?></h2>
      <div class="actions">
        <a class="btn" href="?export=csv">Export CSV</a>
        <a class="btn btn-alt" href="#" onclick="openPrintReport();return false">Print Report</a>
      </div>
    </div>

    <div class="card">

      <!-- STATS -->
      <div class="stats" style="margin-bottom:18px">
        <div class="stat">
          <h4>Total students</h4>
          <p><?=$totalStudents?></p>
        </div>
        <div class="stat">
          <h4>Students with progress</h4>
          <p><?=$withProgress?></p>
        </div>
        <div class="stat">
          <h4>Average level</h4>
          <p><?=$avgLevel?></p>
        </div>
        <div class="stat">
          <h4>Average score</h4>
          <p><?=$avgScore?></p>
        </div>
        <div class="stat">
          <h4>Total time (min)</h4>
          <p><?=$totalTime?></p>
        </div>
      </div>

      <div class="grid">
        <!-- Chart -->
        <div class="chart-box">
          <h3 style="margin-bottom:8px">Level distribution</h3>
          <?php
          // compute chart dimensions
          $maxCount = max($chartData) ?: 1;
          $barMaxHeight = 160;
          ?>
          <svg width="100%" height="200" viewBox="0 0 600 200" preserveAspectRatio="xMidYMid meet" style="width:100%;height:200px">
            <?php
            $cols = count($chartData);
            $colWidth = floor(560 / max(1, $cols));
            $x = 20;
            $i = 0;
            foreach ($chartData as $level => $count):
              $h = ($count / $maxCount) * $barMaxHeight;
              $y = 180 - $h;
              $rx = 8; // rounded corner on rect via rx
              $color = '#7c3aed';
            ?>
              <!-- bar -->
              <rect x="<?=$x?>" y="<?=$y?>" width="<?=($colWidth-8)?>" height="<?=$h?>" rx="6" fill="<?=$color?>" opacity="0.95"></rect>
              <!-- label: level -->
              <text x="<?=$x + ($colWidth/2)?>" y="196" font-size="12" text-anchor="middle" fill="#333"><?=$level?></text>
              <!-- value -->
              <text x="<?=$x + ($colWidth/2)?>" y="<?=$y - 6?>" font-size="12" text-anchor="middle" fill="#333"><?=intval($count)?></text>
            <?php
              $x += $colWidth;
              $i++;
            endforeach;
            ?>
          </svg>

          <div class="s-legend" style="margin-top:10px">
            <?php foreach ($chartData as $lvl => $cnt): ?>
              <span>Level <?=$lvl?> — <?=$cnt?> student<?=($cnt==1?'':'s')?></span>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Student list with latest progress -->
        <div class="list-box">
          <h3 style="margin-bottom:10px">Students (latest progress)</h3>
          <?php if ($students): foreach ($students as $s): 
              $sp = $student_progress[$s['id']] ?? null;
          ?>
            <div class="student-row">
              <div class="student-left">
                <div class="student-name"><?=htmlspecialchars($s['student_name'])?> <span style="font-size:12px;color:#666"> (<?=htmlspecialchars($s['username'])?>)</span></div>
                <div class="student-meta">Grade <?=intval($s['grade_level'])?></div>
              </div>
              <div style="text-align:right">
                <?php if ($sp): ?>
                  <div style="font-weight:700">Level <?=intval($sp['level'])?></div>
                  <div style="font-size:13px;color:#666">Score: <?=intval($sp['score'])?> — <?=htmlspecialchars($sp['date_updated'])?></div>
                <?php else: ?>
                  <div style="color:#666">No progress</div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; else: ?>
            <div style="padding:12px;color:#666">No students found.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- PRINT AREA (visible only in print) -->
<div id="print-area" style="padding:20px">
  <h1 style="color:#4f46e5">MathMaze — Progress Report</h1>
  <p>Teacher: <?=htmlspecialchars($current['name'] ?? $current['username'])?> — Grade <?=intval($grade)?> — Generated: <?=date('Y-m-d H:i')?></p>

  <h3>Summary</h3>
  <table style="width:100%;border-collapse:collapse;margin-bottom:12px">
    <tr><td>Total students</td><td><?=$totalStudents?></td></tr>
    <tr><td>Students with progress</td><td><?=$withProgress?></td></tr>
    <tr><td>Average level</td><td><?=$avgLevel?></td></tr>
    <tr><td>Average score</td><td><?=$avgScore?></td></tr>
    <tr><td>Total time (min)</td><td><?=$totalTime?></td></tr>
  </table>

  <h3>Students (latest)</h3>
  <table style="width:100%;border-collapse:collapse" border="1">
    <thead><tr><th>ID</th><th>Username</th><th>Name</th><th>Grade</th><th>Level</th><th>Score</th><th>Time</th><th>Updated</th></tr></thead>
    <tbody>
    <?php foreach ($students as $s): $sp = $student_progress[$s['id']] ?? null; ?>
      <tr>
        <td><?=intval($s['id'])?></td>
        <td><?=htmlspecialchars($s['username'])?></td>
        <td><?=htmlspecialchars($s['student_name'])?></td>
        <td><?=intval($s['grade_level'])?></td>
        <td><?= $sp ? intval($sp['level']) : '-' ?></td>
        <td><?= $sp ? intval($sp['score']) : '-' ?></td>
        <td><?= $sp ? htmlspecialchars($sp['time_spent']) : '-' ?></td>
        <td><?= $sp ? htmlspecialchars($sp['date_updated']) : '-' ?></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- LOGOUT MODAL (reusable snippet) -->
<div id="logoutModal" class="modal-overlay">
  <div class="modal-box">
    <h3 style="color:#4f46e5">Log Out?</h3>
    <p>Are you sure you want to log out?</p>
    <div class="modal-actions">
      <button class="btn-confirm" onclick="confirmLogout()">Log Out</button>
      <button class="btn-cancel" onclick="closeLogoutModal()">Cancel</button>
    </div>
  </div>
</div>

<script>
// Modal functions
function openLogoutModal(){ document.getElementById('logoutModal').style.display = 'flex'; }
function closeLogoutModal(){ document.getElementById('logoutModal').style.display = 'none'; }
function confirmLogout(){ window.location.href = 'logout.php'; }

// Print report: scroll to print area then call window.print()
function openPrintReport(){
  // temporarily show print area, hide modal/backdrop
  window.print();
}
</script>

</body>
</html>
