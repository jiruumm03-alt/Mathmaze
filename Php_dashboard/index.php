<?php
// login.php - fully restyled (inline CSS)
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
  /* Inline theme (purple gradient) */
  *{box-sizing:border-box;margin:0;padding:0;font-family:"Poppins",Arial,sans-serif}
  body{min-height:100vh;display:flex;flex-direction:column;background:#f4f6f8;color:#222}
  .navbar{display:flex;align-items:center;gap:12px;padding:14px 20px;background:linear-gradient(135deg,#4f46e5,#9333ea);color:#fff;box-shadow:0 3px 12px rgba(0,0,0,.12)}
  .navbar img{width:34px;height:34px;border-radius:6px}
  .navbar h1{font-size:18px;font-weight:600;letter-spacing:0.6px}
  .container{flex:1;display:flex;align-items:center;justify-content:center;padding:40px 18px}
  .card{background:#fff;width:100%;max-width:420px;padding:32px;border-radius:12px;box-shadow:0 8px 30px rgba(13,38,76,0.06);animation:fadeIn .55s ease}
  h2{font-size:20px;margin-bottom:14px;color:#111;text-align:center}
  label{display:block;font-size:13px;color:#444;margin-top:10px}
  input{width:100%;padding:10px 12px;margin-top:6px;border:1px solid #dfe7f3;border-radius:8px;font-size:15px}
  input:focus{outline:none;box-shadow:0 0 0 4px rgba(124,58,237,0.08);border-color:#7c3aed}
  button{width:100%;padding:12px;margin-top:16px;border-radius:8px;border:0;background:linear-gradient(135deg,#4f46e5,#9333ea);color:#fff;font-size:16px;cursor:pointer}
  .error{color:#b91c1c;background:#fff5f5;padding:10px;border-radius:8px;margin-bottom:10px;text-align:center}
  .small{font-size:13px;color:#666;margin-top:12px;text-align:center}
  @keyframes fadeIn{from{opacity:0;transform:translateY(10px)}to{opacity:1;transform:translateY(0)}}
  @media (max-width:480px){.card{padding:22px}}
  </style>
</head>
<body>
  <header class="navbar">
    <img src="favicon-32x32.png" alt="logo" onerror="this.style.display='none'">
    <h1>MathMaze Panel</h1>
  </header>

  <main class="container">
    <section class="card" aria-labelledby="loginTitle">
      <h2 id="loginTitle">Sign in — Admin & Teacher</h2>

      <?php if($err): ?>
        <div class="error" role="alert"><?=htmlspecialchars($err)?></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <label for="username">Username</label>
        <input id="username" name="username" placeholder="Enter username" required autofocus>

        <label for="password">Password</label>
        <input id="password" type="password" name="password" placeholder="Enter password" required>

        <button type="submit">Login</button>
      </form>

      <div class="small">First time? Create a super_admin in DB or ask existing admin to add you.</div>
    </section>
  </main>
</body>
</html>
