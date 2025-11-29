-- ===================================================================
-- MATHMAZE UNIFIED DATABASE - FINAL MIGRATION FILE
-- ===================================================================

CREATE DATABASE IF NOT EXISTS mathmaze_db;
USE mathmaze_db;

-- ==============================================================
-- MAIN TABLES
-- ==============================================================

CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    student_name VARCHAR(150),
    grade_level TINYINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    teacher_id INT NULL,
    teacher_grade TINYINT NULL
);

CREATE TABLE IF NOT EXISTS teachers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    full_name VARCHAR(150),
    grade_level TINYINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    grade_level TINYINT NOT NULL,
    level INT DEFAULT 0,
    score INT DEFAULT 0,
    time_spent FLOAT DEFAULT 0,
    date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS super_admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE,
    password VARCHAR(255)
);

-- ==============================================================
-- RELATIONSHIPS (OPTIONAL)
-- ==============================================================

ALTER TABLE students
  ADD CONSTRAINT fk_student_teacher
  FOREIGN KEY (teacher_id) REFERENCES teachers(id)
  ON DELETE SET NULL;

ALTER TABLE progress
  ADD CONSTRAINT fk_progress_student
  FOREIGN KEY (student_id) REFERENCES students(id)
  ON DELETE CASCADE;

-- ==============================================================
-- CLEANUP (JUST IN CASE)
-- ==============================================================

DROP TABLE IF EXISTS
  grade3_students, grade4_students, grade5_students, grade6_students,
  grade3_teachers, grade4_teachers, grade5_teachers, grade6_teachers,
  grade3_progress, grade4_progress, grade5_progress, grade6_progress;

-- DONE
