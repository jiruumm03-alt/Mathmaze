<?php
// auth.php
session_start();

require_once __DIR__ . '/db.php';

function is_logged_in() {
    return !empty($_SESSION['user']);
}

function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit;
    }
}

function current_user() {
    return $_SESSION['user'] ?? null;
}

// Check role functions
function is_admin() {
    $u = current_user();
    return $u && ($u['role'] === 'admin' || $u['role'] === 'super_admin');
}
function is_teacher() {
    $u = current_user();
    return $u && $u['role'] === 'teacher';
}
