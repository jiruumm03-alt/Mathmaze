<?php
// assignments.php — fully restyled with inline CSS & unified theme
require_once __DIR__ . '/auth.php';
require_login();
$pdo = pdo();

$action = $_REQUEST['action'] ?? 'list';
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;

$current = current_user();
$isAdmin = is_admin();

/* --------------------------
   CRUD PROCESSING
--------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!is_teacher() && !$isAdmin) die("Access denied.");

    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $grade = intval($_POST['grade_level'] ?? 0);
    $tid = $current['id'];

    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO assignments 
            (title, description, grade_level, teacher_id) 
            VALUES (:t, :d, :g, :tid)");
        $stmt->execute([':t'=>$title,':d'=>$desc,':g'=>$grade,':tid'=>$tid]);

        header("Location: assignments.php");
        exit;

    } elseif ($action === 'edit' && $id) {
        $stmt = $pdo->prepare("UPDATE assignments 
            SET title=:t, description=:d, grade_level=:g 
            WHERE id=:id");
        $stmt->execute([':t'=>$title,':d'=>$desc,':g'=>$grade,':id'=>$id]);

        header("Location: assignments.php");
        exit;

    } elseif ($action === 'delete' && $id) {
        $stmt = $pdo->prepare("DELETE FROM assignments WHERE id=:id");
        $stmt->execute([':id'=>$id]);

        header("Location: assignments.php");
        exit;
    }
}

/* --------------------------
   INLINE CSS
--------------------------- */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Assignments — MathMaze</title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<style>
/* GLOBAL */
*{margin:0;padding:0;box-sizing:border-box;font-family:"Poppins",Arial,sans-serif}
body{min-height:100vh;display:flex;flex-direction:column;background:#f4f6f8;color:#222}

/* NAVBAR */
.navbar{
  display:flex;align-items:center;gap:10px;
  background:linear-gradient(135deg,#4f46e5,#9333ea);
  padding:16px 25px;color:#fff;
  box-shadow:0 3px 12px rgba(0,0,0,.15);
}
.navbar img{width:36px;height:36px}
.navbar h1{font-size:20px;font-weight:600}
.nav-right{margin-left:auto;display:flex;gap:10px}

/* BUTTON */
.btn{
  padding:8px 14px;border-radius:8px;
  background:linear-gradient(135deg,#4f46e5,#9333ea);
  color:white;text-decoration:none;font-size:14px;
}
.btn:hover{opacity:.9}

/* PAGE WRAPPER */
.wrapper{flex:1;padding:28px;display:flex;justify-content:center}
.card{
  background:white;width:100%;max-width:1250px;
  padding:26px;border-radius:14px;
  box-shadow:0 8px 26px rgba(0,0,0,.06);
  animation:fadeIn .55s ease;
}

/* TABLE */
.table-wrap{overflow:auto;margin-top:16px}
table{width:100%;border-collapse:collapse}
thead tr{background:linear-gradient(90deg,#4f46e5,#9333ea);color:#fff}
th,td{padding:10px;font-size:14px;text-align:left}
tbody tr{background:white;border-bottom:1px solid #eef2ff}
tbody tr:hover{background:#f9f5ff}

/* FORMS */
.form-card{
  background:#fff;padding:26px;border-radius:12px;
  max-width:650px;margin:auto;
  box-shadow:0 8px 26px rgba(0,0,0,.06);
  animation:fadeIn .5s ease;
}
label{display:block;font-size:14px;margin-top:12px;font-weight:500}
input,textarea{
  width:100%;padding:10px;margin-top:6px;border:1px solid #d5ddec;
  border-radius:8px;font-size:14px;
}
textarea{resize:vertical;height:130px}
input:focus,textarea:focus{
  border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,0.15);outline:none;
}

button{
  width:100%;padding:12px;margin-top:20px;
  border:none;border-radius:8px;
  background:linear-gradient(135deg,#4f46e5,#9333ea);
  color:#fff;font-size:16px;cursor:pointer;
}
button:hover{opacity:.9}

/* ANIMATION */
@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
</style>
</head>
<body>

<?php
/* -------------------------
   COMMON NAVBAR
-------------------------- */
?>
<header class="navbar">
  <img src="favicon-32x32.png" alt="">
  <h1>Assignments</h1>

  <div class="nav-right">
    <?php if (is_admin()): ?>
      <a class="btn" href="dashboard_admin.php">Back</a>
    <?php else: ?>
      <a class="btn" href="dashboard_teacher.php">Back</a>
    <?php endif; ?>

    <a class="btn" href="#" onclick="openLogoutModal()">Logout</a>
    </a>
  </div>
</header>


<?php
/* ===========================================================
   MODE: LIST
=========================================================== */
if ($action === 'list'):
    $rows = $pdo->query("
        SELECT a.*, t.full_name AS teacher_name
        FROM assignments a
        LEFT JOIN teachers t ON a.teacher_id = t.id
        ORDER BY a.created_at DESC
    ")->fetchAll();
?>
<div class="wrapper">
<div class="card">

  <div style="display:flex;justify-content:space-between;align-items:center">
    <h2 style="font-size:22px">All Assignments</h2>
    <a class="btn" href="assignments.php?action=add">Add Assignment</a>
  </div>

  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Title</th>
          <th>Grade</th>
          <th>Teacher</th>
          <th>Created</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php if ($rows): foreach ($rows as $r): ?>
        <tr>
          <td><?=$r['id']?></td>
          <td><?=htmlspecialchars($r['title'])?></td>
          <td><?=$r['grade_level']?></td>
          <td><?=htmlspecialchars($r['teacher_name'])?></td>
          <td><?=$r['created_at']?></td>
          <td>
            <a href="assignments.php?action=edit&id=<?=$r['id']?>">Edit</a> |
            <a href="assignments.php?action=delete&id=<?=$r['id']?>"
               onclick="return confirm('Delete this assignment?')">
               Delete
            </a>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="6" style="text-align:center;padding:12px">No assignments found.</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>
</div>

<?php
/* ===========================================================
   MODE: ADD OR EDIT
=========================================================== */
elseif ($action === 'add' || ($action === 'edit' && $id)):

    $row = ['title'=>'','description'=>'','grade_level'=>$current['grade_level'] ?? 3];

    if ($action === 'edit') {
        $stmt = $pdo->prepare("SELECT * FROM assignments WHERE id=:id");
        $stmt->execute([':id'=>$id]);
        $row = $stmt->fetch() ?: $row;
    }
?>
<div class="wrapper">
<div class="form-card">

  <h2 style="text-align:center;margin-bottom:14px">
    <?=$action === 'add' ? 'Add Assignment' : 'Edit Assignment'?>
  </h2>

  <form method="post">

    <label>Title</label>
    <input name="title" value="<?=htmlspecialchars($row['title'])?>" required>

    <label>Description</label>
    <textarea name="description"><?=htmlspecialchars($row['description'])?></textarea>

    <label>Grade Level</label>
    <input type="number" name="grade_level" min="1" max="10" 
           value="<?=$row['grade_level']?>">

    <button type="submit">
      <?=$action === 'add' ? 'Create Assignment' : 'Update Assignment'?>
    </button>

  </form>

</div>
</div>

<?php
/* ===========================================================
   MODE: DELETE CONFIRMATION
=========================================================== */
elseif ($action === 'delete' && $id):
?>
<div class="wrapper">
<div class="form-card" style="max-width:480px">

  <h2 style="margin-bottom:12px;text-align:center">
    Delete Assignment #<?=$id?>
  </h2>

  <p style="text-align:center;margin-bottom:20px">
    Are you sure you want to delete this assignment?
  </p>

  <form method="post">
    <button type="submit">Delete</button>
    <a href="assignments.php" class="btn" 
       style="display:inline-block;margin-top:12px;text-align:center">Cancel</a>
  </form>

</div>
</div>

<?php endif; ?>
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
