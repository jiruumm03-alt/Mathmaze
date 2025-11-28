<?php

session_start();
require_once "db.php";

// Redirect if already logged in
if (isset($_SESSION['username']) && isset($_SESSION['grade'])) {
    header("Location: dashboard.php");
    exit();
}

$error = "";


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);
    $grade = $_POST['grade'];

    // 1) Check super admin
    $sql = "SELECT * FROM super_admins WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $adminResult = $stmt->get_result();

    if ($adminResult && $admin = $adminResult->fetch_assoc()) {
        if (password_verify($password, $admin['password'])) {
            $_SESSION['username'] = $username;
            $_SESSION['role'] = "super_admin";
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Incorrect password.";
        }
    }

    // 2) If not admin, check teacher login for selected grade
    $table = "grade{$grade}_teachers";
    $sql = "SELECT * FROM $table WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $teacherResult = $stmt->get_result();

    if ($teacherResult && $row = $teacherResult->fetch_assoc()) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['username'] = $username;
            $_SESSION['grade'] = $grade;
            $_SESSION['role'] = "teacher";
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Incorrect password.";
        }
    } else {
        if (empty($error)) $error = "No teacher found for that grade.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MathMaze Login</title>
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
    max-width: 400px;
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
.error {
    color: red;
    text-align: center;
    margin-bottom: 10px;
    font-size: 15px;
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
        <h1>MathMaze Login</h1>
    </div>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <label>Username</label>
        <input type="text" name="username" placeholder="Enter username" required>

        <label>Password</label>
        <input type="password" name="password" placeholder="Enter password" required>

        <label>Grade Level</label>
        <select name="grade" required>
            <option value="">Select Grade</option>
            <option value="3">Grade 3</option>
            <option value="4">Grade 4</option>
            <option value="5">Grade 5</option>
            <option value="6">Grade 6</option>
        </select>

        <button type="submit">Login</button>
    </form>

    <div class="link">
        <p>New teacher? <a href="create_teacher.php">Create an account</a></p>
    </div>
</div>

</body>
</html>