<?php
/**
 * Block Admin Access to User Pages
 * Include this file at the top of any user-facing page after session_start()
 */

// If ONLY admin is logged in (no user session), redirect to admin panel
// This allows admin to also be logged in as a user simultaneously
if (isset($_SESSION['admin_is_admin']) && $_SESSION['admin_is_admin'] === true && !isset($_SESSION['user_id'])) {
    header("Location: " . (strpos($_SERVER['PHP_SELF'], '/home/') !== false ? '../admin/index.php' : 'admin/index.php'));
    exit;
}
?>
