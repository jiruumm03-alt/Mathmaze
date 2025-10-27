// app.js - MathMaze Node.js API (Express)
// Usage: set environment variables in .env or platform settings:
// DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, PORT (optional)
const dotenv = require("dotenv");
dotenv.config();


const express = require('express');
const mysql = require('mysql2');
const bcrypt = require("bcryptjs");
const cors = require('cors');
require('dotenv').config();

const app = express();
app.use(express.json());
app.use(cors());

// ✅ Health check route for Render
app.get('/', (req, res) => {
  res.status(200).send('✅ MathMaze API is live and healthy!');
});

const db = mysql.createPool({
  host: process.env.DB_HOST || 'localhost',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASSWORD || '',
  database: process.env.DB_NAME || 'mathmaze_db',
  port: process.env.DB_PORT ? Number(process.env.DB_PORT) : 3306,
  waitForConnections: true,
  connectionLimit: 10,
  queueLimit: 0,
  ssl: { rejectUnauthorized: true }
});

// Helper: allow grade only 3-6
function validGrade(g){ return [3,4,5,6].includes(Number(g)); }

// SIGNIN - auto-register if not exists
app.post('/signin', async (req, res) => {
  try {
    const { username, password, grade_level } = req.body;
    if (!username || !password || !validGrade(grade_level)) return res.status(400).json({ message: 'Missing or invalid fields' });
    const studentTable = `grade${grade_level}_students`;

    const [rows] = await db.promise().query(`SELECT * FROM ?? WHERE username = ?`, [studentTable, username]);
    if (rows.length === 0){
      const hashed = await bcrypt.hash(password, 10);
      const [result] = await db.promise().query(`INSERT INTO ?? (username, password, full_name, grade_level) VALUES (?, ?, ?, ?)`, [studentTable, username, hashed, username, grade_level]);
      return res.json({ message: 'New student registered', user_id: result.insertId });
    } else {
      const student = rows[0];
      const match = await bcrypt.compare(password, student.password);
      if (!match) return res.status(401).json({ message: 'Invalid password' });
      return res.json({ message: 'Login successful', user_id: student.id });
    }
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: 'Server error', error: err.message });
  }
});

// SAVE PROGRESS
app.post('/save_progress', async (req, res) => {
  try {
    const { user_id, level, score, time_spent, grade_level } = req.body;
    if (!user_id || !validGrade(grade_level)) return res.status(400).json({ message: 'Missing or invalid fields' });
    const progressTable = `grade${grade_level}_progress`;
    const [result] = await db.promise().query(`INSERT INTO ?? (student_id, level, score, time_spent) VALUES (?, ?, ?, ?)`, [progressTable, user_id, level || 0, score || 0, time_spent || 0]);
    return res.json({ message: 'Progress saved successfully', progress_id: result.insertId });
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: 'Server error', error: err.message });
  }
});

// OPTIONAL: endpoint for teacher to fetch students & progress by grade (no auth - you must add auth in production)
app.get('/grade/:g/progress', async (req, res) => {
  try {
    const grade = Number(req.params.g);
    if (!validGrade(grade)) return res.status(400).json({ message: 'Invalid grade' });
    const studentTable = `grade${grade}_students`;
    const progressTable = `grade${grade}_progress`;
    const [rows] = await db.promise().query(`SELECT s.id AS student_id, s.username, s.full_name, p.level, p.score, p.time_spent, p.date_updated FROM ?? p JOIN ?? s ON p.student_id = s.id ORDER BY p.date_updated DESC LIMIT 500`, [progressTable, studentTable]);
    return res.json(rows);
  } catch (err) {
    console.error(err);
    return res.status(500).json({ message: 'Server error', error: err.message });
  }
});

const PORT = process.env.PORT || 3001;
app.listen(PORT, () => console.log(`MathMaze API listening on port ${PORT}`));
