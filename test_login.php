<?php
session_start();
include 'database/supabase.php';

echo "<h2>Login Debug Tool</h2>";
echo "<style>body { font-family: Arial; padding: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; }</style>";

// Test 1: Check if Supabase connection works
echo "<h3>Test 1: Supabase Connection</h3>";
try {
    $test = $supabase->select('accounts', 'account_id', [], 1);
    echo "<p class='success'>✓ Supabase connection working</p>";
} catch (Exception $e) {
    echo "<p class='error'>✗ Supabase connection failed: " . $e->getMessage() . "</p>";
}

// Test 2: List all accounts (without passwords)
echo "<h3>Test 2: Existing Accounts</h3>";
try {
    $accounts = $supabase->select('accounts', 'account_id,username,email,first_name,last_name,is_admin');
    if (!empty($accounts)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>First Name</th><th>Last Name</th><th>Admin</th></tr>";
        foreach ($accounts as $acc) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($acc['account_id']) . "</td>";
            echo "<td>" . htmlspecialchars($acc['username']) . "</td>";
            echo "<td>" . htmlspecialchars($acc['email']) . "</td>";
            echo "<td>" . htmlspecialchars($acc['first_name'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($acc['last_name'] ?? 'N/A') . "</td>";
            echo "<td>" . ($acc['is_admin'] ? 'Yes' : 'No') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='error'>No accounts found in database</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>Error fetching accounts: " . $e->getMessage() . "</p>";
}

// Test 3: Test login with specific credentials
if (isset($_POST['test_login'])) {
    echo "<h3>Test 3: Login Attempt</h3>";
    $login_input = trim($_POST['login_input']);
    $password = $_POST['password'];
    
    echo "<p class='info'>Attempting login with: " . htmlspecialchars($login_input) . "</p>";
    
    // Try to find user
    $user = $supabase->customQuery('accounts', '*', 'or=(email.eq.' . urlencode($login_input) . ',username.eq.' . urlencode($login_input) . ')&limit=1');
    
    if (!empty($user)) {
        $user = $user[0];
        echo "<p class='success'>✓ User found: " . htmlspecialchars($user['username']) . " (" . htmlspecialchars($user['email']) . ")</p>";
        
        // Test password
        if (password_verify($password, $user['password_hash'])) {
            echo "<p class='success'>✓ Password is correct!</p>";
            echo "<p class='success'>Login should work. User ID: " . $user['account_id'] . "</p>";
        } else {
            echo "<p class='error'>✗ Password is incorrect</p>";
            echo "<p class='info'>Password hash in database: " . substr($user['password_hash'], 0, 20) . "...</p>";
        }
    } else {
        echo "<p class='error'>✗ User not found with email/username: " . htmlspecialchars($login_input) . "</p>";
    }
}
?>

<h3>Test Login Form</h3>
<form method="POST">
    <p>
        <label>Email or Username:</label><br>
        <input type="text" name="login_input" required style="padding: 8px; width: 300px;">
    </p>
    <p>
        <label>Password:</label><br>
        <input type="password" name="password" required style="padding: 8px; width: 300px;">
    </p>
    <button type="submit" name="test_login" style="padding: 10px 20px; background: #945a9b; color: white; border: none; cursor: pointer;">Test Login</button>
</form>

<hr>
<p><a href="login.php">← Back to Login Page</a></p>
