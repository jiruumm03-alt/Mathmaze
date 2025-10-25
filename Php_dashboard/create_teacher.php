<?php
// create_teacher.php - run once to create a teacher account for a specific grade
// Usage: place in public folder, open in browser, fill form.
require 'db.php';
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = $_POST['username'] ?? '';
  $password = $_POST['password'] ?? '';
  $fullname = $_POST['fullname'] ?? '';
  $grade = intval($_POST['grade'] ?? 0);
  if ($grade < 3 || $grade > 6) $msg = 'Invalid grade';
  else {
    $table = "grade" . $grade . "_teachers";
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO $table (username, password, full_name) VALUES (?, ?, ?)") ;
    $stmt->bind_param('sss', $username, $hash, $fullname);
    if ($stmt->execute()) $msg = 'Teacher created';
    else $msg = 'Error: ' . $conn->error;
  }
}
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Create Teacher</title></head><body>
<h2>Create teacher account (run once)</h2>
<?php if($msg) echo '<p>'.htmlspecialchars($msg).'</p>'; ?>
<form method="post">
  <input name="fullname" placeholder="Full name" required><br>
  <input name="username" placeholder="Username" required><br>
  <input name="password" type="password" placeholder="Password" required><br>
  <select name="grade">
    <option value="3">Grade 3</option>
    <option value="4">Grade 4</option>
    <option value="5">Grade 5</option>
    <option value="6">Grade 6</option>
  </select><br>
  <button type="submit">Create</button>
</form>
</body></html>
