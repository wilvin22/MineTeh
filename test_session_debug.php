<?php
session_start();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Session Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; }
        .admin { border-left: 4px solid #667eea; }
        .user { border-left: 4px solid #51cf66; }
        h2 { margin-top: 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>🔍 Session Debug Tool</h1>
    
    <div class="box admin">
        <h2>Admin Session Variables</h2>
        <pre><?php
        echo "admin_user_id: " . (isset($_SESSION['admin_user_id']) ? $_SESSION['admin_user_id'] : 'NOT SET') . "\n";
        echo "admin_username: " . (isset($_SESSION['admin_username']) ? $_SESSION['admin_username'] : 'NOT SET') . "\n";
        echo "admin_is_admin: " . (isset($_SESSION['admin_is_admin']) ? ($_SESSION['admin_is_admin'] ? 'TRUE' : 'FALSE') : 'NOT SET') . "\n";
        ?></pre>
    </div>
    
    <div class="box user">
        <h2>User Session Variables</h2>
        <pre><?php
        echo "user_id: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "\n";
        echo "username: " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'NOT SET') . "\n";
        echo "is_admin: " . (isset($_SESSION['is_admin']) ? ($_SESSION['is_admin'] ? 'TRUE' : 'FALSE') : 'NOT SET') . "\n";
        echo "user_status: " . (isset($_SESSION['user_status']) ? $_SESSION['user_status'] : 'NOT SET') . "\n";
        ?></pre>
    </div>
    
    <div class="box">
        <h2>All Session Data</h2>
        <pre><?php print_r($_SESSION); ?></pre>
    </div>
    
    <div class="box">
        <h2>Session ID</h2>
        <pre><?php echo session_id(); ?></pre>
    </div>
    
    <div style="margin-top: 20px;">
        <a href="login.php" style="padding: 10px 20px; background: #51cf66; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px;">User Login</a>
        <a href="admin/login.php" style="padding: 10px 20px; background: #667eea; color: white; text-decoration: none; border-radius: 5px;">Admin Login</a>
    </div>
</body>
</html>
