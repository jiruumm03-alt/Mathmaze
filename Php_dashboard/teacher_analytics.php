<?php
// teacher_analytics.php — Pie / Bar / Line charts
require_once __DIR__ . '/auth.php';
require_login();

if (!is_teacher() && !is_admin()) die("Access denied.");

$pdo = pdo();
$current = current_user();
$teacher_id = $current['id'];
$grade = $current['grade_level'] ?? null;

// Admin can view other teacher
if (is_admin() && isset($_GET['tid'])) {
    $teacher_id = intval($_GET['tid']);
    $st = $pdo->prepare("SELECT grade_level FROM teachers WHERE id = :id LIMIT 1");
    $st->execute([':id'=>$teacher_id]);
    $grade = $st->fetchColumn();
}

/* FETCH STUDENTS */
$stmt = $pdo->prepare("
    SELECT id, username, student_name, grade_level,
           COALESCE(puzzles_completed,0) AS puzzles_completed
    FROM students
    WHERE teacher_id = :tid OR grade_level = :g
    ORDER BY student_name
");
$stmt->execute([':tid'=>$teacher_id, ':g'=>$grade]);
$students = $stmt->fetchAll();

$ids = implode(',', array_map('intval', array_column($students,'id')) ?: [0]);

/* FETCH LAST PROGRESS */
$progressMap = [];
if (!empty($ids)) {
    try {
        $rows = $pdo->query("
            SELECT p.student_id, p.level, p.score, p.date_updated
            FROM progress p
            INNER JOIN (
                SELECT student_id, MAX(date_updated) AS maxd
                FROM progress
                WHERE student_id IN ($ids)
                GROUP BY student_id
            ) v ON p.student_id = v.student_id
            AND p.date_updated = v.maxd
        ")->fetchAll();

        foreach ($rows as $r) $progressMap[$r['student_id']] = $r;
    } catch (Exception $e) {}
}

/* PREPARE CHART DATA */
$labels = [];
$barData = [];
$pieBuckets = ["Beginner"=>0,"Intermediate"=>0,"Advanced"=>0];

foreach ($students as $s) {
    $labels[] = $s['student_name'] ?: $s['username'];
    $barData[] = intval($s['puzzles_completed']);

    $level = $progressMap[$s['id']]['level'] ?? 0;

    if ($level <= 3) $pieBuckets['Beginner']++;
    elseif ($level <= 7) $pieBuckets['Intermediate']++;
    else $pieBuckets['Advanced']++;
}

/* LINE CHART: Avg score last 30 days */
$lineRows = [];
try {
    $lineRows = $pdo->query("
        SELECT DATE(date_updated) AS d, ROUND(AVG(score),2) AS avg_score
        FROM progress
        WHERE student_id IN ($ids)
          AND date_updated >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        GROUP BY DATE(date_updated)
        ORDER BY d ASC
    ")->fetchAll();
} catch (Exception $e) {}

$lineDates = array_column($lineRows,'d');
$lineScores = array_map('floatval', array_column($lineRows,'avg_score'));

?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Teacher — Analytics</title>
<meta name="viewport" content="width=device-width, initial-scale=1">

<link rel="stylesheet"
 href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
*{box-sizing:border-box;margin:0;padding:0;font-family:"Poppins",sans-serif}
:root{
  --sidebar:240px; --sidebar-collapsed:64px;
  --p1:#4f46e5; --p2:#9333ea;
  --bg:#f4f6f8; --text:#222;
}
body{background:var(--bg);display:flex;min-height:100vh;color:var(--text)}

/* SIDEBAR */
.sidebar{
  height:100vh;width:var(--sidebar);background:linear-gradient(180deg,var(--p1),var(--p2));
  position:fixed;left:0;top:0;color:#fff;padding:20px 12px;transition:.25s;
}
.sidebar.collapsed{width:var(--sidebar-collapsed)}
.brand{font-weight:700;margin-bottom:20px;display:flex;align-items:center;gap:10px}
.brand img{width:34px;height:34px;border-radius:6px;background:white}
.nav-item{
  display:flex;align-items:center;gap:12px;padding:12px;border-radius:12px;
  margin-bottom:6px;cursor:pointer;color:white;
}
.nav-item:hover{background:rgba(255,255,255,0.12)}
.sidebar.collapsed .label{display:none}

/* NAVBAR */
.navbar{
  height:64px;background:linear-gradient(90deg,var(--p1),var(--p2));
  width:100%;margin-left:var(--sidebar);transition:.25s;
  display:flex;align-items:center;padding:0 20px;color:white;
}
.sidebar.collapsed ~ .navbar{margin-left:var(--sidebar-collapsed)}
.toggle-btn{
  width:40px;height:40px;border:none;border-radius:8px;
  background:rgba(255,255,255,0.15);color:white;cursor:pointer;
}

/* MAIN CONTENT */
.wrapper{
  margin-left:var(--sidebar);padding:20px;transition:.25s;width:100%;
}
.sidebar.collapsed ~ .wrapper{margin-left:var(--sidebar-collapsed)}
.card{
  background:white;border-radius:14px;padding:22px;box-shadow:0 4px 12px rgba(0,0,0,0.08);
  margin-bottom:20px;
}
.chart-grid{display:flex;flex-wrap:wrap;gap:20px}
.chart-card{
  background:white;padding:16px;border-radius:14px;flex:1;min-width:300px;
  box-shadow:0 4px 14px rgba(0,0,0,0.08);
}
.btn{
  padding:8px 14px;border-radius:8px;background:var(--p1);
  color:white;border:none;cursor:pointer;
}

/* MODAL */
.modal{
  display:none;position:fixed;top:0;left:0;width:100%;height:100%;
  background:rgba(0,0,0,0.45);justify-content:center;align-items:center;z-index:9999;
}
.modal-box{
  background:white;padding:24px;border-radius:14px;text-align:center;width:90%;max-width:420px;
}
.btn-light{
  padding:8px 12px;background:#eee;border-radius:10px;border:none;cursor:pointer;
}

</style>
</head>

<body>

<!-- SIDEBAR -->
<div class="sidebar" id="sidebar">
  <div class="brand">
    <img src="favicon-32x32.png"><span class="label">Teacher Panel</span>
  </div>

  <div class="nav-item" onclick="location.href='dashboard_teacher.php'">
    <i class="fa-solid fa-gauge"></i><span class="label">Dashboard</span>
  </div>

  <div class="nav-item" onclick="location.href='teacher_student.php'">
    <i class="fa-solid fa-users"></i><span class="label">Students</span>
  </div>

  <div class="nav-item" onclick="location.href='teacher_analytics.php'">
    <i class="fa-solid fa-chart-pie"></i><span class="label">Analytics</span>
  </div>

  <div style="margin-top:auto"></div>

  <div class="nav-item" onclick="toggleSidebar()">
    <i class="fa-solid fa-angles-left"></i><span class="label">Collapse</span>
  </div>

  <div class="nav-item" onclick="openLogout()">
    <i class="fa-solid fa-right-from-bracket"></i><span class="label">Logout</span>
  </div>
</div>

<!-- NAVBAR -->
<header class="navbar">
  <button class="toggle-btn" id="sidebarBtn"><i class="fa-solid fa-bars"></i></button>
  <h3 style="margin-left:12px">Analytics</h3>

  <div style="margin-left:auto">
    Hello, <?=htmlspecialchars($current['name'])?>
  </div>
  <button class="btn" style="margin-left:15px" onclick="openLogout()">Logout</button>
</header>

<!-- MAIN -->
<div class="wrapper">

  <div class="card">
    <h2>Class Analytics</h2>
    <p style="color:#666;margin-top:6px">
      Includes student levels (Pie), puzzles completed (Bar), and average score (Line).
    </p>
  </div>

  <div class="chart-grid">
    <div class="chart-card">
      <canvas id="pieChart"></canvas>
    </div>

    <div class="chart-card">
      <canvas id="barChart"></canvas>
    </div>

    <div class="chart-card" style="flex-basis:100%">
      <canvas id="lineChart"></canvas>
    </div>
  </div>

</div>

<!-- Logout Modal -->
<div class="modal" id="logoutModal">
  <div class="modal-box">
    <h3>Log Out?</h3>
    <p>Are you sure you want to log out?</p>
    <div style="margin-top:20px;display:flex;gap:12px;justify-content:center">
      <button class="btn" onclick="window.location='logout.php'">Logout</button>
      <button class="btn-light" onclick="closeLogout()">Cancel</button>
    </div>
  </div>
</div>

<script>
// Sidebar toggle
function toggleSidebar(){
  document.getElementById("sidebar").classList.toggle("collapsed");
}

// Open / Close logout modal
function openLogout(){ document.getElementById("logoutModal").style.display="flex"; }
function closeLogout(){ document.getElementById("logoutModal").style.display="none"; }

// Responsive sidebar for mobile
document.getElementById("sidebarBtn").onclick = () => {
  if(window.innerWidth <= 900){
    document.getElementById("sidebar").classList.toggle("open");
  } else toggleSidebar();
};
</script>

<script>
// Chart Data (PHP → JS)
const labels = <?=json_encode($labels)?>;
const barData = <?=json_encode($barData)?>;
const pieData = <?=json_encode(array_values($pieBuckets))?>;
const pieLabels = <?=json_encode(array_keys($pieBuckets))?>;
const lineDates = <?=json_encode($lineDates)?>;
const lineScores = <?=json_encode($lineScores)?>;

// PIE CHART
new Chart(document.getElementById('pieChart'), {
  type: 'pie',
  data: {
    labels: pieLabels,
    datasets: [{
      data: pieData,
      backgroundColor: ['#60a5fa','#7c3aed','#34d399']
    }]
  }
});

// BAR CHART
new Chart(document.getElementById('barChart'), {
  type: 'bar',
  data: {
    labels: labels,
    datasets: [{
      label: 'Puzzles Completed',
      data: barData
    }]
  }
});

// LINE CHART
new Chart(document.getElementById('lineChart'), {
  type: 'line',
  data: {
    labels: lineDates,
    datasets: [{
      label: 'Avg Score (30 days)',
      data: lineScores,
      tension: 0.2
    }]
  }
});
</script>

</body>
</html>
