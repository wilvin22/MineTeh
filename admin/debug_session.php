<?php
session_start();

echo "<h2>Admin Session Debug</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "</pre>";

echo "<hr>";
echo "<h3>Session Checks:</h3>";
echo "user_id isset: " . (isset($_SESSION['user_id']) ? 'YES' : 'NO') . "<br>";
echo "is_admin isset: " . (isset($_SESSION['is_admin']) ? 'YES' : 'NO') . "<br>";
echo "is_admin value: " . (isset($_SESSION['is_admin']) ? var_export($_SESSION['is_admin'], true) : 'NOT SET') . "<br>";
echo "is_admin == true: " . (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == true ? 'YES' : 'NO') . "<br>";
echo "is_admin === true: " . (isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true ? 'YES' : 'NO') . "<br>";

echo "<hr>";
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    echo "<strong style='color: red;'>❌ Session check FAILED - Would redirect to login</strong>";
} else {
    echo "<strong style='color: green;'>✓ Session check PASSED - Admin access granted</strong>";
}
?>
