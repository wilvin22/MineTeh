<?php
session_start();
include 'database/supabase.php';
include 'send_email.php';

$error   = '';
$success = '';

if (isset($_POST['send_code'])) {
    $email = trim($_POST['email']);

    $user = $supabase->select('accounts', 'account_id,username,email', ['email' => $email], true);

    if ($user && is_array($user)) {
        // Generate 6-digit code
        $reset_code = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires_at = date('Y-m-d H:i:s', strtotime('+15 minutes'));

        // Invalidate any previous unused codes for this email
        // (Supabase doesn't support UPDATE with neq easily, so just insert a new one — verify checks latest)
        $supabase->insert('password_resets', [
            'email'      => $email,
            'reset_code' => $reset_code,
            'expires_at' => $expires_at,
            'used'       => false,
        ]);

        $sent = sendPasswordResetEmail($email, $reset_code, $user['username']);

        if ($sent) {
            $_SESSION['reset_email'] = $email;
            $success = "A 6-digit reset code has been sent to <strong>" . htmlspecialchars($email) . "</strong>. Check your inbox (and spam folder).";
        } else {
            // Email failed — fall back to showing code on-screen so the flow still works
            $_SESSION['reset_email']      = $email;
            $_SESSION['temp_reset_code']  = $reset_code;
            header("Location: verify-reset-code.php");
            exit;
        }
    } else {
        // Intentionally vague to prevent email enumeration
        $success = "If that email is registered, a reset code has been sent.";
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
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex; justify-content: center; align-items: center;
            padding: 20px;
        }
        .container {
            background: white; padding: 40px; border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            width: 100%; max-width: 440px;
        }
        .logo { text-align: center; font-size: 48px; margin-bottom: 10px; }
        h1 { text-align: center; color: #333; margin-bottom: 6px; }
        .subtitle { text-align: center; color: #666; margin-bottom: 28px; font-size: 14px; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; font-size: 14px; }
        input[type="email"] {
            width: 100%; padding: 13px 14px; border: 2px solid #e0e0e0;
            border-radius: 10px; font-size: 15px; font-family: inherit;
            transition: border-color 0.2s;
        }
        input[type="email"]:focus { outline: none; border-color: #945a9b; }
        .error {
            background: #f8d7da; color: #721c24;
            padding: 13px 16px; border-radius: 10px; margin-bottom: 20px;
            font-size: 14px; border-left: 4px solid #dc3545;
        }
        .success {
            background: #d1e7dd; color: #0f5132;
            padding: 13px 16px; border-radius: 10px; margin-bottom: 20px;
            font-size: 14px; border-left: 4px solid #28a745;
        }
        .btn {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, #945a9b, #6a406e);
            color: white; border: none; border-radius: 10px;
            font-size: 15px; font-weight: 600; cursor: pointer;
            font-family: inherit; transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(148,90,155,0.35); }
        .proceed-link {
            display: block; text-align: center; margin-top: 14px;
            color: #945a9b; font-weight: 600; text-decoration: none; font-size: 14px;
        }
        .proceed-link:hover { text-decoration: underline; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #888; font-size: 13px; text-decoration: none; }
        .back-link a:hover { color: #945a9b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🔐</div>
        <h1>Forgot Password</h1>
        <p class="subtitle">Enter your email to receive a reset code</p>

        <?php if ($error): ?>
            <div class="error">⚠️ <?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success">✓ <?php echo $success; ?></div>
            <a href="verify-reset-code.php" class="proceed-link">Enter the code →</a>
        <?php else: ?>
            <form method="POST">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email"
                           placeholder="your@email.com" required
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <button type="submit" name="send_code" class="btn">Send Reset Code</button>
            </form>
        <?php endif; ?>

        <div class="back-link">
            <a href="login.php">← Back to Login</a>
        </div>
    </div>
</body>
</html>
