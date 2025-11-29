<?php
// register.php — Create Teacher (Admin only), fully restyled inline CSS
require_once __DIR__ . '/auth.php';
require_login();
if (!is_admin()) die("Access denied.");

$err = $msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $grade = intval($_POST['grade_level'] ?? 0);

    if ($username === '' || $password === '' || $full_name === '' || $grade <= 0) {
        $err = "All fields are required and grade must be > 0.";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = pdo()->prepare("INSERT INTO teachers (username,password,full_name,grade_level,created_at) 
                                VALUES (:u,:p,:n,:g,NOW())");
        try {
            $stmt->execute([':u'=>$username,':p'=>$hash,':n'=>$full_name,':g'=>$grade]);
            $msg = "Teacher created successfully.";
        } catch (PDOException $e) {
            $err = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Create Teacher — MathMaze</title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<style>
/* GLOBAL */
*{margin:0;padding:0;box-sizing:border-box;font-family:"Poppins",Arial,sans-serif}
body{background:#f4f6f8;color:#222;min-height:100vh;display:flex;flex-direction:column}

/* NAVBAR */
.navbar{
  display:flex;align-items:center;gap:12px;
  background:linear-gradient(135deg,#4f46e5,#9333ea);
  padding:16px 25px;color:#fff;
  box-shadow:0 3px 12px rgba(0,0,0,.15);
}
.navbar img{width:36px;height:36px}
.navbar h1{font-size:20px;font-weight:600}
.nav-right{margin-left:auto;display:flex;align-items:center;gap:12px}

/* BUTTONS */
.btn{
  padding:8px 14px;border-radius:8px;
  background:linear-gradient(135deg,#4f46e5,#9333ea);
  color:#fff;text-decoration:none;font-size:14px;
}
.btn:hover{opacity:.9}

/* MAIN CARD */
.container{flex:1;display:flex;justify-content:center;align-items:center;padding:30px}
.card{
  background:white;padding:32px;width:100%;max-width:480px;
  border-radius:14px;box-shadow:0 8px 26px rgba(0,0,0,.06);
  animation:fadeIn .6s ease;
}

/* FORM */
label{display:block;margin-top:14px;font-weight:500;font-size:14px}
input{
  width:100%;padding:10px;margin-top:6px;border-radius:8px;
  border:1px solid #d5ddec;font-size:15px;
}
input:focus{outline:none;border-color:#7c3aed;box-shadow:0 0 0 4px rgba(124,58,237,0.1)}

button{
  width:100%;padding:12px;margin-top:20px;
  border:none;border-radius:8px;
  background:linear-gradient(135deg,#4f46e5,#9333ea);
  color:#fff;font-size:16px;cursor:pointer;
}
button:hover{opacity:.9}

/* ALERTS */
.error{
  color:#b91c1c;background:#fff5f5;border-radius:8px;
  padding:10px;text-align:center;margin-bottom:8px
}
.success{
  color:#0f5132;background:#d1e7dd;border-radius:8px;
  padding:10px;text-align:center;margin-bottom:8px
}

/* ANIMATION */
@keyframes fadeIn{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}
</style>
</head>

<body>

<header class="navbar">
  <img src="favicon-32x32.png" alt="">
  <h1>Create Teacher</h1>

  <div class="nav-right">
    <a class="btn" href="dashboard_admin.php">Back</a>
    <a class="btn" href="#" onclick="openLogoutModal()">Logout</a>
  </div>
</header>

<div class="container">
  <div class="card">

    <h2 style="text-align:center;margin-bottom:10px">New Teacher Account</h2>

    <?php if($err): ?>
      <div class="error"><?=htmlspecialchars($err)?></div>
    <?php endif; ?>

    <?php if($msg): ?>
      <div class="success"><?=htmlspecialchars($msg)?></div>
    <?php endif; ?>

    <form method="post">
      
      <label>Username</label>
      <input name="username" placeholder="teacher_username123" required>

      <label>Password</label>
      <input name="password" type="password" placeholder="Enter a secure password" required>

      <label>Full Name</label>
      <input name="full_name" placeholder="e.g. Maria Santos" required>

      <label>Grade Level</label>
      <input name="grade_level" type="number" min="1" max="10" placeholder="e.g. 5" required>

      <button type="submit">Create Teacher</button>
    </form>

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
