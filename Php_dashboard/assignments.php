<?php
// assignments.php
require_once __DIR__ . '/auth.php';
require_login();
$pdo = pdo();

$action = $_REQUEST['action'] ?? 'list';
$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : 0;
$current = current_user();
$isAdmin = is_admin();

// Handle create / update / delete (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!is_teacher() && !$isAdmin) { die("Access denied."); }
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $grade = intval($_POST['grade_level'] ?? 0);
    $tid = $current['id'];
    if ($action === 'add') {
        $stmt = $pdo->prepare("INSERT INTO assignments (title,description,grade_level,teacher_id) VALUES (:t,:d,:g,:tid)");
        $stmt->execute([':t'=>$title,':d'=>$desc,':g'=>$grade,':tid'=>$tid]);
        header("Location: assignments.php");
        exit;
    } elseif ($action === 'edit' && $id) {
        $stmt = $pdo->prepare("UPDATE assignments SET title=:t, description=:d, grade_level=:g WHERE id=:id");
        $stmt->execute([':t'=>$title,':d'=>$desc,':g'=>$grade,':id'=>$id]);
        header("Location: assignments.php");
        exit;
    } elseif ($action === 'delete' && $id) {
        $stmt = $pdo->prepare("DELETE FROM assignments WHERE id=:id");
        $stmt->execute([':id'=>$id]);
        header("Location: assignments.php");
        exit;
    }
}

// Render pages
if ($action === 'list') {
    $stmt = $pdo->query("SELECT a.*, t.full_name as teacher_name FROM assignments a LEFT JOIN teachers t ON a.teacher_id = t.id ORDER BY a.created_at DESC");
    $rows = $stmt->fetchAll();
    ?>
    <!doctype html><html><head><meta charset="utf-8"><title>Assignments</title></head><body>
      <h1>Assignments</h1>
      <p><a href="dashboard_admin.php">Admin</a> | <a href="dashboard_teacher.php">Dashboard</a> | <a href="assignments.php?action=add">Add Assignment</a> | <a href="logout.php">Logout</a></p>
      <table border="1" cellpadding="6"><tr><th>ID</th><th>Title</th><th>Grade</th><th>Teacher</th><th>Created</th><th>Actions</th></tr>
        <?php foreach($rows as $r): ?>
          <tr>
            <td><?=intval($r['id'])?></td>
            <td><?=htmlspecialchars($r['title'])?></td>
            <td><?=intval($r['grade_level'])?></td>
            <td><?=htmlspecialchars($r['teacher_name'])?></td>
            <td><?=htmlspecialchars($r['created_at'])?></td>
            <td>
              <a href="assignments.php?action=edit&id=<?=$r['id']?>">Edit</a> |
              <a href="assignments.php?action=delete&id=<?=$r['id']?>" onclick="return confirm('Delete?')">Delete</a>
            </td>
          </tr>
        <?php endforeach;?>
      </table>
    </body></html>
    <?php
    exit;
} elseif ($action === 'add' || ($action === 'edit' && $id)) {
    $row = ['title'=>'','description'=>'','grade_level'=>($current['grade_level'] ?? 3)];
    if ($action === 'edit' && $id) {
        $stmt = $pdo->prepare("SELECT * FROM assignments WHERE id = :id");
        $stmt->execute([':id'=>$id]);
        $row = $stmt->fetch() ?: $row;
    }
    ?>
    <!doctype html><html><meta charset="utf-8"><body>
      <h1><?=($action==='add'?'Add':'Edit')?> Assignment</h1>
      <form method="post">
        <label>Title<br><input name="title" value="<?=htmlspecialchars($row['title'])?>" required></label><br><br>
        <label>Description<br><textarea name="description"><?=htmlspecialchars($row['description'])?></textarea></label><br><br>
        <label>Grade Level<br><input type="number" name="grade_level" value="<?=intval($row['grade_level'])?>" min="1"></label><br><br>
        <button><?=($action==='add'?'Create':'Update')?></button>
      </form>
    </body></html>
    <?php
    exit;
} elseif ($action === 'delete' && $id) {
    // confirm and delete handled in POST above; show confirmation
    ?>
    <!doctype html><html><body>
      <h1>Delete Assignment <?=$id?></h1>
      <form method="post">
        <p>Are you sure?</p>
        <button type="submit">Delete</button>
        <a href="assignments.php">Cancel</a>
      </form>
    </body></html>
    <?php
    exit;
}
