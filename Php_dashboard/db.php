<?php
// Secure database connection using environment variables

$DB_HOST = getenv("DB_HOST") ?: "localhost";
$DB_PORT = getenv("DB_PORT") ?: 3306;
$DB_USER = getenv("DB_USER") ?: "root";
$DB_PASS = getenv("DB_PASS") ?: "";
$DB_NAME = getenv("DB_NAME") ?: "defaultdb";

// Initialize SSL connection (for Aiven)
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