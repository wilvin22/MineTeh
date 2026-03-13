<?php
// Script to create or promote a user to admin
include 'config.php';
include 'database/supabase.php';

echo "<h2>Create Admin Account</h2>";
echo "<p>This script will help you create an admin account or promote an existing user to admin.</p>";

// Check if form is submitted
if (isset($_POST['action'])) {
    if ($_POST['action'] === 'promote') {
        // Promote existing user to admin
        $username = trim($_POST['username']);
        
        // Find user
        $user = $supabase->select('accounts', '*', ['username' => $username], true);
        
        if ($user) {
            // Update user to admin
            $result = $supabase->update('accounts', 
                ['is_admin' => true], 
                ['account_id' => $user['account_id']]
            );
            
            if ($result) {
                echo "<div style='background: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
                echo "✓ Success! User '{$username}' is now an admin.<br>";
                echo "You can now login at: <a href='admin/login.php'>admin/login.php</a>";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
                echo "✗ Failed to update user to admin.";
                echo "</div>";
            }
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
            echo "✗ User '{$username}' not found.";
            echo "</div>";
        }
    } elseif ($_POST['action'] === 'create') {
        // Create new admin account
        $username = trim($_POST['new_username']);
        $email = trim($_POST['email']);
        $password = $_POST['password'];
        $first_name = trim($_POST['first_name']);
        $last_name = trim($_POST['last_name']);
        
        // Check if username already exists
        $existing = $supabase->select('accounts', '*', ['username' => $username], true);
        
        if ($existing) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
            echo "✗ Username '{$username}' already exists.";
            echo "</div>";
        } else {
            // Create new admin account
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // Create new admin account
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $result = $supabase->insert('accounts', [
                'username' => $username,
                'email' => $email,
                'password_hash' => $password_hash,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'is_admin' => true
            ]);
            
            if ($result !== false && !empty($result)) {
                echo "<div style='background: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
                echo "✓ Success! Admin account created.<br>";
                echo "Username: {$username}<br>";
                echo "You can now login at: <a href='admin/login.php'>admin/login.php</a>";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin: 20px 0;'>";
                echo "✗ Failed to create admin account.<br>";
                $error = $supabase->getLastError();
                if ($error) {
                    echo "<strong>Error Details:</strong><br>";
                    echo "HTTP Code: " . $error['http_code'] . "<br>";
                    echo "Response: " . htmlspecialchars($error['response']) . "<br>";
                }
                echo "</div>";
            }
        }
    }
}

// Get all existing users
$all_users = $supabase->select('accounts', 'account_id, username, email, first_name, last_name, is_admin', []);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin Account</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        h2 {
            color: #333;
            margin-bottom: 10px;
        }
        
        p {
            color: #666;
            margin-bottom: 30px;
        }
        
        .section {
            margin-bottom: 40px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        h3 {
            color: #667eea;
            margin-bottom: 20px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #333;
            font-weight: bold;
        }
        
        input, select {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        button {
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        button:hover {
            transform: translateY(-2px);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #667eea;
            color: white;
            font-weight: bold;
        }
        
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-admin {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        .badge-user {
            background: #cfe2ff;
            color: #084298;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🛡️ Admin Account Management</h2>
        <p>Create a new admin account or promote an existing user to admin.</p>
        
        <!-- Option 1: Promote Existing User -->
        <div class="section">
            <h3>Option 1: Promote Existing User to Admin</h3>
            <form method="POST">
                <input type="hidden" name="action" value="promote">
                <div class="form-group">
                    <label>Select User to Promote:</label>
                    <select name="username" required>
                        <option value="">-- Select a user --</option>
                        <?php if (!empty($all_users)): ?>
                            <?php foreach ($all_users as $user): ?>
                                <?php if (!$user['is_admin']): ?>
                                    <option value="<?php echo htmlspecialchars($user['username']); ?>">
                                        <?php echo htmlspecialchars($user['username']); ?> 
                                        (<?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <button type="submit">Promote to Admin</button>
            </form>
        </div>
        
        <!-- Option 2: Create New Admin Account -->
        <div class="section">
            <h3>Option 2: Create New Admin Account</h3>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="new_username" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" required>
                </div>
                <div class="form-group">
                    <label>First Name:</label>
                    <input type="text" name="first_name" required>
                </div>
                <div class="form-group">
                    <label>Last Name:</label>
                    <input type="text" name="last_name" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required minlength="6">
                </div>
                <button type="submit">Create Admin Account</button>
            </form>
        </div>
        
        <!-- Current Users List -->
        <div class="section">
            <h3>Current Users</h3>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($all_users)): ?>
                        <?php foreach ($all_users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge <?php echo $user['is_admin'] ? 'badge-admin' : 'badge-user'; ?>">
                                        <?php echo $user['is_admin'] ? 'ADMIN' : 'USER'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align: center; color: #666;">No users found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <p style="margin-top: 30px; text-align: center; color: #999; font-size: 12px;">
            ⚠️ Important: Delete this file (create_admin.php) after creating your admin account for security reasons.
        </p>
    </div>
</body>
</html>
