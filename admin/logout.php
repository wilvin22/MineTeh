<?php
session_start();

// Only destroy admin session data, not user session
unset($_SESSION['admin_user_id']);
unset($_SESSION['admin_username']);
unset($_SESSION['admin_is_admin']);

// Redirect to admin login page
header('Location: login.php?logout=success');
exit();
?>
