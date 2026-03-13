<?php
include 'config.php';
include 'database/supabase.php';

echo "<h2>Admin Login Debug Tool</h2>";
echo "<hr>";

// Test 1: Generate correct password hash
$password = 'Admin1!';
$hash = password_hash($password, PASSWORD_DEFAULT);

echo "<h3>1. Password Hash Generation</h3>";
echo "<p><strong>Password:</strong> Admin1!</p>";
echo "<p><strong>Generated Hash:</strong></p>";
echo "<textarea style='width: 100%; padding: 10px; font-family: monospace;' rows='2' readonly onclick='this.select()'>" . $hash . "</textarea>";
echo "<p style='color: #666; font-size: 14px;'>Copy this hash and update it in Supabase for the admin1 user</p>";

echo "<hr>";

// Test 2: Check if admin1 user exists
echo "<h3>2. Check if admin1 exists in database</h3>";
$user = $supabase->select('accounts', '*', ['username' => 'admin1'], true);

if ($user) {
    echo "<div style='background: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 8px; margin: 10px 0;'>";
    echo "✓ User 'admin1' found in database<br>";
    echo "<strong>Details:</strong><br>";
    echo "Account ID: " . $user['account_id'] . "<br>";
    echo "Username: " . $user['username'] . "<br>";
    echo "Email: " . $user['email'] . "<br>";
    echo "Is Admin: " . ($user['is_admin'] ? 'YES' : 'NO') . "<br>";
    echo "Password Hash: <code style='font-size: 11px;'>" . substr($user['password_hash'], 0, 50) . "...</code><br>";
    echo "</div>";
    
    // Test 3: Verify password
    echo "<hr>";
    echo "<h3>3. Password Verification Test</h3>";
    if (password_verify('Admin1!', $user['password_hash'])) {
        echo "<div style='background: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 8px;'>";
        echo "✓ Password 'Admin1!' matches the stored hash!<br>";
        echo "Login should work. If it doesn't, there might be an issue with the login script.";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px;'>";
        echo "✗ Password 'Admin1!' does NOT match the stored hash!<br>";
        echo "You need to update the password_hash in Supabase with the hash generated above.";
        echo "</div>";
    }
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px;'>";
    echo "✗ User 'admin1' NOT found in database<br>";
    echo "Please run the SQL insert command in Supabase first.";
    echo "</div>";
}

echo "<hr>";

// Test 4: Show SQL to update password
echo "<h3>4. SQL to Update Password Hash</h3>";
echo "<p>If the password verification failed, run this SQL in Supabase:</p>";
echo "<textarea style='width: 100%; padding: 10px; font-family: monospace;' rows='4' readonly onclick='this.select()'>";
echo "UPDATE accounts\nSET password_hash = '" . $hash . "'\nWHERE username = 'admin1';";
echo "</textarea>";

echo "<hr>";
echo "<p style='text-align: center; margin-top: 30px;'>";
echo "<a href='admin/login.php' style='padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 8px;'>Go to Admin Login</a>";
echo "</p>";
?>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        max-width: 900px;
        margin: 20px auto;
        padding: 20px;
        background: #f5f7fa;
    }
    h2 {
        color: #333;
    }
    h3 {
        color: #667eea;
        margin-top: 20px;
    }
    code {
        background: #f0f0f0;
        padding: 2px 6px;
        border-radius: 4px;
    }
</style>
