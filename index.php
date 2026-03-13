<?php
/**
 * MineTeh - Main Entry Point
 * Redirects to login page or homepage based on session
 */
session_start();

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    // User is logged in, redirect to homepage
    header("Location: home/homepage.php");
} else {
    // User is not logged in, redirect to login
    header("Location: login.php");
}
exit;
?>
