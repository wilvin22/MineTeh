<?php
// Simple password hasher tool

if (isset($_POST['password'])) {
    $password = $_POST['password'];
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    echo "<div style='background: #d1e7dd; color: #0f5132; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3>Password Hash Generated!</h3>";
    echo "<p><strong>Original Password:</strong> " . htmlspecialchars($password) . "</p>";
    echo "<p><strong>Hashed Password:</strong></p>";
    echo "<textarea style='width: 100%; padding: 10px; font-family: monospace; font-size: 12px;' rows='3' readonly onclick='this.select()'>" . $hash . "</textarea>";
    echo "<p style='margin-top: 10px; font-size: 14px;'>Click the hash above to select it, then copy it.</p>";
    echo "</div>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hasher</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 600px;
        }
        
        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }
        
        p {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: bold;
        }
        
        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        
        input:focus {
            outline: none;
            border-color: #667eea;
        }
        
        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        button:hover {
            transform: translateY(-2px);
        }
        
        .instructions {
            background: #fff3cd;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }
        
        .instructions h3 {
            margin-bottom: 10px;
        }
        
        .instructions ol {
            margin-left: 20px;
        }
        
        .instructions li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Password Hasher</h1>
        <p>Generate a bcrypt hash for your admin password</p>
        
        <form method="POST">
            <div class="form-group">
                <label>Enter Password to Hash:</label>
                <input type="text" name="password" required placeholder="e.g., Admin1!">
            </div>
            <button type="submit">Generate Hash</button>
        </form>
        
        <div class="instructions">
            <h3>📋 How to Create Admin Account Manually:</h3>
            <ol>
                <li>Enter your desired password above and click "Generate Hash"</li>
                <li>Copy the generated hash</li>
                <li>Go to your Supabase dashboard</li>
                <li>Open the "accounts" table</li>
                <li>Click "Insert" → "Insert row"</li>
                <li>Fill in the fields:
                    <ul>
                        <li><strong>username:</strong> your admin username</li>
                        <li><strong>email:</strong> your admin email</li>
                        <li><strong>password_hash:</strong> paste the hash you copied</li>
                        <li><strong>first_name:</strong> your first name</li>
                        <li><strong>last_name:</strong> your last name</li>
                        <li><strong>is_admin:</strong> check the box (set to TRUE)</li>
                    </ul>
                </li>
                <li>Click "Save"</li>
                <li>Login at: <a href="admin/login.php">admin/login.php</a></li>
            </ol>
        </div>
    </div>
</body>
</html>
