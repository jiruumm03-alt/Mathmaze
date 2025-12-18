<?php
// ------------------------------------------------------------
// REPORT GENERATOR — FINAL FIXED VERSION (ADMIN + TEACHER SAFE)
// ------------------------------------------------------------
ob_start();
require_once __DIR__ . "/auth.php";
require_login();

$pdo = pdo();
$current = current_user();
$is_teacher = is_teacher();
$is_admin = is_admin();
$teacher_id = $current['id'] ?? 0;

// SANITIZE INPUT
$_GET = filter_input_array(INPUT_GET, FILTER_SANITIZE_SPECIAL_CHARS) ?? [];
$type = $_GET['type'] ?? '';
$isTeacher = isset($_GET['teacher']);   // teacher dashboard passes &teacher=1

// Admins ALWAYS see all data
if ($is_admin) {
    $isTeacher = false;
}

// Missing type → error
if (!$type) {
    echo "<div style='padding:12px;color:#b91c1c;font-weight:600'>⚠ No report type selected.</div>";
    exit;
}

// Base filter (teachers only)
$student_filter_sql = "";
$params = [];

if ($isTeacher && $is_teacher) {
    // Teacher sees only their own students
    $student_filter_sql = " WHERE s.teacher_id = :tid ";
    $params[':tid'] = $teacher_id;
}

/* ============================================================
   TABLE RENDER FUNCTION
   ============================================================ */
function render_table($headers, $rows) {
    echo "<div class='table-wrap'><table>";
    echo "<thead><tr>";
    foreach ($headers as $h) echo "<th>{$h}</th>";
    echo "</tr></thead><tbody>";

    if (!$rows) {
        echo "<tr><td colspan='".count($headers)."' style='text-align:center;color:#777;padding:16px'>No data available.</td></tr>";
    } else {
        foreach ($rows as $r) {
            echo "<tr>";
            foreach ($headers as $key => $label) {
                $k = is_string($key) ? $key : $label;
                echo "<td>" . htmlspecialchars($r[$k] ?? '') . "</td>";
            }
            echo "</tr>";
        }
    }
    echo "</tbody></table></div>";
}

/* ============================================================
   REPORT A — STUDENT PROGRESS REPORT
   ============================================================ */
if ($type === "student_progress") {

    $sql = "
        SELECT 
            s.id,
            s.student_name,
            s.username,
            s.grade_level,
            p.level,
            p.score,
            p.time_spent,
            p.date_updated
        FROM students s
        LEFT JOIN progress p ON p.student_id = s.id
        $student_filter_sql
        ORDER BY s.student_name ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Student Progress Report</h2>";
    echo "<p style='color:#555;margin-bottom:10px'>Latest performance and recorded progress of students.</p>";

    render_table(
        ["ID","Name","Username","Grade","Level","Score","Time Spent","Last Update"],
        $rows
    );

    exit;
}

/* ============================================================
   REPORT B — GRADE SUMMARY REPORT
   ============================================================ */
if ($type === "grade_summary") {

    $sql = "
        SELECT
            s.grade_level,
            COUNT(DISTINCT s.id) AS total_students,
            ROUND(AVG(p.score),2) AS avg_score,
            ROUND(AVG(p.time_spent),2) AS avg_time
        FROM students s
        LEFT JOIN progress p ON p.student_id = s.id
        $student_filter_sql
        GROUP BY s.grade_level
        ORDER BY s.grade_level ASC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Grade Summary Report</h2>";
    echo "<p style='color:#555;margin-bottom:10px'>Aggregated performance based on grade levels.</p>";

    render_table(
        ["Grade","Total Students","Avg Score","Avg Time"],
        $rows
    );

    exit;
}

/* ============================================================
   REPORT C — ASSIGNMENT PERFORMANCE REPORT
   ============================================================ */

// Filter assignments to teacher only
$assignment_filter = "";
$assign_params = [];

if ($isTeacher && $is_teacher) {
    $assignment_filter = " WHERE a.teacher_id = :tid ";
    $assign_params[':tid'] = $teacher_id;
}

if ($type === "assignment_performance") {

    $sql = "
        SELECT
            a.id AS assignment_id,
            a.title,
            a.grade_level,
            COUNT(sa.id) AS submissions,
            ROUND(AVG(sa.score),2) AS avg_score,
            ROUND(AVG(sa.time_spent),2) AS avg_time,
            MAX(sa.date_submitted) AS last_submit
        FROM assignments a
        LEFT JOIN student_assignment_submissions sa ON sa.assignment_id = a.id
        $assignment_filter
        GROUP BY a.id
        ORDER BY a.created_at DESC
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($assign_params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<h2>Assignment Performance Report</h2>";
    echo "<p style='color:#555;margin-bottom:10px'>Assignment completion, scoring, and timing.</p>";

    render_table(
        ["ID","Title","Grade","Submissions","Avg Score","Avg Time","Last Submitted"],
        $rows
    );

    exit;
}

/* ============================================================
   UNKNOWN TYPE (DEFAULT CATCH)
   ============================================================ */
echo "<div style='padding:12px;color:#b91c1c;font-weight:600'>⚠ Unknown report type.</div>";
exit;

?>
