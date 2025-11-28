Node.js API for MathMaze
-----------------------
Files:
  - app.js           : main Express API
  - package.json     : npm dependencies
  - .env.example     : sample env file

Endpoints:
  POST /signin            -> { username, password, grade_level }  (auto-register)
  POST /save_progress     -> { user_id, level, score, time_spent, grade_level }
  GET  /grade/:g/progress -> returns recent progress for grade g (3-6)

Deployment:
  1. Copy .env.example to .env and set DB_* variables (or set env vars in host).
  2. npm install
  3. npm start (or use PM2/system service)

Security note:
  - This example omits authentication for teacher endpoints. Add API keys or auth for production.
