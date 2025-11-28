<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'db.php';
$msg = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $grade = intval($_POST['grade'] ?? 0);

    if ($grade < 3 || $grade > 6) {
        $msg = 'Invalid grade selected.';
    } elseif (!$fullname || !$username || !$password) {
        $msg = 'All fields are required.';
    } else {
        $table = "mathmaze_db.grade" . $grade . "_teachers";

        // Check if username already exists
        $check = $conn->prepare("SELECT id FROM $table WHERE username = ?");
        $check->bind_param('s', $username);
        $check->execute();
        $checkRes = $check->get_result();

        if ($checkRes->num_rows > 0) {
            $msg = 'That username already exists for this grade.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO $table (username, password, full_name) VALUES (?, ?, ?)");
            $stmt->bind_param('sss', $username, $hash, $fullname);

            if ($stmt->execute()) {
                $success = true;
                $msg = 'Account created successfully! Redirecting to login...';
                header("refresh:3;url=index.php");
            } else {
                $msg = 'Error: ' . htmlspecialchars($conn->error);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MathMaze - Create Account</title>
<link rel="icon" type="image/png" href="favicon-32x32.png">
<style>
body {
    font-family: 'Segoe UI', Arial, sans-serif;
    margin: 0;
    background: linear-gradient(135deg, #5A60FF, #B14EFF);
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    color: #333;
}
.container {
    background: white;
    border-radius: 16px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    padding: 30px 40px;
    max-width: 420px;
    width: 90%;
}
.header {
    text-align: center;
    margin-bottom: 20px;
}
.header img {
    height: 48px;
    margin-bottom: 8px;
}
.header h1 {
    margin: 0;
    color: #5A60FF;
}
form {
    display: flex;
    flex-direction: column;
}
input, select {
    padding: 12px;
    border: 1px solid #ccc;
    border-radius: 8px;
    margin-bottom: 14px;
    font-size: 16px;
}
button {
    background: linear-gradient(90deg, #5A60FF, #B14EFF);
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
}
button:hover {
    opacity: 0.9;
}
.message {
    text-align: center;
    margin-bottom: 10px;
    font-weight: bold;
    color: <?= $success ? "'green'" : "'red'" ?>;
}
.link {
    text-align: center;
    margin-top: 10px;
}
.link a {
    color: #5A60FF;
    text-decoration: none;
}
.link a:hover {
    text-decoration: underline;
}

/* Mobile adjustments */
@media (max-width: 500px) {
    .container {
        padding: 25px 20px;
    }
    input, select, button {
        font-size: 15px;
        padding: 10px;
    }
    .header h1 {
        font-size: 22px;
    }
}
</style>
</head>
<body>

<div class="container">
    <div class="header">
        <img src="favicon-32x32.png" alt="MathMaze Logo">
        <h1>Create Account</h1>
    </div>

    <?php if($msg): ?>
        <div class="message"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <form method="post">
        <label>Full Name</label>
        <input name="fullname" placeholder="Enter full name" required>

        <label>Username</label>
        <input name="username" placeholder="Enter username" required>

        <label>Password</label>
        <input name="password" type="password" placeholder="Enter password" required>

        <label>Grade Level</label>
        <select name="grade" required>
            <option value="">Select grade</option>
            <option value="3">Grade 3</option>
            <option value="4">Grade 4</option>
            <option value="5">Grade 5</option>
            <option value="6">Grade 6</option>
        </select>

        <button type="submit">Create Account</button>
    </form>

    <div class="link">
        <p>Already have an account? <a href="index.php">Login here</a></p>
    </div>
</div>

</body>
</html>