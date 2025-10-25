-- mathmaze_schema.sql
-- Run this on your MySQL server (phpMyAdmin, CLI, or import)

CREATE DATABASE IF NOT EXISTS mathmaze_db;
USE mathmaze_db;


CREATE TABLE IF NOT EXISTS grade3_students (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE,
  password VARCHAR(255),
  full_name VARCHAR(100),
  grade_level INT DEFAULT 3
);
CREATE TABLE IF NOT EXISTS grade4_students LIKE grade3_students;
CREATE TABLE IF NOT EXISTS grade5_students LIKE grade3_students;
CREATE TABLE IF NOT EXISTS grade6_students LIKE grade3_students;

-- Progress per grade
CREATE TABLE IF NOT EXISTS grade3_progress (
  id INT AUTO_INCREMENT PRIMARY KEY,
  student_id INT,
  level INT,
  score INT,
  time_spent FLOAT,
  date_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES grade3_students(id)
);
CREATE TABLE IF NOT EXISTS grade4_progress LIKE grade3_progress;
CREATE TABLE IF NOT EXISTS grade5_progress LIKE grade3_progress;
CREATE TABLE IF NOT EXISTS grade6_progress LIKE grade3_progress;

-- Teachers per grade (for dashboard login)
CREATE TABLE IF NOT EXISTS grade3_teachers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE,
  password VARCHAR(255),
  full_name VARCHAR(100)
);
CREATE TABLE IF NOT EXISTS grade4_teachers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE,
  password VARCHAR(255),
  full_name VARCHAR(100)
);
CREATE TABLE IF NOT EXISTS grade5_teachers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE,
  password VARCHAR(255),
  full_name VARCHAR(100)
);
CREATE TABLE IF NOT EXISTS grade6_teachers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) UNIQUE,
  password VARCHAR(255),
  full_name VARCHAR(100)
);

-- Example: create a sample teacher (change password)
-- Use the PHP /create_teacher.php script to insert hashed password securely.
