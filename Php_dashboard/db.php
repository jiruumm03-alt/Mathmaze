<?php
// db.php - database connection (update with your online credentials)
$DB_HOST = getenv('DB_HOST') ?: 'db4free.net';
$DB_USER = getenv('DB_USER') ?: 'your_db_user';
$DB_PASS = getenv('DB_PASS') ?: 'your_db_password';
$DB_NAME = getenv('DB_NAME') ?: 'mathmaze_db';

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($conn->connect_error) {
  die('DB Connection failed: ' . $conn->connect_error);
}
?>
