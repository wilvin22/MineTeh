<?php
session_start();
include 'database/supabase.php';

if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

$user_id = $_SESSION['user_id'];

// Get user data
$user = $supabase->select('accounts', 'account_id, username, user_status, restriction_until, status_reason', ['account_id' => $user_id], true);

echo "<h2>User Restriction Debug</h2>";
echo "<pre>";
print_r($user);
echo "</pre>";

echo "<hr>";
echo "<h3>Analysis:</h3>";
echo "User Status: " . ($user['user_status'] ?? 'NULL') . "<br>";
echo "Restriction Until: " . ($user['restriction_until'] ?? 'NULL') . "<br>";
echo "Status Reason: " . ($user['status_reason'] ?? 'NULL') . "<br>";

if (empty($user['status_reason'])) {
    echo "<br><strong style='color: red;'>⚠️ Status reason is empty! This is why it shows 'No reason provided'</strong>";
}
?>
