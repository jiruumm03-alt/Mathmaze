<?php
// dashboard_admin.php — fully restyled admin dashboard with inline CSS
require_once __DIR__ . '/auth.php';
require_login();
if (!is_admin()) die("Access denied.");

$pdo = pdo();

// fetch counts
$counts = [];
foreach (['students','teachers','progress'] as $t) {
    $stmt = $pdo->query("SELECT COUNT(*) FROM {$t}");
    $counts[$t] = $stmt->fetchColumn();
}

// fetch teachers
$stmt = $pdo->query("SELECT id,username,full_name,grade_level,created_at FROM teachers ORDER BY grade_level");
$teachers = $stmt->fetchAll();

// student preview
$students = $pdo->query("SELECT id,username,student_name,grade_level,teacher_id FROM students LIMIT 20")->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Admin Dashboard — MathMaze</title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<style>
/* ===== GLOBAL ===== */
*{margin:0;padding:0;box-sizing:border-box;font-family:"Poppins",Arial,sans-serif}
body{background:#f4f6f8;color:#222;min-height:100vh;display:flex;flex-direction:column}

/* ===== NAVBAR ===== */
.navbar{
  display:flex;align-items:center;gap:10px;
  background:linear-gradient(135deg,#4f46e5,#9333ea);
  color:#fff;padding:16px 25px;
  box-shadow:0 3px 12px rgba(0,0,0,.15);
}
.navbar img{width:36px;height:36px}
.navbar h1{font-size:20px;font-weight:600}
.nav-right{margin-left:auto;display:flex;align-items:center;gap:10px}

/* ===== BUTTONS ===== */
.btn{
  display:inline-block;padding:8px 14px;border-radius:8px;
  background:linear-gradient(135deg,#4f46e5,#9333ea);
  color:white;text-decoration:none;font-size:14px;
}
.btn:hover{opacity:.9}

/* ===== LAYOUT ===== */
.wrapper{padding:28px;flex:1;display:flex;justify-content:center}
.card{
  background:white;width:100%;max-width:1250px;
  padding:26px;border-radius:14px;
  box-shadow:0 8px 26px rgba(0,0,0,.06);
  animation:fadeIn .55s ease;
}

/* ===== STATS ===== */
.stats{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:25px}
.stat-box{
  flex:1;min-width:180px;background:white;
  padding:20px;border-radius:12px;
  border:1px solid #e2e8f0;
  text-align:center;
}
.stat-box h3{font-size:16px;color:#4f46e5;margin-bottom:8px}
.stat-box .num{font-size:26px;font-weight:700}

/* ===== TABLE ===== */
.table-wrap{overflow:auto;margin-top:12px}
table{width:100%;border-collapse:collapse}
thead tr{background:linear-gradient(90deg,#4f46e5,#9333ea);color:#fff}
th,td{padding:10px;text-align:left;font-size:14px}
tbody tr{background:#fff;border-bottom:1px solid #edf2f7}
tbody tr:hover{background:#f3f0ff}

/* ===== ANIMATIONS ===== */
@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
</style>
</head>

<body>

<header class="navbar">
  <img src="favicon-32x32.png" alt="">
  <h1>MathMaze — Admin Dashboard</h1>

  <div class="nav-right">
    <span style="opacity:.9">Hello, <?=htmlspecialchars(current_user()['name'])?></span>
    <a class="btn" href="#" onclick="openLogoutModal()">Logout</a>
  </div>
</header>

<div class="wrapper">
<div class="card">

  <h2 style="margin-bottom:16px;font-size:22px">Overview</h2>

  <!-- STATISTICS -->
  <div class="stats">
    <div class="stat-box">
      <h3>Students</h3>
      <div class="num"><?=$counts['students']?></div>
    </div>
    <div class="stat-box">
      <h3>Teachers</h3>
      <div class="num"><?=$counts['teachers']?></div>
    </div>
    <div class="stat-box">
      <h3>Progress Records</h3>
      <div class="num"><?=$counts['progress']?></div>
    </div>
  </div>

  <!-- ACTION BUTTONS -->
  <div style="display:flex;gap:10px;margin-bottom:20px">
    <a class="btn" href="register.php">Create Teacher</a>
    <a class="btn" href="assignments.php">Assignments</a>
  </div>

  <!-- TEACHER LIST -->
  <h2 style="margin-top:10px;font-size:20px">Teachers</h2>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th><th>Username</th><th>Full Name</th><th>Grade</th><th>Created</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($teachers as $t): ?>
        <tr>
          <td><?=$t['id']?></td>
          <td><?=htmlspecialchars($t['username'])?></td>
          <td><?=htmlspecialchars($t['full_name'])?></td>
          <td><?=$t['grade_level']?></td>
          <td><?=$t['created_at']?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <!-- STUDENT PREVIEW -->
  <h2 style="margin-top:30px;font-size:20px">Students (Preview)</h2>
  <div class="table-wrap">
    <table>
      <thead style="background:#e5e0ff;color:#333">
        <tr>
          <th>ID</th><th>Username</th><th>Name</th><th>Grade</th><th>Teacher</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($students): foreach ($students as $s): ?>
        <tr>
          <td><?=$s['id']?></td>
          <td><?=htmlspecialchars($s['username'])?></td>
          <td><?=htmlspecialchars($s['student_name'])?></td>
          <td><?=$s['grade_level']?></td>
          <td><?= $s['teacher_id'] ?? 'NULL' ?></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="5" style="text-align:center;padding:12px">No students yet.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>
</div>
<!-- LOGOUT MODAL -->
<div id="logoutModal" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <h3>Log Out?</h3>
    <p>Are you sure you want to log out?</p>

    <div class="modal-actions">
      <button class="btn-confirm" onclick="confirmLogout()">Log Out</button>
      <button class="btn-cancel" onclick="closeLogoutModal()">Cancel</button>
    </div>
  </div>
</div>

<style>
/* Overlay */
.modal-overlay{
  position:fixed;top:0;left:0;width:100%;height:100%;
  background:rgba(0,0,0,0.4);
  display:flex;justify-content:center;align-items:center;
  animation:fadeIn .25s ease;
  z-index:9999;
}

/* Modal box */
.modal-box{
  width:90%;max-width:360px;background:#fff;
  padding:25px;border-radius:14px;text-align:center;
  box-shadow:0 10px 35px rgba(0,0,0,0.15);
  animation:slideUp .25s ease;
}

/* Title */
.modal-box h3{
  font-size:20px;margin-bottom:10px;color:#4f46e5;
}

/* Buttons */
.modal-actions{
  display:flex;gap:10px;margin-top:20px;justify-content:center;
}

.btn-confirm{
  flex:1;padding:10px;border:none;border-radius:8px;
  background:linear-gradient(135deg,#4f46e5,#9333ea);
  color:#fff;font-size:15px;cursor:pointer;
}

.btn-cancel{
  flex:1;padding:10px;border:none;border-radius:8px;
  background:#e5e7eb;color:#111;font-size:15px;cursor:pointer;
}

/* Animations */
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
@keyframes slideUp{from{transform:translateY(20px);opacity:0}to{transform:translateY(0);opacity:1}}
</style>

<script>
function openLogoutModal(){
  document.getElementById("logoutModal").style.display = "flex";
}
function closeLogoutModal(){
  document.getElementById("logoutModal").style.display = "none";
}
function confirmLogout(){
  window.location.href = "logout.php";
}
</script>

</body>
</html>
