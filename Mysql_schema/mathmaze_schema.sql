-- mathmaze_schema.sql
-- Run this on your MySQL server (phpMyAdmin, CLI, or import)

CREATE DATABASE IF NOT EXISTS mathmaze_db;
USE mathmaze_db;


-- Students table (all grades)
CREATE TABLE IF NOT EXISTS students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100),
  age INT,
  grade_level INT NOT NULL CHECK (grade_level IN (3, 4, 5, 6)),
  INDEX idx_grade_level (grade_level)
);

-- Progress tracking (all grades)
CREATE TABLE IF NOT EXISTS progress (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT NOT NULL,
  level INT,
  score INT,
  time_spent FLOAT,
  date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
  INDEX idx_student_id (student_id),
  INDEX idx_student_level (student_id, level)
);

-- Teachers table (all grades)
CREATE TABLE IF NOT EXISTS teachers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE,
  password VARCHAR(255),
  full_name VARCHAR(100),
  grade_level INT NOT NULL CHECK (grade_level IN (3, 4, 5, 6)),
  INDEX idx_grade_level (grade_level)
);

-- Example: create a sample teacher (change password)
-- Use the PHP /create_teacher.php script to insert hashed password securely.
