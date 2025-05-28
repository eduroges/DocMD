<?php
// Start or resume the session
session_start();

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    // User is logged in, redirect to dashboard.php
    header("Location: dashboard.php");
    exit;
} else {
    // User is not logged in, redirect to login.php
    header("Location: login.php");
    exit;
}
?>
