<?php
// login.php
require_once __DIR__ . '/db.php';
session_start();

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = $_POST['password'] ?? '';

    if ($u === '' || $p === '') {
        $err = "Username and password required.";
    } else {
        // Try super_admins
        $stmt = pdo()->prepare("SELECT id, username, password, full_name FROM super_admins WHERE username = :u LIMIT 1");
        $stmt->execute([':u' => $u]);
        $sa = $stmt->fetch();

        if ($sa && password_verify($p, $sa['password'])) {
            $_SESSION['user'] = [
                'id'=>$sa['id'],'username'=>$sa['username'],'name'=>$sa['full_name'],'role'=>'super_admin'
            ];
            header("Location: dashboard_admin.php");
            exit;
        }

        // Try teachers
        $stmt = pdo()->prepare("SELECT id, username, password, full_name, grade_level FROM teachers WHERE username = :u LIMIT 1");
        $stmt->execute([':u' => $u]);
        $t = $stmt->fetch();
        if ($t && password_verify($p, $t['password'])) {
            $_SESSION['user'] = [
                'id'=>$t['id'],'username'=>$t['username'],'name'=>$t['full_name'],'role'=>'teacher','grade_level'=>$t['grade_level']
            ];
            header("Location: dashboard_teacher.php");
            exit;
        }

        $err = "Invalid credentials.";
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>MathMaze — Login</title>
  <style>body{font-family:Arial;padding:28px;} .card{max-width:420px;margin:auto;border:1px solid #ddd;padding:18px;border-radius:6px}</style>
</head>
<body>
  <div class="card">
    <h2>Login — Admin / Teacher</h2>
    <?php if($err): ?><div style="color:red"><?=htmlspecialchars($err)?></div><?php endif; ?>
    <form method="post" novalidate>
      <label>Username<br><input name="username" required></label><br><br>
      <label>Password<br><input type="password" name="password" required></label><br><br>
      <button>Login</button>
    </form>
    <p style="font-size:0.9em;color:#666">If first time, create a super_admin in DB manually or run register.php while logged-in as super_admin.</p>
  </div>
</body>
</html>
