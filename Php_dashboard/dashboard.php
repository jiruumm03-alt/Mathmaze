<?php
// dashboard.php - shows progress for the logged-in teacher's grade
session_start();
if (!isset($_SESSION['username'])) {
  header('Location: index.php');
  exit();
}

require 'db.php';

$teacher = htmlspecialchars($_SESSION['username']);
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'teacher';

if ($role == "teacher") {
    $grade = intval($_SESSION['grade']);
} else {
    $grade = null; // super admin does not have a fixed grade
}
 // <-- match session variable name
$studentTable = "grade" . $grade . "_students";
$progressTable = "grade" . $grade . "_progress";

$result = null;
if ($role == "teacher") {
    $studentTable = "grade" . $grade . "_students";
    $progressTable = "grade" . $grade . "_progress";

    $sql = "SELECT s.full_name, s.username, p.level, p.score, p.time_spent, p.date_updated 
        FROM $progressTable p 
        JOIN $studentTable s ON p.student_id = s.id
        ORDER BY p.date_updated DESC LIMIT 500";
    $result = $conn->query($sql);
} else {
    // For super admin, show students list with assignment info
    $sql = "SELECT id, full_name, username, grade_level, teacher_id, teacher_grade FROM students ORDER BY grade_level, full_name";
    $result = $conn->query($sql);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MathMaze Teacher Dashboard - Grade <?= $grade ?></title>
  <link rel="icon" type="image/png" href="favicon-32x32.png">
  <style>
    /* Global */
    body {
      margin: 0;
      font-family: "Segoe UI", Arial, sans-serif;
      background-color: #f4f6f8;
      color: #333;
      overflow-x: hidden;
    }

    /* Header */
    header {
      background: linear-gradient(90deg, #4f46e5, #9333ea);
      color: white;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 12px 24px; /* increased right padding */
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      width: calc(100% - 0px);
      z-index: 1000;
      box-shadow: 0 2px 6px rgba(0,0,0,0.2);
      gap: 16px;
      box-sizing: border-box;
    }

    .header-left {
      display: flex;
      align-items: center;
    }
    .header-left img {
      height: 32px;
      margin-right: 10px;
    }
    .header-left h1 {
      font-size: 20px;
      margin: 0;
    }

    .menu-btn {
      font-size: 26px;
      cursor: pointer;
      color: white;
      margin-right: 12px;
      display: block;
    }

    /* Sidebar */
    .sidebar {
      position: fixed;
      top: 60px;
      left: 0;
      height: 100%;
      width: 220px;
      background-color: #fff;
      box-shadow: 2px 0 6px rgba(0,0,0,0.1);
      padding-top: 20px;
      transition: transform 0.3s ease;
    }

    .sidebar.hide {
      transform: translateX(-220%);
      transition: transform 0.3s;
    }

    .sidebar a {
      display: block;
      padding: 12px 20px;
      color: #333;
      text-decoration: none;
      font-weight: 500;
    }
    .sidebar a:hover,
    .sidebar a.active {
      background: linear-gradient(90deg, #4f46e5, #9333ea);
      color: white;
    }

    /* Main Content */
    .content {
      margin-left: 240px;
      transition: margin-left 0.3s ease;
    }

    @media (max-width: 768px) {
        .sidebar {
            position: fixed;
            top: 60px;
            left: 0;
            width: 220px;
            height: 100%;
            transition: translateX(-100%);
            z-index: 999;
        }
    }
    

    h2 {
      color: #333;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
      background: white;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    th, td {
      padding: 12px;
      border-bottom: 1px solid #ddd;
      text-align: center;
    }

    th {
      background: linear-gradient(90deg, #4f46e5, #9333ea);
      color: white;
    }

    tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    tr:hover {
      background-color: #eef3ff;
    }

    .logout {
      color: #e11d48;
      font-weight: bold;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .menu-btn {
        display: block;
      }
      .sidebar {
        transition: transform 0.3s ease;
      }
      .sidebar.show {
        transform: translateX(0);
      }
      .content {
        margin-left: 0;
        padding-top: 90px;
      }
    }

    /* Buttons */
    button {
      background: linear-gradient(90deg, #4f46e5, #9333ea);
      color: white;
      border: none;
      padding: 10px 16px;
      border-radius: 6px;
      cursor: pointer;
      margin-bottom: 15px;
    }
    button:hover {
      opacity: 0.9;
    }
  </style>
</head>
<body>

  <!-- Header -->
  <header>
    <div class="header-left">
      <span class="menu-btn" onclick="toggleSidebar()">☰</span>
       <img src="favicon-32x32.png" alt="Logo">
      <h1>MathMaze Dashboard</h1>
    </div>
    <div class="header-right">
      <span>Welcome, <strong><?= $teacher ?></strong> (<?= $role == 'super_admin' ? 'Super Admin' : "Grade $grade" ?>)</span>
    </div>
  </header>

  <!-- Sidebar -->
  <nav class="sidebar" id="sidebar">
<?php if ($role == "teacher"): ?>
    <a href="#" class="active" onclick="showTab('overview')">Overview</a>
    <a href="#" onclick="showTab('students')">Students</a>
    <a href="#" onclick="showTab('levels')">Game Levels</a>
    <a href="#" onclick="showTab('reports')">Reports</a>
<?php endif; ?>

<?php if ($role == "super_admin"): ?>
    <a href="manage_teachers.php">Manage Teachers</a>
    <a href="manage_students.php">Manage Students</a>
    <a href="admin_reports.php">System Reports</a>
<?php endif; ?>

    <a href="#" class="logout" onclick="confirmLogout(event)">Logout</a>
</nav>

  <!-- Main Content -->
  <main class="content" id="content">
    <section id="overview" class="tab-content">
      <h2>Overview</h2>
      <p>Welcome back, <strong><?= $teacher ?></strong>! Here you can monitor student progress, view reports, and track performance for Grade <?= $grade ?>.</p>
      <button onclick="window.print()">🖨️ Print Student Report</button>
    </section>

    <section id="students" class="tab-content" style="display:none;">
      <h2>Student Progress</h2>
      <table>
        <tr>
          <th>Student</th>
          <th>Level</th>
          <th>Score</th>
          <th>Time</th>
          <th>Date</th>
        </tr>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while($row = $result->fetch_assoc()): ?>
            <tr>
              <td><?= htmlspecialchars($row['full_name'] ?: $row['username']) ?></td>
              <td><?= htmlspecialchars($row['level']) ?></td>
              <td><?= htmlspecialchars($row['score']) ?></td>
              <td><?= htmlspecialchars($row['time_spent']) ?></td>
              <td><?= htmlspecialchars($row['date_updated']) ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="5">No progress data found for your students.</td></tr>
        <?php endif; ?>
      </table>
    </section>

    <section id="levels" class="tab-content" style="display:none;">
      <h2>Game Levels</h2>
      <p>Coming soon: view and manage MathMaze game levels and challenges.</p>
    </section>

    <section id="reports" class="tab-content" style="display:none;">
      <h2>Reports</h2>
      <p>View reports on student performance, trends, and statistics.</p>
    </section>
  </main>

  <script>
  const sidebar = document.getElementById('sidebar');
  const content = document.getElementById('content');
  const tabs = document.querySelectorAll('.tab-content');

  function toggleSidebar() {
    if (window.innerWidth <= 768) {
        sidebar.classList.toggle('show');
    } else {
        sidebar.classList.toggle('hide');
    }
  }

  function showTab(tabId) {
    tabs.forEach(tab => tab.style.display = 'none');
    document.getElementById(tabId).style.display = 'block';
    document.querySelectorAll('.sidebar a').forEach(link => link.classList.remove('active'));
    event.target.classList.add('active');
    if (window.innerWidth <= 768) sidebar.classList.remove('show');
  }

  // 🧩 Logout confirmation popup
  function confirmLogout(event) {
    event.preventDefault();

    // Create a simple modal
    const overlay = document.createElement('div');
    overlay.style.position = 'fixed';
    overlay.style.top = '0';
    overlay.style.left = '0';
    overlay.style.width = '100%';
    overlay.style.height = '100%';
    overlay.style.backgroundColor = 'rgba(0,0,0,0.4)';
    overlay.style.display = 'flex';
    overlay.style.alignItems = 'center';
    overlay.style.justifyContent = 'center';
    overlay.style.zIndex = '2000';

    const box = document.createElement('div');
    box.style.background = 'white';
    box.style.padding = '20px 30px';
    box.style.borderRadius = '10px';
    box.style.boxShadow = '0 2px 10px rgba(0,0,0,0.2)';
    box.style.textAlign = 'center';
    box.innerHTML = `
      <h3>Are you sure you want to log out?</h3>
      <div style="margin-top:15px;">
        <button id="yesLogout" style="background:#ef4444;color:white;border:none;padding:8px 14px;border-radius:6px;cursor:pointer;">Yes</button>
        <button id="cancelLogout" style="margin-left:10px;background:#e5e7eb;color:#333;border:none;padding:8px 14px;border-radius:6px;cursor:pointer;">Cancel</button>
      </div>
    `;

    overlay.appendChild(box);
    document.body.appendChild(overlay);

    // Handle buttons
    document.getElementById('yesLogout').onclick = () => {
      window.location.href = 'logout.php';
    };

    document.getElementById('cancelLogout').onclick = () => {
      overlay.remove();
    };
  }
</script>

</body>
</html>