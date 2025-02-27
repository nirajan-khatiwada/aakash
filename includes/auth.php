<?php
// Start session
session_start();

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Redirect if user is not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

// Redirect if user is not admin
function requireAdmin() {
    requireLogin();
    if ($_SESSION['role'] !== 'admin') {
        header('Location: student-dashboard.php');
        exit;
    }
}

// Redirect if user is not student
function requireStudent() {
    requireLogin();
    if ($_SESSION['role'] !== 'student') {
        header('Location: admin-dashboard.php');
        exit;
    }
}

// Logout function
function logout() {
    // Destroy all session data
    session_destroy();
    
    // Redirect to login page
    header('Location: login.php');
    exit;
}
?>