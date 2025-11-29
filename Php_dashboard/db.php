<?php
// db.php
// Simple PDO connection wrapper.
// Looks for a .env file in same directory (optional), fallback to constants below.

// ---------- CONFIG ----------
$DEFAULT_DB = [
    'DB_HOST' => 'mysql-178347b3-jiruumm03-9e90.f.aivencloud.com',
    'DB_PORT' => '26168',
    'DB_NAME' => 'mathmaze_db',
    'DB_USER' => 'avnadmin',
    'DB_PASS' => '',
    'DB_CHARSET' => 'utf8mb4'
];

function load_dotenv($path = __DIR__ . '/.env') {
    if (!file_exists($path)) return [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $vars = [];
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (!strpos($line, '=')) continue;
        list($k,$v) = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        $v = trim($v, "\"'");
        $vars[$k] = $v;
    }
    return $vars;
}

$env = load_dotenv();
$config = array_merge($DEFAULT_DB, $env);

$dsn = "mysql:host={$config['DB_HOST']};port={$config['DB_PORT']};charset={$config['DB_CHARSET']}";

try {
    // connect without db first to ensure DB exists
    $pdo = new PDO($dsn, $config['DB_USER'], $config['DB_PASS'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$config['DB_NAME']}` CHARACTER SET {$config['DB_CHARSET']} COLLATE {$config['DB_CHARSET']}_general_ci;");
    // switch to DB
    $pdo->exec("USE `{$config['DB_NAME']}`;");
} catch (PDOException $e) {
    die("DB connection failed: " . htmlspecialchars($e->getMessage()));
}

// Helper: return PDO object
function pdo() {
    global $pdo;
    return $pdo;
}

// --------------------------------------------------
// Auto-create core tables (safe: IF NOT EXISTS)
// --------------------------------------------------
$createStatements = [

"CREATE TABLE IF NOT EXISTS super_admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(150),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;",

"CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    full_name VARCHAR(150),
    grade_level TINYINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;",

"CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    student_name VARCHAR(150),
    grade_level TINYINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    teacher_id INT NULL,
    teacher_grade TINYINT NULL,
    INDEX (grade_level),
    INDEX (teacher_id)
) ENGINE=InnoDB;",

"CREATE TABLE IF NOT EXISTS progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    grade_level TINYINT NOT NULL,
    level INT DEFAULT 0,
    score INT DEFAULT 0,
    time_spent FLOAT DEFAULT 0,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (student_id),
    INDEX (grade_level)
) ENGINE=InnoDB;",

"CREATE TABLE IF NOT EXISTS assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    grade_level TINYINT,
    teacher_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;"
];

foreach ($createStatements as $sql) {
    try { pdo()->exec($sql); } catch (PDOException $e) {
        // safe continue but log
        error_log("DB create table error: " . $e->getMessage());
    }
}

// Basic foreign key linking if not already set (safe)
try {
    pdo()->exec("ALTER TABLE students ADD CONSTRAINT IF NOT EXISTS fk_students_teacher FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE SET NULL;");
} catch (Exception $e) { /* ignore for DB engines lacking IF NOT EXISTS FK */ }

