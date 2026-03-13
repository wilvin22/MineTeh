<?php
session_start();
date_default_timezone_set('Asia/Manila');

include 'config.php';
include 'database/supabase.php';

echo "<!DOCTYPE html><html><head><title>Column Test</title></head><body>";
echo "<h2>Testing if user_status columns exist</h2>";
echo "<hr>";

if (!isset($_SESSION['user_id'])) {
    echo "❌ No user logged in. Please <a href='login.php'>login</a> first.";
    echo "</body></html>";
    exit;
}

echo "<h3>Test 1: Query with ALL columns (*)</h3>";
$user_all = $supabase->select('accounts', '*', ['account_id' => $_SESSION['user_id']], true);

if ($user_all === false) {
    echo "❌ Query failed<br>";
    $error = $supabase->getLastError();
    echo "<pre>" . print_r($error, true) . "</pre>";
} elseif (empty($user_all)) {
    echo "❌ No user found with account_id: " . $_SESSION['user_id'] . "<br>";
} else {
    echo "✅ User found! Columns available:<br>";
    echo "<pre>" . print_r(array_keys($user_all), true) . "</pre>";
    
    echo "<h3>Checking for restriction columns:</h3>";
    echo "user_status exists: " . (isset($user_all['user_status']) ? '✅ YES' : '❌ NO') . "<br>";
    echo "restriction_until exists: " . (isset($user_all['restriction_until']) ? '✅ YES' : '❌ NO') . "<br>";
    echo "status_reason exists: " . (isset($user_all['status_reason']) ? '✅ YES' : '❌ NO') . "<br>";
    
    if (isset($user_all['user_status'])) {
        echo "<hr><h3>Current Values:</h3>";
        echo "user_status: <strong>" . $user_all['user_status'] . "</strong><br>";
        echo "restriction_until: " . ($user_all['restriction_until'] ?? 'NULL') . "<br>";
        echo "status_reason: " . ($user_all['status_reason'] ?? 'NULL') . "<br>";
    }
}

echo "<hr>";
echo "<p><a href='home/homepage.php'>Go to Homepage</a></p>";
echo "</body></html>";
?>
