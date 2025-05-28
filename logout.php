<?php
// 1. Start the session
// It's necessary to start the session to be able to destroy it.
session_start();

// 2. Unset all of the session variables
$_SESSION = array();

// 3. Destroy the session.
// This will remove the session cookie as well if session.use_cookies is enabled in php.ini (default).
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// 4. Redirect to login page
header("Location: login.php");
exit; // Ensure no further code is executed after redirection.
?>
