<?php
require_once 'auth.php';

// Log out user and destroy existing session
logout_user();

// Start fresh session to pass logout notification message
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['success'] = "You have been successfully logged out.";

header("Location: login.php");
exit();
?>
