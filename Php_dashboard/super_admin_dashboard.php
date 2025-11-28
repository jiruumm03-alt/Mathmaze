<?php
// super_admin_dashboard.php
session_start();
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'super_admin') {
  header('Location: index.php');
  exit();
}

require 'db.php';

$admin = htmlspecialchars($_SESSION['username']);

// Handle Add
if (isset($_POST['add_type'])) {
    $type = $_POST['add_type'];
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $username = $conn->real_escape_string($_POST['username']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $grade = intval($_POST['grade_level']);
    $table = ($type === 'teacher') ? 'teachers' : 'students';
    $conn->query("INSERT INTO $table (full_name, username, password, grade_level) VALUES ('$full_name', '$username', '$password', $grade)");
}

// Handle Edit
if (isset($_POST['edit_type'])) {
    $type = $_POST['edit_type'];
    $id = intval($_POST['edit_id']);
    $full_name = $conn->real_escape_string($_POST['full_name']);
    $username = $conn->real_escape_string($_POST['username']);
    $grade = intval($_POST['grade_level']);
    $table = ($type === 'teacher') ? 'teachers' : 'students';
    $conn->query("UPDATE $table SET full_name='$full_name', username='$username', grade_level=$grade WHERE id=$id");
}

// Handle Delete
if (isset($_POST['delete_type']) && isset($_POST['delete_id'])) {
    $type = $_POST['delete_type'];
    $id = intval($_POST['delete_id']);
    $table = ($type === 'teacher') ? 'teachers' : 'students';
    $conn->query("DELETE FROM $table WHERE id = $id");
}

// Stats
$teacherCount = $conn->query("SELECT COUNT(*) AS total FROM teachers")->fetch_assoc()['total'];
$studentCount = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total'];

// Lists
$teachers = $conn->query("SELECT * FROM teachers ORDER BY id DESC");
$students = $conn->query("SELECT * FROM students ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MathMaze Super Admin Dashboard</title>
  <link rel="icon" type="image/png" href="favicon-32x32.png">
  <style>
    body { margin:0; font-family:"Segoe UI",Arial,sans-serif; background:#f4f6f8; color:#333; }
    header {
      background:linear-gradient(90deg,#4f46e5,#9333ea);
      color:white; display:flex; justify-content:space-between; align-items:center;
      padding:12px 24px; position:fixed; top:0; left:0; right:0;
      box-shadow:0 2px 6px rgba(0,0,0,0.2); z-index:1000;
    }
    .header-left{display:flex;align-items:center;gap:10px;}
    .menu-btn{font-size:26px;cursor:pointer;}
    .sidebar{
      position:fixed; top:60px; left:0; width:220px; height:100%;
      background:white; box-shadow:2px 0 6px rgba(0,0,0,0.1);
      padding-top:20px; transition:transform 0.3s;
    }
    .sidebar.hide{transform:translateX(-220px);}
    .sidebar a{
      display:block;padding:12px 20px;text-decoration:none;
      color:#333;font-weight:500;
    }
    .sidebar a:hover,.sidebar a.active{
      background:linear-gradient(90deg,#4f46e5,#9333ea);color:white;
    }
    .content{margin-left:240px;padding:80px 20px;transition:margin-left 0.3s;}
    table{width:100%;border-collapse:collapse;background:white;border-radius:8px;
      overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);}
    th,td{padding:10px;text-align:center;border-bottom:1px solid #ddd;}
    th{background:linear-gradient(90deg,#4f46e5,#9333ea);color:white;}
    tr:nth-child(even){background:#f9f9f9;}
    .card{background:white;padding:20px;border-radius:10px;box-shadow:0 2px 6px rgba(0,0,0,0.1);margin-bottom:20px;text-align:center;}
    .card h2{margin:10px 0 5px;}
    .logout{color:#e11d48;font-weight:bold;}
    button{
      background:linear-gradient(90deg,#4f46e5,#9333ea);
      color:white;border:none;padding:8px 14px;border-radius:6px;cursor:pointer;
    }
    button:hover{opacity:0.9;}
    .edit-btn{background:#10b981;}
    .delete-btn{background:#ef4444;}
    .add-btn{background:#3b82f6;margin-bottom:10px;}
    @media(max-width:768px){
      .sidebar{transform:translateX(-100%);}
      .sidebar.show{transform:translateX(0);}
      .content{margin-left:0;padding-top:90px;}
    }
    .modal-overlay{
      position:fixed;top:0;left:0;width:100%;height:100%;
      background:rgba(0,0,0,0.4);display:flex;align-items:center;justify-content:center;
      z-index:2000;
    }
    .modal-box{
      background:white;padding:20px 30px;border-radius:10px;box-shadow:0 2px 10px rgba(0,0,0,0.2);
      width:90%;max-width:400px;text-align:center;
    }
    .modal-box input,select{
      width:100%;padding:8px;margin:6px 0;border:1px solid #ccc;border-radius:6px;
    }
  </style>
</head>
<body>
<header>
  <div class="header-left">
    <span class="menu-btn" onclick="toggleSidebar()">☰</span>
    <h1>MathMaze Admin Panel</h1>
  </div>
  <div>Welcome, <strong><?= $admin ?></strong> (Super Admin)</div>
</header>

<nav class="sidebar" id="sidebar">
  <a href="#" class="active" onclick="showTab('overview')">Overview</a>
  <a href="#" onclick="showTab('teachers')">Manage Teachers</a>
  <a href="#" onclick="showTab('students')">Manage Students</a>
  <a href="#" onclick="showTab('reports')">System Reports</a>
  <a href="#" class="logout" onclick="confirmLogout(event)">Logout</a>
</nav>

<main class="content" id="content">

  <section id="overview" class="tab-content">
    <h2>System Overview</h2>
    <div class="card"><h2><?= $teacherCount ?></h2><p>Total Teachers</p></div>
    <div class="card"><h2><?= $studentCount ?></h2><p>Total Students</p></div>
  </section>

  <section id="teachers" class="tab-content" style="display:none;">
    <h2>Manage Teachers</h2>
    <button class="add-btn" onclick="openAddModal('teacher')">➕ Add Teacher</button>
    <table>
      <tr><th>ID</th><th>Name</th><th>Username</th><th>Grade</th><th>Actions</th></tr>
      <?php while($t=$teachers->fetch_assoc()): ?>
      <tr>
        <td><?= $t['id'] ?></td>
        <td><?= htmlspecialchars($t['full_name']) ?></td>
        <td><?= htmlspecialchars($t['username']) ?></td>
        <td><?= $t['grade_level'] ?></td>
        <td>
          <button class="edit-btn" onclick="openEditModal('teacher',<?= $t['id'] ?>,'<?= htmlspecialchars($t['full_name']) ?>','<?= htmlspecialchars($t['username']) ?>',<?= $t['grade_level'] ?>)">Edit</button>
          <button class="delete-btn" onclick="openDeleteModal('teacher',<?= $t['id'] ?>)">Delete</button>
        </td>
      </tr>
      <?php endwhile; ?>
    </table>
  </section>

  <section id="students" class="tab-content" style="display:none;">
    <h2>Manage Students</h2>
    <button class="add-btn" onclick="openAddModal('student')">➕ Add Student</button>
    <table>
      <tr><th>ID</th><th>Name</th><th>Username</th><th>Grade</th><th>Actions</th></tr>
      <?php while($s=$students->fetch_assoc()): ?>
      <tr>
        <td><?= $s['id'] ?></td>
        <td><?= htmlspecialchars($s['full_name']) ?></td>
        <td><?= htmlspecialchars($s['username']) ?></td>
        <td><?= $s['grade_level'] ?></td>
        <td>
          <button class="edit-btn" onclick="openEditModal('student',<?= $s['id'] ?>,'<?= htmlspecialchars($s['full_name']) ?>','<?= htmlspecialchars($s['username']) ?>',<?= $s['grade_level'] ?>)">Edit</button>
          <button class="delete-btn" onclick="openDeleteModal('student',<?= $s['id'] ?>)">Delete</button>
        </td>
      </tr>
      <?php endwhile; ?>
    </table>
  </section>

  <section id="reports" class="tab-content" style="display:none;">
    <h2>System Reports</h2>
    <p>Coming soon: export and analytics features.</p>
  </section>

</main>

<script>
const sidebar=document.getElementById('sidebar');
const tabs=document.querySelectorAll('.tab-content');
function toggleSidebar(){
  if(window.innerWidth<=768){sidebar.classList.toggle('show');}
  else{sidebar.classList.toggle('hide');}
}
function showTab(tabId){
  tabs.forEach(t=>t.style.display='none');
  document.getElementById(tabId).style.display='block';
  document.querySelectorAll('.sidebar a').forEach(a=>a.classList.remove('active'));
  event.target.classList.add('active');
  if(window.innerWidth<=768) sidebar.classList.remove('show');
}
function confirmLogout(event){
  event.preventDefault();
  const overlay=document.createElement('div');
  overlay.className='modal-overlay';
  overlay.innerHTML=`
    <div class="modal-box">
      <h3>Log out?</h3>
      <button onclick="window.location.href='logout.php'" class="delete-btn">Yes</button>
      <button onclick="this.closest('.modal-overlay').remove()">Cancel</button>
    </div>`;
  document.body.appendChild(overlay);
}
function openDeleteModal(type,id){
  const overlay=document.createElement('div');
  overlay.className='modal-overlay';
  overlay.innerHTML=`
    <div class="modal-box">
      <h3>Delete this ${type}?</h3>
      <form method="POST">
        <input type="hidden" name="delete_type" value="${type}">
        <input type="hidden" name="delete_id" value="${id}">
        <button type="submit" class="delete-btn">Confirm</button>
        <button type="button" onclick="this.closest('.modal-overlay').remove()">Cancel</button>
      </form>
    </div>`;
  document.body.appendChild(overlay);
}
function openEditModal(type,id,name,username,grade){
  const overlay=document.createElement('div');
  overlay.className='modal-overlay';
  overlay.innerHTML=`
    <div class="modal-box">
      <h3>Edit ${type}</h3>
      <form method="POST">
        <input type="hidden" name="edit_type" value="${type}">
        <input type="hidden" name="edit_id" value="${id}">
        <label>Name</label>
        <input type="text" name="full_name" value="${name}" required>
        <label>Username</label>
        <input type="text" name="username" value="${username}" required>
        <label>Grade</label>
        <select name="grade_level">
          <option value="3" ${grade==3?'selected':''}>3</option>
          <option value="4" ${grade==4?'selected':''}>4</option>
          <option value="5" ${grade==5?'selected':''}>5</option>
          <option value="6" ${grade==6?'selected':''}>6</option>
        </select>
        <button type="submit" class="edit-btn">Save</button>
        <button type="button" onclick="this.closest('.modal-overlay').remove()">Cancel</button>
      </form>
    </div>`;
  document.body.appendChild(overlay);
}
function openAddModal(type){
  const overlay=document.createElement('div');
  overlay.className='modal-overlay';
  overlay.innerHTML=`
    <div class="modal-box">
      <h3>Add New ${type}</h3>
      <form method="POST">
        <input type="hidden" name="add_type" value="${type}">
        <label>Full Name</label>
        <input type="text" name="full_name" required>
        <label>Username</label>
        <input type="text" name="username" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <label>Grade</label>
        <select name="grade_level">
          <option value="3">3</option>
          <option value="4">4</option>
          <option value="5">5</option>
          <option value="6">6</option>
        </select>
        <button type="submit" class="add-btn">Add</button>
        <button type="button" onclick="this.closest('.modal-overlay').remove()">Cancel</button>
      </form>
    </div>`;
  document.body.appendChild(overlay);
}
</script>
</body>
</html>
