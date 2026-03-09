<?php
session_start();
include 'database/supabase.php';

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot-password.php");
    exit;
}

$error = '';

if (isset($_POST['verify_code'])) {
    $code = trim($_POST['code']);
    $email = $_SESSION['reset_email'];
    
    // Find valid reset code
    $reset = $supabase->customQuery('password_resets', '*', 
        'email=eq.' . urlencode($email) . 
        '&reset_code=eq.' . $code . 
        '&used=eq.false' .
        '&order=created_at.desc' .
        '&limit=1'
    );
    
    if (!empty($reset) && is_array($reset)) {
        $reset = $reset[0];
        
        // Check if expired
        if (strtotime($reset['expires_at']) > time()) {
            $_SESSION['reset_code_verified'] = true;
            $_SESSION['reset_id'] = $reset['id'];
            header("Location: reset-password.php");
            exit;
        } else {
            $error = "This code has expired. Please request a new one.";
        }
    } else {
        $error = "Invalid code. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Reset Code - MineTeh</title>
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
            max-width: 450px;
        }

        .logo {
            text-align: center;
            font-size: 48px;
            margin-bottom: 10px;
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
        }

        .subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
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
            font-size: 24px;
            text-align: center;
            letter-spacing: 10px;
            font-family: 'Courier New', monospace;
        }

        input:focus {
            outline: none;
            border-color: #667eea;
        }

        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
        }

        .btn {
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

        .btn:hover {
            transform: translateY(-2px);
        }

        .back-link {
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #667eea;
            text-decoration: none;
        }

        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #667eea;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">📧</div>
        <h1>Enter Reset Code</h1>
        <p class="subtitle">Check your email for the 6-digit code</p>

        <div class="info-box">
            Code sent to: <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong>
            <?php if (isset($_SESSION['temp_reset_code'])): ?>
                <br><br>
                <div style="background: #fff3cd; padding: 10px; border-radius: 4px; margin-top: 10px;">
                    <strong>⚠️ TESTING MODE - Your code is:</strong><br>
                    <span style="font-size: 24px; font-family: 'Courier New', monospace; letter-spacing: 5px;">
                        <?php echo $_SESSION['temp_reset_code']; ?>
                    </span>
                    <br><small>(Email sending is disabled. In production, this will be sent via email.)</small>
                </div>
                <?php unset($_SESSION['temp_reset_code']); // Clear after showing ?>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>6-Digit Code</label>
                <input type="text" name="code" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus>
            </div>

            <button type="submit" name="verify_code" class="btn">Verify Code</button>
        </form>

        <div class="back-link">
            <a href="forgot-password.php">← Request New Code</a>
        </div>
    </div>
</body>
</html>
