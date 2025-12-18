<?php
// dashboard_teacher.php — Teacher dashboard with Assignments (teacher-only) + Reports
require_once __DIR__ . '/auth.php';
require_login();

// Allow access only to teachers or admins (admins can also view teacher dashboard if needed)
if (!is_teacher() && !is_admin()) {
    die("Access denied.");
}

$pdo = pdo();
$current = current_user();

// We'll show assignments only for the logged-in teacher (Option 1)
$teacher_id = intval($current['id']);

/* ============================
   PROCESS ASSIGNMENTS CRUD (POST)
   - teacher-only: teacher_id is forced to current user
   ============================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? '';

    if (in_array($mode, ['assignment_create','assignment_update'])) {
        // only teacher or admin allowed to create/update here
        if (!is_teacher() && !is_admin()) { die("Access denied."); }

        $title = trim($_POST['title'] ?? '');
        $grade = intval($_POST['grade_level'] ?? 1);
        $id    = intval($_POST['id'] ?? 0);

        $questions_json = $_POST['questions_json'] ?? '[]';
        $decoded = json_decode($questions_json, true);
        if (!is_array($decoded)) $questions_json = json_encode([]);
        else {
            $clean = [];
            foreach ($decoded as $q) {
                $qt = trim($q['question'] ?? '');
                $ans = trim($q['answer'] ?? '');
                if ($qt !== '') $clean[] = ['question'=>$qt,'answer'=>$ans];
            }
            $questions_json = json_encode($clean, JSON_UNESCAPED_UNICODE);
        }

        if ($title === '') {
            $_SESSION['flash_error'] = "Title required.";
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        }

        try {
            if ($mode === 'assignment_create') {
                $stmt = $pdo->prepare("
                    INSERT INTO assignments (title, grade_level, teacher_id, questions_json, created_at)
                    VALUES (:title, :grade, :tid, :q, NOW())
                ");
                $stmt->execute([':title'=>$title, ':grade'=>$grade, ':tid'=>$teacher_id, ':q'=>$questions_json]);
                $_SESSION['flash_success'] = "Assignment created.";
            } else {
                // assignment_update - ensure the assignment belongs to this teacher
                $stmtCheck = $pdo->prepare("SELECT teacher_id FROM assignments WHERE id=:id LIMIT 1");
                $stmtCheck->execute([':id'=>$id]);
                $owner = $stmtCheck->fetchColumn();
                if (!$owner || intval($owner) !== $teacher_id) {
                    $_SESSION['flash_error'] = "Permission denied (assignment ownership).";
                } else {
                    $stmt = $pdo->prepare("UPDATE assignments SET title=:title, grade_level=:grade, questions_json=:q WHERE id=:id");
                    $stmt->execute([':title'=>$title, ':grade'=>$grade, ':q'=>$questions_json, ':id'=>$id]);
                    $_SESSION['flash_success'] = "Assignment updated.";
                }
            }
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "DB error: ".$e->getMessage();
        }

        header("Location: ".$_SERVER['PHP_SELF']."?tab=assignments");
        exit;
    }

    if ($mode === 'assignment_delete') {
        if (!is_teacher() && !is_admin()) { die("Access denied."); }
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            // ensure teacher owns the assignment
            $stmtCheck = $pdo->prepare("SELECT teacher_id FROM assignments WHERE id=:id LIMIT 1");
            $stmtCheck->execute([':id'=>$id]);
            $owner = $stmtCheck->fetchColumn();
            if ($owner && intval($owner) === $teacher_id) {
                $pdo->prepare("DELETE FROM assignments WHERE id=:id")->execute([':id'=>$id]);
                $_SESSION['flash_success'] = "Assignment deleted.";
            } else {
                $_SESSION['flash_error'] = "Permission denied (assignment ownership).";
            }
        }
        header("Location: ".$_SERVER['PHP_SELF']."?tab=assignments");
        exit;
    }
}

/* ============================
   FETCH DATA
   - Students for this teacher (teacher_id OR same grade)
   - Latest progress for those students
   - Assignments that belong to this teacher only (Option 1)
   ============================ */

/* Students */
$grade = $current['grade_level'] ?? null;
$stmt = $pdo->prepare("
    SELECT id, username, student_name, grade_level, puzzles_completed, maze_sections, created_at
    FROM students
    WHERE (teacher_id = :tid) OR (grade_level = :g)
    ORDER BY student_name ASC
");
$stmt->execute([':tid'=>$teacher_id, ':g'=>$grade]);
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
$studentCount = count($students);

/* Latest progress for those students */
$progressMap = [];
if ($studentCount > 0) {
    $ids = implode(',', array_map('intval', array_column($students,'id')));
    $sql = "
        SELECT p.student_id, p.level, p.score, p.date_updated
        FROM progress p
        INNER JOIN (
            SELECT student_id, MAX(date_updated) AS maxd
            FROM progress
            WHERE student_id IN ($ids)
            GROUP BY student_id
        ) latest ON latest.student_id = p.student_id AND latest.maxd = p.date_updated
        WHERE p.student_id IN ($ids)
    ";
    try { $lastProgress = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e) { $lastProgress = []; }
    foreach ($lastProgress as $r) { $progressMap[$r['student_id']] = $r; }
}

/* Chart data (same as before) */
$labels = [];
$barData = [];
$pieBuckets = ['Beginner'=>0,'Intermediate'=>0,'Advanced'=>0];

foreach ($students as $s) {
    $labels[] = $s['student_name'] ?: $s['username'];
    $barData[] = intval($s['puzzles_completed'] ?? 0);

    $lp = $progressMap[$s['id']] ?? null;
    $lvl = $lp ? intval($lp['level']) : 0;
    if ($lvl <= 3) $pieBuckets['Beginner']++;
    elseif ($lvl <= 7) $pieBuckets['Intermediate']++;
    else $pieBuckets['Advanced']++;
}

/* Line chart data: avg score per day (30d) */
$lineRows = [];
if ($studentCount > 0) {
    $ids = implode(',', array_map('intval', array_column($students,'id')));
    $sql = "
        SELECT DATE(date_updated) AS d, ROUND(AVG(score),2) AS avg_score
        FROM progress
        WHERE date_updated >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
          AND student_id IN ($ids)
        GROUP BY DATE(date_updated)
        ORDER BY DATE(date_updated)
    ";
    try { $lineRows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC); } catch (Exception $e) { $lineRows = []; }
}
$lineDates = array_column($lineRows, 'd');
$lineScores = array_map('floatval', array_column($lineRows, 'avg_score'));

/* Assignments - ONLY those created by the logged-in teacher (Option 1) */
$assignments = $pdo->prepare("
    SELECT a.*, t.full_name AS teacher_name
    FROM assignments a
    LEFT JOIN teachers t ON a.teacher_id = t.id
    WHERE a.teacher_id = :tid
    ORDER BY a.created_at DESC
");
$assignments->execute([':tid'=>$teacher_id]);
$assignments = $assignments->fetchAll(PDO::FETCH_ASSOC);

/* flash messages */
$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Teacher Dashboard</title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<!-- Favicon -->
<link rel="icon" type="image/png" sizes="32x32" href="favicon-32x32.png">
<link rel="icon" type="image/png" sizes="16x16" href="favicon-16x16.png">
<link rel="shortcut icon" href="favicon.ico">

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
/* Basic reset & theme (keeps styling close to your admin) */
*{box-sizing:border-box;margin:0;padding:0;font-family:"Poppins",Arial,sans-serif}
:root{
  --sidebar-collapsed-w:64px;
  --sidebar-expanded-w:240px;
  --purple-1:#4f46e5;
  --purple-2:#9333ea;
  --bg:#f4f6f8;
  --card:#fff;
  --muted:#6b7280;
}
body{background:var(--bg);display:flex;min-height:100vh}

/* Sidebar */
.sidebar{position:fixed;left:0;top:0;height:100vh;width:var(--sidebar-collapsed-w);background:linear-gradient(180deg,var(--purple-1),var(--purple-2));color:#fff;padding:18px 8px;transition:width .25s ease;overflow:hidden}
.sidebar.expanded{width:var(--sidebar-expanded-w)}
.logo{display:flex;align-items:center;justify-content:center;margin-bottom:18px}
.logo img{width:40px;height:40px;border-radius:8px;background:#fff}
.nav{display:flex;flex-direction:column;gap:16px;padding:6px}
.nav-item{display:flex;align-items:center;gap:12px;padding:8px;border-radius:10px;cursor:pointer;color:#fff}
.nav-item:hover{background:rgba(255,255,255,0.08)}
.icon{width:36px;height:36;display:flex;align-items:center;justify-content:center}
.label{display:none;white-space:nowrap}
.sidebar.expanded .label{display:inline-block}
.bottom{position:absolute;left:0;bottom:20px;width:100%}
.bottom .nav-item{padding-left:12px;padding-right:12px}

/* Wrapper / topbar */
.wrapper{margin-left:var(--sidebar-collapsed-w);width:100%;padding:18px;transition:margin-left .25s ease}
.sidebar.expanded ~ .wrapper{margin-left:var(--sidebar-expanded-w)}
.topbar{background:linear-gradient(135deg,var(--purple-1),var(--purple-2));color:#fff;padding:14px;border-radius:8px;margin-bottom:18px;display:flex;align-items:center;justify-content:space-between}
.topbar .right{display:flex;align-items:center;gap:12px}
.btn{background:linear-gradient(135deg,var(--purple-1),var(--purple-2));color:#fff;border:none;padding:8px 12px;border-radius:8px;cursor:pointer}
.btn-light{background:#f3f4f6;color:#111;padding:8px 10px;border-radius:8px;border:none}

/* Cards & tables */
.card{background:var(--card);padding:18px;border-radius:12px;box-shadow:0 6px 20px rgba(0,0,0,0.06);margin-bottom:18px}
.table-wrap{overflow:auto}
table{width:100%;border-collapse:collapse;margin-top:10px}
th,td{padding:10px;border-bottom:1px solid #eee;text-align:left}
th{background:var(--purple-1);color:#fff}

/* Assignments styling */
.assign-form{max-width:920px;margin-top:14px}
.assign-row{display:flex;gap:10px;align-items:center;margin-top:10px}
.assign-row input[type="text"], .assign-row input[type="number"]{flex:1;padding:10px;border-radius:10px;border:1px solid #dbe4fb;font-size:14px;background:#fff;}
.q-row{display:flex;gap:8px;align-items:center;margin-top:10px}
.q-row input{flex:1;padding:10px;border-radius:8px;border:1px solid #e6e9f8}
.q-row .btn-q-remove{background:#ef4444;border:0;color:#fff;padding:8px 10px;border-radius:8px;cursor:pointer}

/* Logout Modal */
.modal-overlay {
    display:none;                     /* stays hidden by default */
    position:fixed;
    inset:0;
    background:rgba(0,0,0,0.55);
    backdrop-filter:blur(4px);
    z-index:9999;
    align-items:center;
    justify-content:center;
}


.modal-box {
    width:90%;
    max-width:420px;

    background:#fff;
    padding:24px;
    border-radius:16px;
    text-align:center;
    box-shadow:0 8px 25px rgba(0,0,0,0.25);

    animation:fadeInScale .25s ease-out;
}

.modal-actions {
    margin-top:18px;
    display:flex;
    justify-content:center;
    gap:12px;
}
/* Fix Actions column header & cell alignment */
th.actions-col, td.actions-col {
    text-align: center !important;
    width: 150px;   /* adjust if needed */
    white-space: nowrap;
}


@keyframes fadeInScale {
    from { transform:scale(0.85); opacity:0; }
    to   { transform:scale(1); opacity:1; }
}

/* Reports output */
.report-output{margin-top:20px;background:#fff;padding:18px;border-radius:12px;min-height:150px;box-shadow:0 4px 12px rgba(0,0,0,0.06)}

/* Analytics sizing */
.chart-card { min-height: 320px; display:flex; align-items:center; justify-content:center; padding:12px; }
.chart-card canvas { width:100% !important; height:320px !important; display:block; }

/* Responsive */
@media (max-width:900px){
  .sidebar{transform:translateX(-110%);position:fixed}
  .sidebar.open{transform:translateX(0)}
  .wrapper{margin-left:0;padding:12px}
}
</style>
</head>
<body>
<!-- SIDEBAR -->
<aside id="sidebar" class="sidebar" role="navigation" aria-label="Main navigation">
  <div class="logo"><img src="favicon-32x32.png" alt="logo"></div>

  <nav class="nav" aria-label="Sidebar menu">
    <div class="nav-item" onclick="showTab('overview')">
      <div class="icon"><i class="fa-solid fa-gauge"></i></div><div class="label">Dashboard</div>
    </div>

    <div class="nav-item" onclick="showTab('students')">
      <div class="icon"><i class="fa-solid fa-users"></i></div><div class="label">Students</div>
    </div>

    <div class="nav-item" onclick="showTab('analytics')">
      <div class="icon"><i class="fa-solid fa-chart-pie"></i></div><div class="label">Analytics</div>
    </div>

    <div class="nav-item" onclick="showTab('assignments')">
      <div class="icon"><i class="fa-solid fa-book"></i></div><div class="label">Assignments</div>
    </div>

    <div class="nav-item" onclick="showTab('reports')">
      <div class="icon"><i class="fa-solid fa-file-lines"></i></div><div class="label">Reports</div>
    </div>
  </nav>

  <div class="bottom">
    <div class="nav-item" onclick="toggleSidebar()"><div class="icon"><i class="fa-solid fa-angles-left"></i></div><div class="label">Collapse</div></div>
    <div class="nav-item" onclick="openLogoutModal()"><div class="icon"><i class="fa-solid fa-right-from-bracket"></i></div><div class="label">Logout</div></div>
  </div>
</aside>

<!-- MAIN -->
<div class="wrapper" id="wrapper">
  <div class="topbar">
    <div style="font-weight:700;font-size:18px">Teacher Dashboard</div>
    <div class="right">
      <div style="color:#fff">Hello, <?=htmlspecialchars($current['name'] ?? $current['username'])?></div>
      <button class="btn" onclick="openLogoutModal()">Logout</button>
    </div>
  </div>

  <!-- OVERVIEW -->
  <div id="overview" class="tab">
    <div class="card">
      <h2>Overview</h2>
      <div style="display:flex;gap:12px;margin-top:12px;flex-wrap:wrap">
        <div style="flex:1" class="card"><div style="color:var(--muted)">My Students</div><div style="font-size:22px;font-weight:700"><?=intval($studentCount)?></div></div>
        <div style="flex:1" class="card"><div style="color:var(--muted)">Avg Score (30d)</div><div style="font-size:22px;font-weight:700"><?=count($lineScores) ? round(array_sum($lineScores)/count($lineScores),2) : 0?></div></div>
        <div style="flex:1" class="card"><div style="color:var(--muted)">Total Puzzles</div><div style="font-size:22px;font-weight:700"><?=array_sum($barData)?></div></div>
        <div style="flex:1" class="card"><div style="color:var(--muted)">Active Days</div><div style="font-size:22px;font-weight:700"><?=count($lineRows)?></div></div>
      </div>
    </div>
  </div>

  <!-- STUDENTS -->
  <div id="students" class="tab" style="display:none">
    <div class="card">
      <h2>Students</h2>
      <p style="color:var(--muted);margin-bottom:10px">Select students to bulk print reports or export CSV.</p>

      <div style="display:flex;gap:10px;margin-bottom:12px">
        <button class="btn" id="printSelectedBtn"><i class="fa-solid fa-print"></i> Print Selected</button>
        <button class="btn-light" id="exportCsvBtn"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
      </div>

      <div class="table-wrap">
        <table>
          <thead>
            <tr><th><input type="checkbox" id="checkAll"></th><th>ID</th><th>Username</th><th>Name</th><th>Grade</th><th>Level</th><th>Score</th><th>Puzzles</th><th class="actions-col">Actions</th>
</tr>
          </thead>
          <tbody>
            <?php foreach ($students as $s):
                $lp = $progressMap[$s['id']] ?? null;
                $level = $lp ? intval($lp['level']) : 0;
                $score = $lp ? intval($lp['score']) : 0;
            ?>
            <tr>
              <td><input type="checkbox" class="sel" name="sid[]" value="<?=intval($s['id'])?>"></td>
              <td><?=intval($s['id'])?></td>
              <td><?=htmlspecialchars($s['username'])?></td>
              <td><?=htmlspecialchars($s['student_name'])?></td>
              <td><?=intval($s['grade_level'])?></td>
              <td><?=intval($level)?></td>
              <td><?=intval($score)?></td>
              <td><?=intval($s['puzzles_completed'] ?? 0)?></td>
              <td>
                <a class="btn-light" href="student_report.php?id=<?=intval($s['id'])?>" target="_blank" rel="noopener">View</a>
                <a class="btn" href="student_report.php?id=<?=intval($s['id'])?>&print=1" target="_blank" rel="noopener">Print</a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($students)): ?>
              <tr><td colspan="9" style="text-align:center;padding:16px;color:var(--muted)">No students found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- ANALYTICS -->
  <div id="analytics" class="tab" style="display:none">
    <div class="card">
      <h2>Analytics</h2>
      <div class="charts" style="margin-top:12px; display:flex; gap:18px; flex-wrap:wrap;">
        <div class="chart-card" style="flex-basis:48%;">
            <canvas id="pieChart" aria-label="Pie chart" role="img"></canvas>
        </div>

        <div class="chart-card" style="flex-basis:48%;">
            <canvas id="barChart" aria-label="Bar chart" role="img"></canvas>
        </div>

        <div class="chart-card" style="flex-basis:100%; margin-top:18px;">
            <canvas id="lineChart" aria-label="Line chart" role="img"></canvas>
        </div>
      </div>
    </div>
  </div>

  <!-- ASSIGNMENTS (teacher-only) -->
  <div id="assignments" class="tab" style="display:none">
    <div class="card" style="display:flex;justify-content:space-between;align-items:center">
      <h2>Assignments</h2>
      <div><button class="btn" onclick="openAssignmentCreate()">New Assignment</button></div>
    </div>

    <?php if ($flash_success): ?><div class="card" style="background:#d1fae5;color:#064e3b;margin-bottom:12px;"><?=htmlspecialchars($flash_success)?></div><?php endif; ?>
    <?php if ($flash_error): ?><div class="card" style="background:#fff1f2;color:#7f1d1d;margin-bottom:12px;"><?=htmlspecialchars($flash_error)?></div><?php endif; ?>

    <div id="assignment-list" class="card">
      <div class="table-wrap">
        <table>
          <thead><tr><th>ID</th><th>Title</th><th>Grade</th><th>Created</th><th>Actions</th></tr></thead>
          <tbody>
            <?php if (empty($assignments)): ?>
              <tr><td colspan="5" style="text-align:center;padding:18px;color:var(--muted)">You have no assignments yet.</td></tr>
            <?php else: foreach ($assignments as $a): ?>
              <tr>
                <td><?=intval($a['id'])?></td>
                <td><?=htmlspecialchars($a['title'])?></td>
                <td><?=intval($a['grade_level'])?></td>
                <td><?=htmlspecialchars($a['created_at'])?></td>
                <td class="actions-col">
                  <?php $js = json_encode($a, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?>
                  <button class="btn-light" onclick='openAssignmentEdit(<?= $js ?>)'>Edit</button>
                  <button class="btn" onclick="deleteAssignment(<?= intval($a['id']) ?>)">Delete</button>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Assignment Form -->
    <div id="assignment-form" class="card" style="display:none">
      <form id="frmAssignment" method="post" onsubmit="return submitAssignmentForm();">
        <input type="hidden" name="mode" id="modeField" value="assignment_create">
        <input type="hidden" name="id" id="assignmentId">
        <input type="hidden" name="questions_json" id="questionsJson">

        <div style="display:flex;gap:14px;align-items:flex-end">
          <div style="flex:1">
            <label style="display:block;font-weight:700;margin-bottom:6px">Title</label>
            <input type="text" id="assignmentTitle" name="title" style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6e9fb" required>
          </div>
          <div style="width:160px">
            <label style="display:block;font-weight:700;margin-bottom:6px">Grade</label>
            <input type="number" id="assignmentGrade" name="grade_level" class="small-input" min="1" max="12" value="3" style="width:100%;padding:10px;border-radius:8px;border:1px solid #e6e9fb">
          </div>
        </div>

        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px">
          <div style="font-weight:600">Questions</div>
          <div>
            <button type="button" class="btn" onclick="addQuestionRow()">Add</button>
            <button type="button" class="btn-light" onclick="fillSample()" style="margin-left:8px">Sample</button>
          </div>
        </div>

        <div id="questionsContainer" style="margin-top:12px"></div>

        <div style="display:flex;gap:12px;margin-top:18px">
          <button type="submit" class="btn" style="flex:1">Save Assignment</button>
          <button type="button" class="btn-light" style="flex:1" onclick="closeAssignmentForm()">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- REPORTS -->
  <div id="reports" class="tab" style="display:none">
    <div class="card">
      <h2>Reports</h2>
      <div style="margin-top:12px">
        <h3 style="margin-bottom:10px">Available Reports</h3>
        <div style="display:flex;gap:12px;flex-wrap:wrap">
          <button class="btn" onclick="showReport('student_progress')">Student Progress Report</button>
          <button class="btn" onclick="showReport('grade_summary')">Grade Summary Report</button>
          <button class="btn" onclick="showReport('assignment_performance')">Assignment Performance Report</button>
        </div>

        <div id="reportOutput" class="report-output">
          <em>Select a report to generate…</em>
        </div>
      </div>
    </div>
  </div>

</div> <!-- end wrapper -->

<!-- LOGOUT MODAL -->
<div id="logoutModal" class="modal-overlay">
  <div class="modal-box">
    <h3>Log Out?</h3>
    <p>Are you sure you want to log out?</p>

    <div class="modal-actions">
      <button class="btn" onclick="window.location='logout.php'">Log Out</button>
      <button class="btn-light" onclick="closeLogoutModal()">Cancel</button>
    </div>
  </div>
</div>


<script>
// single showTab + initial tab loader + analytics redraw
function showTab(tabId){
  document.querySelectorAll('.tab').forEach(n=>n.style.display='none');
  const el = document.getElementById(tabId);
  if(el) el.style.display = 'block';
  window.scrollTo({top:0,behavior:'smooth'});

  if (tabId === 'analytics') {
    // small timeout to ensure canvas is visible and layout settled
    setTimeout(() => {
      if (window.pieChart && typeof window.pieChart.resize === 'function') window.pieChart.resize();
      if (window.barChart && typeof window.barChart.resize === 'function') window.barChart.resize();
      if (window.lineChart && typeof window.lineChart.resize === 'function') window.lineChart.resize();
    }, 120);
  }
}
(function(){ const t = (new URLSearchParams(location.search)).get('tab') || 'overview'; showTab(t); })();

// --- Sidebar toggle ---
const sidebar = document.getElementById('sidebar');
function toggleSidebar(){ sidebar.classList.toggle('expanded'); }

// --- Logout modal ---
function openLogoutModal(){ document.getElementById('logoutModal').style.display = 'flex'; }
function closeLogoutModal(){ document.getElementById('logoutModal').style.display = 'none'; }

// --- Students: checkAll / print / csv ---
document.addEventListener('DOMContentLoaded', function(){

  // checkAll
  const checkAll = document.getElementById('checkAll');
  if (checkAll) {
    checkAll.addEventListener('change', function(){
      const checked = this.checked;
      document.querySelectorAll('.sel').forEach(cb => cb.checked = checked);
    });
  }

  // print selected
  const printBtn = document.getElementById('printSelectedBtn');
  if (printBtn) {
    printBtn.addEventListener('click', function(){
      const selected = Array.from(document.querySelectorAll('.sel:checked')).map(cb => cb.value);
      if (selected.length === 0) { alert('No students selected.'); return; }
      selected.forEach(id => window.open('student_report.php?id=' + encodeURIComponent(id) + '&print=1','_blank','noopener'));
    });
  }

  // export CSV
  const exportBtn = document.getElementById('exportCsvBtn');
  if (exportBtn) {
    exportBtn.addEventListener('click', function(){
      const headers = ['id','username','student_name','grade','level','score','puzzles'];
      const rows = [headers.join(',')];
      document.querySelectorAll('.sel:checked').forEach(cb => {
        const tr = cb.closest('tr');
        if (!tr) return;
        const id = tr.children[1].innerText.trim();
        const username = tr.children[2].innerText.trim().replace(/,/g,'');
        const name = tr.children[3].innerText.trim().replace(/,/g,'');
        const grade = tr.children[4].innerText.trim();
        const level = tr.children[5].innerText.trim();
        const score = tr.children[6].innerText.trim();
        const puzzles = tr.children[7].innerText.trim();
        rows.push([id, username, name, grade, level, score, puzzles].join(','));
      });
      if (rows.length <= 1) { alert('No students selected.'); return; }
      const blob = new Blob([rows.join('\n')], {type: 'text/csv'}), url = URL.createObjectURL(blob);
      const a = document.createElement('a'); a.href = url; a.download = 'students_export.csv'; document.body.appendChild(a); a.click(); document.body.removeChild(a); URL.revokeObjectURL(url);
    });
  }

  /* Charts (Chart.js) */

  // data from PHP
  var pieLabels = <?=json_encode(array_keys($pieBuckets), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;
  var pieData = <?=json_encode(array_values($pieBuckets))?>;
  var studentLabels = <?=json_encode($labels, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT)?>;
  var barData = <?=json_encode($barData)?>;
  var lineDates = <?=json_encode($lineDates)?>;
  var lineScores = <?=json_encode($lineScores)?>;

  // Pie
  const pieCtxEl = document.getElementById('pieChart');
  if (pieCtxEl) {
    const pieCtx = pieCtxEl.getContext('2d');
    window.pieChart = new Chart(pieCtx, {
      type:'pie',
      data:{ labels:pieLabels, datasets:[{ data:pieData, backgroundColor:['#60a5fa','#ec4899','#f59e0b'] }] },
      options: { responsive:true, maintainAspectRatio:false, plugins:{legend:{position:'top'}} }
    });
  }

  // Bar
  const barCtxEl = document.getElementById('barChart');
  if (barCtxEl) {
    const barCtx = barCtxEl.getContext('2d');
    window.barChart = new Chart(barCtx, {
      type:'bar',
      data:{ labels:studentLabels, datasets:[{ label:'Puzzles completed', data:barData, backgroundColor:'#7c3aed' }] },
      options:{ responsive:true, maintainAspectRatio:false, scales:{x:{ticks:{maxRotation:45,minRotation:0}}} }
    });
  }

  // Line
  const lineCtxEl = document.getElementById('lineChart');
  if (lineCtxEl) {
    const lineCtx = lineCtxEl.getContext('2d');
    window.lineChart = new Chart(lineCtx, {
      type:'line',
      data:{ labels:lineDates, datasets:[{ label:'Average score (last 30 days)', data:lineScores, borderColor:'#3b82f6', fill:false, tension:0.2 }] },
      options:{ responsive:true, maintainAspectRatio:false, scales:{x:{title:{display:true,text:'Date'}}, y:{title:{display:true,text:'Avg Score'}}} }
    });
  }

}); // DOMContentLoaded

// --- Assignments UI ---
function openAssignmentCreate(){
  document.getElementById('assignment-list').style.display = 'none';
  document.getElementById('assignment-form').style.display = 'block';
  document.getElementById('modeField').value = 'assignment_create';
  document.getElementById('assignmentId').value = '';
  document.getElementById('assignmentTitle').value = '';
  document.getElementById('assignmentGrade').value = '3';
  document.getElementById('questionsContainer').innerHTML = '';
  addQuestionRow();
  window.scrollTo({top:0,behavior:'smooth'});
}
function openAssignmentEdit(a){
  let questions = [];
  try { questions = (typeof a.questions_json === 'string') ? JSON.parse(a.questions_json) : (a.questions_json || []); }
  catch(e){ questions = []; }
  document.getElementById('assignment-list').style.display = 'none';
  document.getElementById('assignment-form').style.display = 'block';
  document.getElementById('modeField').value = 'assignment_update';
  document.getElementById('assignmentId').value = a.id || '';
  document.getElementById('assignmentTitle').value = a.title || '';
  document.getElementById('assignmentGrade').value = a.grade_level || '3';
  const container = document.getElementById('questionsContainer'); container.innerHTML = '';
  if (!questions.length) addQuestionRow(); else questions.forEach(q => addQuestionRow(q.question || '', q.answer || ''));
  window.scrollTo({top:0,behavior:'smooth'});
}
function closeAssignmentForm(){
  document.getElementById('assignment-form').style.display = 'none';
  document.getElementById('assignment-list').style.display = 'block';
}
function addQuestionRow(q='', a=''){
  const container = document.getElementById('questionsContainer');
  const row = document.createElement('div'); row.className = 'q-row'; row.style.alignItems = 'center';
  const qInput = document.createElement('input'); qInput.type='text'; qInput.className='q-question'; qInput.placeholder='Question (e.g. What is 2 + 2?)'; qInput.value = q;
  const aInput = document.createElement('input'); aInput.type='text'; aInput.className='q-answer'; aInput.placeholder='Answer (optional)'; aInput.value = a;
  const btn = document.createElement('button'); btn.type='button'; btn.className='btn-q-remove'; btn.textContent='Remove'; btn.onclick=function(){row.remove();};
  row.appendChild(qInput); row.appendChild(aInput); row.appendChild(btn); container.appendChild(row);
}
function fillSample(){ const c=document.getElementById('questionsContainer'); c.innerHTML=''; addQuestionRow('What is 5 + 3?','8'); addQuestionRow('What is 9 - 4?','5'); addQuestionRow('How many sides does a triangle have?','3'); }
function submitAssignmentForm(){
  const qs = [];
  document.querySelectorAll('#questionsContainer .q-row').forEach(r=>{
    const q = (r.querySelector('.q-question') || {}).value || '';
    const a = (r.querySelector('.q-answer') || {}).value || '';
    if (q.trim() !== '') qs.push({question:q.trim(), answer:a.trim()});
  });
  document.getElementById('questionsJson').value = JSON.stringify(qs);
  return true; // allow form submit
}
function deleteAssignment(id){
  if (!confirm('Delete this assignment?')) return;
  const form = document.createElement('form');
  form.method='POST';
  form.innerHTML = '<input type="hidden" name="mode" value="assignment_delete"><input type="hidden" name="id" value="'+id+'">';
  document.body.appendChild(form); form.submit();
}

// --- Reports loader ---
function showReport(type){
    const box = document.getElementById("reportOutput");
    box.innerHTML = "<em>Generating report...</em>";

    fetch("report_generator.php?type=" + encodeURIComponent(type) + "&teacher=1")
    .then(r => r.text())
    .then(html => box.innerHTML = html)
    .catch(() => box.innerHTML = "<b>Error loading report.</b>");
}

</script>
</body>
</html>
