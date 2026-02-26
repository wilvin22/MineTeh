<?php
session_start();
include 'database/supabase.php';

echo "<h1>Login Query Test</h1>";
echo "<style>body { font-family: Arial; padding: 20px; } .error { color: red; } .success { color: green; }</style>";

// Test with a sample username/email
$test_input = isset($_GET['test']) ? $_GET['test'] : 'dummy1';

echo "<h2>Testing login query for: " . htmlspecialchars($test_input) . "</h2>";

// Try the same query that login.php uses
$user = $supabase->customQuery('accounts', '*', 'or=(email.eq.' . urlencode($test_input) . ',username.eq.' . urlencode($test_input) . ')&limit=1');

echo "<h3>Query Result:</h3>";
if ($user === false) {
    echo "<p class='error'>❌ Query failed (returned false)</p>";
    $error = $supabase->getLastError();
    if ($error) {
        echo "<h4>Error Details:</h4>";
        echo "<pre>" . print_r($error, true) . "</pre>";
    }
} elseif (empty($user)) {
    echo "<p class='error'>❌ Query succeeded but no user found</p>";
    echo "<p>This means the account doesn't exist in the database.</p>";
} else {
    echo "<p class='success'>✅ User found!</p>";
    echo "<pre>" . print_r($user[0], true) . "</pre>";
}

// Test: Get all accounts
echo "<h3>All Accounts in Database:</h3>";
$all_accounts = $supabase->select('accounts', 'account_id,username,email,first_name,last_name');
if ($all_accounts && is_array($all_accounts)) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Name</th></tr>";
    foreach ($all_accounts as $acc) {
        echo "<tr>";
        echo "<td>" . $acc['account_id'] . "</td>";
        echo "<td>" . htmlspecialchars($acc['username']) . "</td>";
        echo "<td>" . htmlspecialchars($acc['email']) . "</td>";
        echo "<td>" . htmlspecialchars($acc['first_name'] . ' ' . $acc['last_name']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p class='error'>❌ Cannot fetch accounts - RLS might be enabled</p>";
    $error = $supabase->getLastError();
    if ($error) {
        echo "<pre>" . print_r($error, true) . "</pre>";
    }
}

echo "<hr>";
echo "<h3>Test with your credentials:</h3>";
echo "<form method='GET'>";
echo "<input type='text' name='test' placeholder='Enter username or email' value='" . htmlspecialchars($test_input) . "' style='padding: 8px; width: 300px;'>";
echo "<button type='submit' style='padding: 8px 20px; background: #945a9b; color: white; border: none; cursor: pointer;'>Test</button>";
echo "</form>";

echo "<p><a href='login.php'>← Back to Login</a></p>";
?>
