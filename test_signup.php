<?php
// Quick test to verify signup is working with Supabase
session_start();
include 'database/supabase.php';

echo "<h2>Testing Signup to Supabase</h2>";

// Test data
$test_username = 'testuser' . rand(100, 999);
$test_email = 'test' . rand(100, 999) . '@example.com';

echo "<p>Creating test account...</p>";
echo "<p>Username: $test_username</p>";
echo "<p>Email: $test_email</p>";

$result = $supabase->insert('accounts', [
    'username' => $test_username,
    'first_name' => 'Test',
    'last_name' => 'User',
    'email' => $test_email,
    'password_hash' => password_hash('Test123!', PASSWORD_DEFAULT),
    'is_admin' => false
]);

echo "<h3>Result:</h3>";
echo "<pre>";
print_r($result);
echo "</pre>";

if ($result && !empty($result[0])) {
    echo "<p style='color: green;'>✅ SUCCESS! Account created with ID: " . $result[0]['account_id'] . "</p>";
    echo "<p>Now check your Supabase dashboard → Table Editor → accounts</p>";
    echo "<p>You should see the new user: <strong>$test_username</strong></p>";
    
    // Try to fetch it back
    echo "<h3>Verifying by fetching the account:</h3>";
    $fetched = $supabase->select('accounts', '*', ['account_id' => $result[0]['account_id']], true);
    echo "<pre>";
    print_r($fetched);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ FAILED! Account was not created.</p>";
    echo "<p>Check your Supabase credentials in database/supabase.php</p>";
}

echo "<hr>";
echo "<p><a href='login.php'>Go to Login Page</a></p>";
?>
