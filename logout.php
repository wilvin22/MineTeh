<?php
session_start();

// Only destroy user session data, not admin session
unset($_SESSION['user_id']);
unset($_SESSION['username']);
unset($_SESSION['is_admin']);
unset($_SESSION['user_status']);

// Redirect to login page
header('Location: login.php?logout=success');
exit();
?>
