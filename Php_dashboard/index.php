<?php
// login.php - fully restyled center box only
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
<html lang="en">
<head>
<meta charset="utf-8">
<title>Login</title>
<meta name="viewport" content="width=device-width,initial-scale=1">

<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Poppins",Arial,sans-serif}
body{
    min-height:100vh;
    background:#f4f6f8;
    display:flex;
    flex-direction:column;
}

/* NAVBAR stays the same (your theme) */
.navbar{
    display:flex;
    align-items:center;
    gap:12px;
    padding:14px 20px;
    background:linear-gradient(135deg,#4f46e5,#9333ea);
    color:#fff;
    box-shadow:0 3px 12px rgba(0,0,0,.12);
}
.navbar img{width:34px;height:34px;border-radius:6px}
.navbar h1{font-size:18px;font-weight:600;letter-spacing:0.6px}

/* CENTER AREA — Facebook styling */
.container{
    flex:1;
    display:flex;
    justify-content:center;
    align-items:flex-start;
    padding-top:60px;
}

/* FACEBOOK STYLE LOGIN BOX */
.card{
    background:#fff;
    width:100%;
    max-width:380px;
    padding:28px 24px;
    border-radius:10px;
    box-shadow:0 2px 10px rgba(0,0,0,0.15);
    text-align:center;
}

/* Title like Facebook */
.login-title{
    font-size:20px;
    font-weight:600;
    color:#1c1e21;
    margin-bottom:20px;
}

/* Inputs identical spacing as FB */
input{
    width:100%;
    padding:14px;
    font-size:15px;
    margin-bottom:12px;
    border:1px solid #ccd0d5;
    border-radius:6px;
}
input:focus{
    border-color:#4f46e5;
    box-shadow:0 0 0 2px rgba(79,70,229,0.2);
    outline:none;
}

/* Button same shape as FB but purple */
button{
    width:100%;
    padding:14px;
    font-size:17px;
    font-weight:600;
    color:#fff;
    border:none;
    border-radius:6px;
    background:linear-gradient(135deg,#4f46e5,#9333ea);
    cursor:pointer;
}
button:hover{
    opacity:.9;
}

/* error */
.error{
    background:#ffecec;
    color:#c00;
    padding:10px;
    border-radius:6px;
    margin-bottom:12px;
    font-size:14px;
}

/* tiny bottom text */
.small{
    margin-top:15px;
    font-size:13px;
    color:#555;
}
</style>
</head>

<body>

<header class="navbar">
  <img src="favicon-32x32.png" alt="logo">
  <h1>MathMaze Panel</h1>
</header>

<main class="container">

  <section class="card">

    <div class="login-title">Log in to MathMaze</div>

    <?php if($err): ?>
      <div class="error"><?=htmlspecialchars($err)?></div>
    <?php endif; ?>

    <form method="post">
      <input name="username" placeholder="Email or username" required autofocus>
      <input type="password" name="password" placeholder="Password" required>

      <button type="submit">Log In</button>
    </form>

    <div class="small">First time? Ask an admin to create your account.</div>

  </section>

</main>

</body>
</html>
