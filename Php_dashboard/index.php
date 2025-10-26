<?php
// login.php - teacher login form
session_start();
require 'db.php';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'] ?? '';
  $password = $_POST['password'] ?? '';
  $grade = intval($_POST['grade'] ?? 0);
  $table = "mathmaze_db.grade" . $grade . "_teachers";

  if ($grade < 3 || $grade > 6) {
    $msg = 'Invalid grade selected.';
  } else {
    $stmt = $conn->prepare("SELECT * FROM $table WHERE username = ?");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows === 1) {
      $teacher = $res->fetch_assoc();
      if (password_verify($password, $teacher['password'])) {
        $_SESSION['teacher'] = $teacher['username'];
        $_SESSION['grade'] = $grade;
        header('Location: dashboard.php');
        exit();
      } else {
        $msg = 'Invalid password.';
      }
    } else {
      $msg = 'No teacher account found.';
    }
  }
}
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Teacher Login - MathMaze</title>
<link rel="stylesheet" href="styles.css"></head><body>
<div class="box">
  <h2>Teacher Login</h2>
  <?php if($msg) echo '<p class="error">'.htmlspecialchars($msg).'</p>'; ?>
  <form method="post">
    <input name="username" placeholder="Username" required><br>
    <input name="password" type="password" placeholder="Password" required><br>
    <select name="grade" required>
      <option value="">Select grade</option>
      <option value="3">Grade 3</option>
      <option value="4">Grade 4</option>
      <option value="5">Grade 5</option>
      <option value="6">Grade 6</option>
    </select><br>
    <button type="submit">Login</button>
  </form>
</div>
</body></html>
