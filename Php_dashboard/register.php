<?php
// register.php (create teacher) — only super_admin can access
require_once __DIR__ . '/auth.php';
require_login();
if (!is_admin()) {
    die("Access denied.");
}

$err = $msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $grade = intval($_POST['grade_level'] ?? 0);

    if ($username === '' || $password === '' || $full_name === '' || $grade <= 0) {
        $err = "All fields required and grade must be > 0.";
    } else {
        // insert teacher
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = pdo()->prepare("INSERT INTO teachers (username,password,full_name,grade_level,created_at) VALUES (:u,:p,:n,:g,NOW())");
        try {
            $stmt->execute([':u'=>$username,':p'=>$hash,':n'=>$full_name,':g'=>$grade]);
            $msg = "Teacher created.";
        } catch (PDOException $e) {
            $err = "DB error: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Create Teacher</title></head>
<body>
  <h2>Create Teacher (Admin)</h2>
  <p><a href="dashboard_admin.php">Back to Admin Dashboard</a> | <a href="logout.php">Logout</a></p>
  <?php if($err): ?><div style="color:red"><?=$err?></div><?php endif; ?>
  <?php if($msg): ?><div style="color:green"><?=$msg?></div><?php endif; ?>
  <form method="post">
    <label>Username<br><input name="username"></label><br><br>
    <label>Password<br><input name="password" type="password"></label><br><br>
    <label>Full name<br><input name="full_name"></label><br><br>
    <label>Grade level<br><input name="grade_level" type="number" min="1"></label><br><br>
    <button>Create Teacher</button>
  </form>
</body>
</html>
