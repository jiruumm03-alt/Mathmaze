<?php
// dashboard_admin.php — CLEAN FINAL (Progress tab fixed 2-column layout)
require_once __DIR__ . '/auth.php';
require_login();
if (!is_admin()) die("Access denied.");

$pdo = pdo();
$current = current_user();

/* ===========================
   PROCESS ASSIGNMENTS CRUD (POST)
   =========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mode = $_POST['mode'] ?? '';

    if ($mode === 'assignment_create' || $mode === 'assignment_update') {
        if (!is_teacher() && !is_admin()) die("Access denied.");

        $title = trim($_POST['title'] ?? '');
        $grade = intval($_POST['grade_level'] ?? 1);
        $id    = intval($_POST['id'] ?? 0);

        $questions_json = $_POST['questions_json'] ?? '[]';
        $decoded = json_decode($questions_json, true);

        if (!is_array($decoded)) {
            $questions_json = json_encode([]);
        } else {
            $clean = [];
            foreach ($decoded as $q) {
                $qt = trim($q['question'] ?? '');
                $ans = trim($q['answer'] ?? '');
                if ($qt !== '') $clean[] = ['question' => $qt, 'answer' => $ans];
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
                $stmt->execute([':title'=>$title,':grade'=>$grade,':tid'=>$current['id'],':q'=>$questions_json]);
                $_SESSION['flash_success'] = "Assignment created.";
            } else {
                $stmt = $pdo->prepare("UPDATE assignments SET title=:title, grade_level=:grade, questions_json=:q WHERE id=:id");
                $stmt->execute([':title'=>$title,':grade'=>$grade,':q'=>$questions_json,':id'=>$id]);
                $_SESSION['flash_success'] = "Assignment updated.";
            }
        } catch (PDOException $e) {
            $_SESSION['flash_error'] = "DB error: ".$e->getMessage();
        }

        header("Location: ".$_SERVER['PHP_SELF']."?section=assignments");
        exit;
    }

    if ($mode === 'assignment_delete') {
        if (!is_teacher() && !is_admin()) die("Access denied.");
        $id = intval($_POST['id'] ?? 0);
        if ($id) {
            $pdo->prepare("DELETE FROM assignments WHERE id=:id")->execute([':id'=>$id]);
            $_SESSION['flash_success'] = "Assignment deleted.";
        }
        header("Location: ".$_SERVER['PHP_SELF']."?section=assignments");
        exit;
    }
}

/* ===========================
   FETCH DATA
   =========================== */
$counts = [];
foreach (['students','teachers','progress'] as $t) {
    try {
        $counts[$t] = (int)$pdo->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
    } catch (Exception $e) {
        $counts[$t] = 0;
    }
}

$teachers = $pdo->query("SELECT id, username, full_name, grade_level, created_at FROM teachers ORDER BY id DESC")->fetchAll();
$students = $pdo->query("
    SELECT s.id, s.username, s.student_name, s.grade_level, t.full_name AS teacher_name
    FROM students s
    LEFT JOIN teachers t ON t.id = s.teacher_id
    ORDER BY s.id DESC
")->fetchAll();
$assignments = $pdo->query("
    SELECT a.*, t.full_name AS teacher_name
    FROM assignments a
    LEFT JOIN teachers t ON a.teacher_id = t.id
    ORDER BY a.created_at DESC
")->fetchAll();

/* ===========================
   PROGRESS QUERIES
   - progress: id, student_id, grade_level, level, score, time_spent, date_updated
   =========================== */
$progress_records = [];
$student_summary = [];
$grade_summary = [];

try {
    // Latest progress records with student info
    $stmt = $pdo->query("
        SELECT p.*, s.username, s.student_name
        FROM progress p
        LEFT JOIN students s ON s.id = p.student_id
        ORDER BY p.date_updated DESC, p.id DESC
        LIMIT 1000
    ");
    $progress_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Student summary: highest level, best score, total time, last update
    $stmt = $pdo->query("
        SELECT
          p.student_id,
          COALESCE(s.username,'Unknown') AS username,
          COALESCE(s.student_name,'Unknown') AS student_name,
          MAX(p.level) AS highest_level,
          MAX(p.score) AS best_score,
          COALESCE(SUM(p.time_spent),0) AS total_time_spent,
          MAX(p.date_updated) AS last_updated
        FROM progress p
        LEFT JOIN students s ON s.id = p.student_id
        GROUP BY p.student_id
        ORDER BY total_time_spent DESC
        LIMIT 500
    ");
    $student_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Grade summary: per grade_level in progress
    $stmt = $pdo->query("
        SELECT
          p.grade_level,
          COUNT(DISTINCT p.student_id) AS students,
          ROUND(AVG(NULLIF(p.score, 0)),2) AS avg_score,
          ROUND(AVG(NULLIF(p.time_spent, 0)),2) AS avg_time_spent
        FROM progress p
        GROUP BY p.grade_level
        ORDER BY p.grade_level
    ");
    $grade_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $_SESSION['flash_error'] = "Progress DB error: ".$e->getMessage();
}

$flash_success = $_SESSION['flash_success'] ?? null;
$flash_error   = $_SESSION['flash_error'] ?? null;
unset($_SESSION['flash_success'], $_SESSION['flash_error']);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin Dashboard</title>
<meta name="viewport" content="width=device-width,initial-scale=1">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

<style>
/* ---------- Global & Theme ---------- */
*{box-sizing:border-box;margin:0;padding:0;font-family:"Poppins",Arial,sans-serif}
:root{
  --purple-1:#4f46e5;
  --purple-2:#9333ea;
  --bg:#f4f6f8;
  --card:#fff;
  --muted:#6b7280;
  --sidebar-w:240px;
  --sidebar-collapsed-w:64px;
}
body{background:var(--bg);display:flex;min-height:100vh;overflow:hidden;color:#111}

/* ---------- Sidebar ---------- */
.sidebar{
  width:var(--sidebar-w);
  background:linear-gradient(180deg,var(--purple-1),var(--purple-2));
  color:#fff;padding:20px 12px;display:flex;flex-direction:column;transition:width .25s ease;
}
.sidebar.collapsed{width:var(--sidebar-collapsed-w)}
.brand{display:flex;align-items:center;gap:12px;margin-bottom:18px}
.brand img{width:38px;height:38px;border-radius:8px;background:#fff}
.brand span{font-weight:700;font-size:16px}
.sidebar.collapsed .brand span{display:none}

.nav-btn{display:flex;align-items:center;gap:12px;padding:10px;margin-bottom:8px;border-radius:10px;cursor:pointer;color:#fff}
.nav-btn:hover{background:rgba(255,255,255,.06)}
.nav-btn.active{background:rgba(255,255,255,.12)}
.nav-btn i{width:36px;height:36px;border-radius:8px;background:rgba(255,255,255,.14);display:flex;align-items:center;justify-content:center}
.label{white-space:nowrap}
.sidebar.collapsed .label{display:none}

/* ---------- Main area + Top nav ---------- */
.main{flex:1;display:flex;flex-direction:column;overflow:hidden}

/* fixed top nav */
.topnav{
  height:64px;padding:12px 20px;display:flex;align-items:center;justify-content:space-between;
  background:linear-gradient(90deg,var(--purple-1),var(--purple-2));color:#fff;font-weight:700;
  box-shadow:0 3px 12px rgba(0,0,0,.06);position:sticky;top:0;z-index:60;
}
.topnav .brand-title{font-size:18px}
.topnav .right{display:flex;align-items:center;gap:12px}
.topnav .admin-name{padding:6px 10px;border-radius:8px;background:rgba(255,255,255,0.06);font-weight:600}
.btn_top{padding:8px 14px;border-radius:8px;border:0;background:linear-gradient(135deg,var(--purple-1),var(--purple-2));color:#fff;cursor:pointer}

/* ---------- Content ---------- */
.content{flex:1;padding:22px;overflow:auto}
.container-card{background:var(--card);padding:22px;border-radius:14px;box-shadow:0 8px 26px rgba(0,0,0,.06);margin-bottom:22px}

/* stats */
.stats{display:flex;gap:16px}
.stat{flex:1;background:#fff;padding:18px;border-radius:12px;border:1px solid #eef2ff;text-align:center}
.stat .num{font-size:28px;font-weight:800;margin-top:8px}

/* tables */
.table-wrap{overflow:auto;margin-top:12px}
table{width:100%;border-collapse:collapse;table-layout:fixed}
thead tr{background:linear-gradient(90deg,var(--purple-1),var(--purple-2));color:#fff}
th,td{padding:12px 10px;border-bottom:1px solid #eef2ff;font-size:14px;vertical-align:middle;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
tbody tr:hover{background:#f7f4ff}

/* column widths */
th:nth-child(1), td:nth-child(1){ width:60px; }
th:nth-child(2), td:nth-child(2){ width:180px; }
th:nth-child(3), td:nth-child(3){ width:220px; }
th:nth-child(4), td:nth-child(4){ width:80px; }
th:nth-child(5), td:nth-child(5){ width:180px; }
th:nth-child(6), td:nth-child(6){ width:140px; }

td:last-child, th:last-child{ text-align:center !important; }

.card-scroll{ max-height:520px; overflow:auto; border-radius:12px; padding:0; }

/* buttons */
.btn-delete{background:#e11d48;color:#fff;border:0;padding:8px 12px;border-radius:8px;cursor:pointer}
.btn-small{padding:8px 12px;border-radius:8px;background:linear-gradient(135deg,var(--purple-1),var(--purple-2));color:#fff;border:0;cursor:pointer;text-decoration:none}

/* --- FINAL FIX: Prevent summary boxes from overflowing --- */

/* Keep the entire progress card centered and limited */
#progress.container-card {
    max-width: 1400px;
    margin-left: auto;
    margin-right: auto;
}

/* Force both summary boxes to always fit inside container */
.progress-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    width: 100%;
}

/* Prevent shrinking (THIS is what was breaking your layout) */
.progress-card {
    min-width: 500px;      /* You may adjust to 480–520 if needed */
    background: #ffffff !important;
}

/* When screen is narrower, stack them instead of overflowing */
@media (max-width: 1200px) {
    .progress-grid {
        grid-template-columns: 1fr;
    }
    .progress-card {
        min-width: unset;
        width: 100%;
    }
}

/* ---------- Progress (FIXED 2-column layout) ---------- */
/* Progress grid: FIXED 2 columns — choice A */
.progress-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px;align-items:start}

/* solid cards (remove transparency issues) */
.progress-card{
  background:#ffffff !important;
  border:1px solid #e6e7eb !important;
  border-radius:12px !important;
  padding:16px !important;
  box-shadow:0 6px 18px rgba(0,0,0,0.04);
}

.progress-card table.summary-table{background:#ffffff !important;border-radius:8px;width:100%}
.progress-card thead tr{background:#f3f4f6 !important;color:#111 !important}
.progress-card td, .progress-card th{background:#ffffff !important}
.summary-table th, .summary-table td{font-size:13px;padding:8px}

/* ensure progress container doesn't inherit sidebar transparency */
#progress.container-card{ background:#ffffff !important; }

/* responsive fallback (still shows two columns, but will stack if extremely narrow) */
@media(max-width:700px){
  .progress-grid{grid-template-columns:1fr}
}

/* assignment/form styles */
.assign-card{padding:18px 22px}
.assign-form{max-width:920px;margin-top:14px}
.assign-row{display:flex;gap:10px;align-items:center;margin-top:10px}
.assign-row input[type="text"], .assign-row input[type="number"]{flex:1;padding:10px;border-radius:10px;border:1px solid #dbe4fb;font-size:14px;background:#fff;}
.q-row{display:flex;gap:8px;align-items:center;margin-top:10px}
.q-row input{flex:1;padding:10px;border-radius:8px;border:1px solid #e6e9f8}
.q-row .btn-q-remove{background:#ef4444;border:0;color:#fff;padding:8px 10px;border-radius:8px;cursor:pointer}

/* flash messages */
.flash-success{background:#d1fae5;color:#064e3b;padding:12px;border-radius:8px;margin-bottom:12px}
.flash-error{background:#fff1f2;color:#7f1d1d;padding:12px;border-radius:8px;margin-bottom:12px}

/* responsive small screens (general) */
@media(max-width:1100px){
  table{table-layout:auto}
  th:nth-child(2),td:nth-child(2){ width:auto }
  th:nth-child(3),td:nth-child(3){ width:auto }
  th:nth-child(5),td:nth-child(5){ width:auto }
}
@media(max-width:900px){
  .sidebar{position:fixed;height:100vh;transform:translateX(-110%);z-index:99}
  .sidebar.open{transform:translateX(0)}
  .content{padding:12px}
  .table-wrap{font-size:13px}
  .topnav{padding:10px}
}
</style>
</head>
<body>

<!-- SIDEBAR -->
<aside id="sidebar" class="sidebar" aria-label="Main navigation">
  <div class="brand">
    <img src="favicon-32x32.png" alt="">
    <span>Admin Panel</span>
  </div>

  <div class="nav-btn active" data-section="overview"><i class="fa-solid fa-gauge"></i><span class="label">Dashboard</span></div>
  <div class="nav-btn" data-section="teachers"><i class="fa-solid fa-user-tie"></i><span class="label">Teachers</span></div>
  <div class="nav-btn" data-section="students"><i class="fa-solid fa-users"></i><span class="label">Students</span></div>
  <div class="nav-btn" data-section="progress"><i class="fa-solid fa-chart-line"></i><span class="label">Progress</span></div>
  <div class="nav-btn" data-section="reports"><i class="fa-solid fa-file-lines"></i><span class="label">Reports</span></div>

  <div id="collapseToggle" class="nav-btn" title="Collapse sidebar" style="margin-top:auto"><i class="fa-solid fa-angles-left"></i><span class="label">Collapse</span></div>
</aside>

<!-- MAIN -->
<div class="main">

  <!-- TOP NAV -->
  <header class="topnav">
    <div class="brand-title">Admin Dashboard</div>
    <div class="right">
      <div class="admin-name"><?= htmlspecialchars($current['name']) ?></div>
      <button class="btn_top" onclick="openLogoutModal()">Logout</button>
    </div>
  </header>

  <!-- CONTENT -->
  <main class="content">

    <!-- OVERVIEW -->
    <section id="overview" class="container-card">
      <h2 style="margin-bottom:12px">Overview</h2>
      <div class="stats">
        <div class="stat">Students<div class="num"><?= $counts['students'] ?></div></div>
        <div class="stat">Teachers<div class="num"><?= $counts['teachers'] ?></div></div>
        <div class="stat">Progress Records<div class="num"><?= $counts['progress'] ?></div></div>
      </div>
    </section>

    <!-- TEACHERS -->
    <section id="teachers" class="container-card" style="display:none">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <h2>Teachers</h2>
        <a href="register.php" class="btn-small">Add Teacher</a>
      </div>

      <div class="card-scroll teachers-card" style="margin-top:14px">
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>ID</th><th>Username</th><th>Name</th><th>Grade</th><th>Created</th><th>Delete</th></tr>
            </thead>
            <tbody>
              <?php foreach ($teachers as $t): ?>
              <tr>
                <td><?= $t['id'] ?></td>
                <td title="<?= htmlspecialchars($t['username']) ?>"><?= htmlspecialchars($t['username']) ?></td>
                <td title="<?= htmlspecialchars($t['full_name']) ?>"><?= htmlspecialchars($t['full_name']) ?></td>
                <td><?= $t['grade_level'] ?></td>
                <td title="<?= $t['created_at'] ?>"><?= $t['created_at'] ?></td>
                <td><button class="btn-delete" onclick="deleteItem('teacher', <?= $t['id'] ?>)">Delete</button></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- STUDENTS -->
    <section id="students" class="container-card" style="display:none">
      <h2>Students</h2>
      <div class="card-scroll" style="margin-top:14px">
        <div class="table-wrap">
          <table>
            <thead><tr><th>ID</th><th>Username</th><th>Name</th><th>Grade</th><th>Teacher</th><th>Delete</th></tr></thead>
            <tbody>
              <?php foreach ($students as $s): ?>
              <tr>
                <td><?= $s['id'] ?></td>
                <td title="<?= htmlspecialchars($s['username']) ?>"><?= htmlspecialchars($s['username']) ?></td>
                <td title="<?= htmlspecialchars($s['student_name']) ?>"><?= htmlspecialchars($s['student_name']) ?></td>
                <td><?= $s['grade_level'] ?></td>
                <td title="<?= htmlspecialchars($s['teacher_name'] ?? 'None') ?>"><?= htmlspecialchars($s['teacher_name'] ?? 'None') ?></td>
                <td><button class="btn-delete" onclick="deleteItem('student', <?= $s['id'] ?>)">Delete</button></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>

    <!-- PROGRESS -->
    <section id="progress" class="container-card" style="display:none">
      <div style="display:flex;justify-content:space-between;align-items:center">
        <h2>Progress</h2>
        <div style="color:var(--muted)">Showing latest progress records and summaries</div>
      </div>

      <?php if ($flash_error): ?><div class="flash-error" style="margin-top:12px"><?= htmlspecialchars($flash_error) ?></div><?php endif; ?>

      <div class="progress-grid" style="margin-top:14px">
        <div class="progress-card">
          <strong>Student Summary</strong>
          <div style="margin-top:10px;overflow:auto">
            <table class="summary-table" style="width:100%;border-collapse:collapse">
              <thead>
                <tr style="background:#f3f4f6"><th>Student</th><th>Highest Level</th><th>Best Score</th><th>Total Time (s)</th><th>Last Update</th></tr>
              </thead>
              <tbody>
                <?php if (empty($student_summary)): ?>
                  <tr><td colspan="5" style="padding:8px;text-align:center">No progress data</td></tr>
                <?php else: foreach ($student_summary as $ss): ?>
                  <tr>
                    <td title="<?= htmlspecialchars($ss['student_name']) ?>"><?= htmlspecialchars($ss['username'] . ($ss['student_name'] ? ' — '.$ss['student_name'] : '')) ?></td>
                    <td><?= htmlspecialchars($ss['highest_level']) ?></td>
                    <td><?= htmlspecialchars($ss['best_score']) ?></td>
                    <td><?= htmlspecialchars($ss['total_time_spent']) ?></td>
                    <td title="<?= htmlspecialchars($ss['last_updated']) ?>"><?= htmlspecialchars($ss['last_updated']) ?></td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="progress-card">
          <strong>Grade Summary</strong>
          <div style="margin-top:10px;overflow:auto">
            <table class="summary-table" style="width:100%;border-collapse:collapse">
              <thead><tr style="background:#f3f4f6"><th>Grade</th><th>Students</th><th>Avg Score</th><th>Avg Time (s)</th></tr></thead>
              <tbody>
                <?php if (empty($grade_summary)): ?>
                  <tr><td colspan="4" style="padding:8px;text-align:center">No grade data</td></tr>
                <?php else: foreach ($grade_summary as $gs): ?>
                  <tr>
                    <td><?= htmlspecialchars($gs['grade_level']) ?></td>
                    <td><?= htmlspecialchars($gs['students']) ?></td>
                    <td><?= htmlspecialchars($gs['avg_score'] ?? '0') ?></td>
                    <td><?= htmlspecialchars($gs['avg_time_spent'] ?? '0') ?></td>
                  </tr>
                <?php endforeach; endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div style="margin-top:18px" class="card-scroll">
        <div class="table-wrap">
          <table>
            <thead>
              <tr><th>ID</th><th>Student</th><th>Grade</th><th>Level</th><th>Score</th><th>Time Spent (s)</th><th>Updated</th></tr>
            </thead>
            <tbody>
              <?php if (empty($progress_records)): ?>
                <tr><td colspan="7" style="text-align:center;padding:18px">No progress records yet.</td></tr>
              <?php else: foreach ($progress_records as $p): ?>
                <tr>
                  <td><?= htmlspecialchars($p['id']) ?></td>
                  <td title="<?= htmlspecialchars($p['student_name'] ?? $p['username'] ?? 'Unknown') ?>"><?= htmlspecialchars(($p['username']??'') . ($p['student_name'] ? ' — '.$p['student_name'] : '')) ?></td>
                  <td><?= htmlspecialchars($p['grade_level']) ?></td>
                  <td><?= htmlspecialchars($p['level']) ?></td>
                  <td><?= htmlspecialchars($p['score']) ?></td>
                  <td><?= htmlspecialchars($p['time_spent']) ?></td>
                  <td title="<?= htmlspecialchars($p['date_updated']) ?>"><?= htmlspecialchars($p['date_updated']) ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
    <!-- ASSIGNMENTS -->
   

    <!-- REPORTS -->
    <section id="reports" class="container-card" style="display:none">
      <h2>Reports</h2>
     <div style="margin-top:12px">

    <h3 style="margin-bottom:10px">Available Reports</h3>

    <div class="report-buttons" style="display:flex;gap:12px;flex-wrap:wrap">

        <button class="btn-small" onclick="showReport('student_progress')">
            Student Progress Report
        </button>

        <button class="btn-small" onclick="showReport('grade_summary')">
            Grade Summary Report
        </button>

        <button class="btn-small" onclick="showReport('assignment_performance')">
            Assignment Performance Report
        </button>

    </div>

    <div id="reportOutput" style="
        margin-top:20px;
        background:#fff;
        padding:18px;
        border-radius:12px;
        min-height:150px;
        box-shadow:0 4px 12px rgba(0,0,0,0.06);
    ">
        <em>Select a report to generate…</em>
    </div>

</div>

    </section>

  </main>
</div>

<!-- LOGOUT MODAL -->
<div id="logoutModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);align-items:center;justify-content:center;z-index:9999">
  <div style="background:#fff;padding:22px;border-radius:12px;width:90%;max-width:420px;text-align:center">
    <h3>Log Out?</h3>
    <p>Are you sure you want to log out?</p>
    <div style="display:flex;gap:12px;margin-top:18px">
      <button onclick="location='logout.php'" class="btn-small" style="flex:1">Logout</button>
      <button onclick="closeLogoutModal()" class="btn-small" style="flex:1;background:#d1d5db;color:black">Cancel</button>
    </div>
  </div>
</div>

<script>
/* Sidebar collapse toggle */
let collapsed = false;
document.getElementById('collapseToggle').addEventListener('click', ()=> {
  collapsed = !collapsed;
  document.getElementById('sidebar').classList.toggle('collapsed', collapsed);

  // when collapsed, minimize labels and ensure progress summary stays solid
  if (collapsed) {
    // no layout change for progress (choice A) — only visual sidebar shrink
    // keep summaries solid by re-applying background (useful for some browsers)
    document.querySelectorAll('.progress-card').forEach(el=>{
      el.style.background = '#ffffff';
    });
  } else {
    document.querySelectorAll('.progress-card').forEach(el=>{
      el.style.background = '#ffffff';
    });
  }
});

/* Section switching */
document.querySelectorAll('.nav-btn').forEach(btn=>{
  btn.addEventListener('click', ()=>{
    const section = btn.dataset.section;
    if (!section) return;
    document.querySelectorAll('.nav-btn').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');

    document.querySelectorAll('main > section').forEach(s => s.style.display = 'none');

    const el = document.getElementById(section);
    if (el) el.style.display = 'block';

    closeAssignmentForm();
    window.scrollTo({top:0,behavior:'smooth'});
  });
});

/* Logout modal helpers */
function openLogoutModal(){ document.getElementById('logoutModal').style.display = 'flex'; }
function closeLogoutModal(){ document.getElementById('logoutModal').style.display = 'none'; }

/* Delete teacher/student */
function deleteItem(type,id){
  if (!confirm('Delete this '+type+'?')) return;
  const form = document.createElement('form');
  form.method = 'POST';
  form.action = (type === 'teacher') ? 'action_delete_teacher.php' : 'action_delete_student.php';
  const input = document.createElement('input');
  input.type = 'hidden'; input.name = 'id'; input.value = id;
  form.appendChild(input);
  document.body.appendChild(form);
  form.submit();
}


/* initial: show the 'overview' section */
(function showInitial(){
  document.querySelectorAll('main > section').forEach(s => s.style.display = 'none');
  const sec = document.getElementById('overview');
  if (sec) sec.style.display = 'block';
})();

function showReport(type){
    const box = document.getElementById("reportOutput");

    box.innerHTML = "<em>Generating report...</em>";

    fetch("report_generator.php?type=" + encodeURIComponent(type))
    .then(r => r.text())
    .then(html => box.innerHTML = html)
    .catch(() => box.innerHTML = "<b>Error loading report.</b>");
}

</script>

</body>
</html>
