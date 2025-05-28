<?php
// auth.php - Authentication Check

// 1. Session Start
// Check if a session is already active before starting a new one.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. Authentication Check
// Check if the user_id session variable is set, which indicates the user is logged in.
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    // User is not authenticated.
    
    // 3. Redirect to login.php
    header("Location: login.php");
    exit; // Ensure script execution stops after redirection.
}

// If $_SESSION['user_id'] is set, the script will simply complete, 
// allowing the script that included it to continue execution.
// This file produces no output itself.
?>
