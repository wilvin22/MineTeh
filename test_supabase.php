<?php
// Simple test to verify Supabase connection
include 'database/supabase.php';

echo "<h2>Testing Supabase Connection</h2>";

// Test 1: Check if we can connect
echo "<h3>Test 1: Connection Test</h3>";
$result = $supabase->select('accounts', 'count');
if ($result !== false) {
    echo "✅ Connection successful!<br>";
} else {
    echo "❌ Connection failed. Check your credentials in database/supabase.php<br>";
    die();
}

// Test 2: Create a test account
echo "<h3>Test 2: Insert Test</h3>";
$testUser = $supabase->insert('accounts', [
    'username' => 'testuser_' . time(),
    'first_name' => 'Test',
    'last_name' => 'User',
    'email' => 'test_' . time() . '@example.com',
    'password_hash' => password_hash('test123', PASSWORD_DEFAULT),
    'is_admin' => false
]);

if ($testUser && !empty($testUser[0])) {
    echo "✅ Insert successful! User ID: " . $testUser[0]['account_id'] . "<br>";
    $test_user_id = $testUser[0]['account_id'];
    
    // Test 3: Read the account back
    echo "<h3>Test 3: Select Test</h3>";
    $user = $supabase->select('accounts', '*', ['account_id' => $test_user_id], true);
    if ($user) {
        echo "✅ Select successful! Username: " . $user['username'] . "<br>";
    } else {
        echo "❌ Select failed<br>";
    }
    
    // Test 4: Update the account
    echo "<h3>Test 4: Update Test</h3>";
    $updateResult = $supabase->update('accounts', 
        ['first_name' => 'Updated'],
        ['account_id' => $test_user_id]
    );
    if ($updateResult !== false) {
        echo "✅ Update successful!<br>";
    } else {
        echo "❌ Update failed<br>";
    }
    
    // Test 5: Count accounts
    echo "<h3>Test 5: Count Test</h3>";
    $count = $supabase->count('accounts');
    echo "✅ Total accounts: " . $count . "<br>";
    
    // Test 6: Delete the test account
    echo "<h3>Test 6: Delete Test</h3>";
    $deleteResult = $supabase->delete('accounts', ['account_id' => $test_user_id]);
    if ($deleteResult !== false) {
        echo "✅ Delete successful!<br>";
    } else {
        echo "❌ Delete failed<br>";
    }
    
} else {
    echo "❌ Insert failed. Error details should be in PHP error log<br>";
}

echo "<br><h3>All Tests Complete!</h3>";
echo "<p>If all tests passed, your Supabase setup is working correctly.</p>";
echo "<p>You can now test your actual application features:</p>";
echo "<ul>";
echo "<li><a href='login.php'>Test Login/Signup</a></li>";
echo "<li>After login, test creating a listing</li>";
echo "<li>Test placing bids</li>";
echo "<li>Test favorites</li>";
echo "</ul>";
?>
