<!DOCTYPE html>
<html>
<head>
    <title>Setup Supabase Credentials</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        label {
            display: block;
            margin-top: 20px;
            font-weight: bold;
            color: #555;
        }
        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            box-sizing: border-box;
        }
        button {
            margin-top: 20px;
            padding: 12px 30px;
            background: #3ecf8e;
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }
        button:hover {
            background: #2da771;
        }
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #2196f3;
        }
        .success {
            background: #d4edda;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #28a745;
            color: #155724;
        }
        .error {
            background: #f8d7da;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Setup Supabase Credentials</h1>
        
        <div class="info">
            <strong>Where to find your credentials:</strong><br>
            1. Go to <a href="https://supabase.com" target="_blank">supabase.com</a><br>
            2. Open your project<br>
            3. Click <strong>Settings</strong> (gear icon) → <strong>API</strong><br>
            4. Copy the values below
        </div>

        <?php
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $url = trim($_POST['supabase_url']);
            $key = trim($_POST['supabase_key']);
            
            if (empty($url) || empty($key)) {
                echo '<div class="error">❌ Please fill in both fields!</div>';
            } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
                echo '<div class="error">❌ Invalid URL format. Should look like: https://xxxxx.supabase.co</div>';
            } else {
                // Read the current file
                $filePath = 'database/supabase.php';
                $content = file_get_contents($filePath);
                
                // Replace the placeholders
                $content = str_replace('YOUR_SUPABASE_URL', $url, $content);
                $content = str_replace('YOUR_SUPABASE_ANON_KEY', $key, $content);
                
                // Write back
                if (file_put_contents($filePath, $content)) {
                    echo '<div class="success">';
                    echo '✅ <strong>Success!</strong> Credentials saved to database/supabase.php<br><br>';
                    echo '<strong>Next steps:</strong><br>';
                    echo '1. Go to Supabase SQL Editor<br>';
                    echo '2. Run the SQL from <code>supabase_schema.sql</code><br>';
                    echo '3. Then test: <a href="test_supabase.php">test_supabase.php</a>';
                    echo '</div>';
                } else {
                    echo '<div class="error">❌ Could not write to file. Check file permissions.</div>';
                }
            }
        }
        ?>

        <form method="POST">
            <label for="supabase_url">
                Project URL
                <small style="color: #888; font-weight: normal;">(e.g., https://abcdefghijk.supabase.co)</small>
            </label>
            <input type="text" 
                   id="supabase_url" 
                   name="supabase_url" 
                   placeholder="https://xxxxx.supabase.co"
                   required>

            <label for="supabase_key">
                anon public Key
                <small style="color: #888; font-weight: normal;">(long string from "Project API keys" section)</small>
            </label>
            <input type="text" 
                   id="supabase_key" 
                   name="supabase_key" 
                   placeholder="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
                   required>

            <button type="submit">💾 Save Credentials</button>
        </form>

        <div class="info" style="margin-top: 30px;">
            <strong>⚠️ Important:</strong> Make sure you're using the <code>anon</code> key, 
            NOT the <code>service_role</code> key!
        </div>
    </div>
</body>
</html>
