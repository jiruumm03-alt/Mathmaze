PHP Teacher Dashboard for MathMaze
---------------------------------
Files:
  - db.php             : DB connection (uses env vars if available)
  - login.php          : Teacher login form
  - dashboard.php      : Shows progress for the teacher's grade
  - logout.php         : Logs out teacher
  - create_teacher.php : Simple form to create teacher accounts (run once)
  - styles.css         : basic styles

Deployment:
  1. Upload to a PHP-capable host (000webhost, shared host, or VPS with PHP & Apache).
  2. Ensure the MySQL database is accessible and import mysql_schema/mathmaze_schema.sql
  3. Edit environment variables or directly edit db.php with credentials.
  4. Use create_teacher.php to add teacher accounts (it will hash passwords).
  5. Visit login.php and sign in as a teacher.

Security note:
  - Do not leave create_teacher.php public after creating accounts.
  - In production, restrict access by HTTPS and consider moving DB credentials to host env vars.
