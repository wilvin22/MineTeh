<?php
session_start();
include 'database/supabase.php';
include 'send_email.php';

$message = '';
$error = '';

if (isset($_POST['send_code'])) {
    $email = trim($_POST['email']);
    
    // Check if email exists (query all columns)
    $user = $supabase->select('accounts', '*', ['email' => $email], true);
    
    if ($user && is_array($user)) {
        // Generate 6-digit code
        $reset_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        
        // Set expiration (15 minutes from now)
        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));
        
        // Store in database
        $supabase->insert('password_resets', [
            'email' => $email,
            'reset_code' => $reset_code,
            'expires_at' => $expires_at,
            'used' => false
        ]);
        
        // TEMPORARY: Skip email and show code directly (for testing)
        // In production, you should use SendGrid or similar service
        $_SESSION['reset_email'] = $email;
        $_SESSION['temp_reset_code'] = $reset_code; // TEMPORARY - for testing only
        header("Location: verify-reset-code.php");
        exit;
        
        /* ORIGINAL EMAIL CODE (uncomment when email is configured):
        // Send email
        if (sendPasswordResetEmail($email, $reset_code, $user['username'])) {
            $_SESSION['reset_email'] = $email;
            header("Location: verify-reset-code.php");
            exit;
        } else {
            $error = "Failed to send email. Please try again.";
        }
        */
    } else {
        $error = "No account found with that email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - MineTeh</title>
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
            font-size: 14px;
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

        .success {
            background: #d1e7dd;
            color: #0f5132;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🔐</div>
        <h1>Forgot Password</h1>
        <p class="subtitle">Enter your email to receive a reset code</p>

        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div class="success"><?php echo $message; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="your@email.com" required>
            </div>

            <button type="submit" name="send_code" class="btn">Send Reset Code</button>
        </form>

        <div class="back-link">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>
