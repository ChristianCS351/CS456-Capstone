<?php
session_start(); // Start the session to access it

// Destroy all session data
session_unset();     // Remove all session variables
session_destroy();   // Destroy the session

// Optionally, destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Redirect to login page
header("Location: login.php");
exit;
?>
