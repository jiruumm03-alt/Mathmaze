<?php


$DB_HOST = "mysql-178347b3-jiruumm03-9e90.f.aivencloud.com";
$DB_PORT = 26168; 
$DB_USER = "avnadmin";
$DB_PASS = "";
$DB_NAME = "mathmaze_db";

// Initialize SSL connection
$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, NULL, NULL, NULL);
mysqli_real_connect(
    $conn,
    $DB_HOST,
    $DB_USER,
    $DB_PASS,
    $DB_NAME,
    $DB_PORT,
    NULL,
    MYSQLI_CLIENT_SSL
);

if (mysqli_connect_errno()) {
    die("DB Connection failed: " . mysqli_connect_error());
}

// Optional: ensure UTF-8 encoding
mysqli_set_charset($conn, "utf8mb4");
?>