<?php
session_start();
include 'database/supabase.php';

if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot-password.php");
    exit;
}

$error = '';

if (isset($_POST['verify_code'])) {
    $code  = trim($_POST['code']);
    $email = $_SESSION['reset_email'];

    $reset = $supabase->customQuery('password_resets', '*',
        'email=eq.' . urlencode($email) .
        '&reset_code=eq.' . urlencode($code) .
        '&used=eq.false' .
        '&order=created_at.desc' .
        '&limit=1'
    );

    if (!empty($reset) && is_array($reset)) {
        $reset = $reset[0];
        if (strtotime($reset['expires_at']) > time()) {
            $_SESSION['reset_code_verified'] = true;
            $_SESSION['reset_id']            = $reset['id'];
            header("Location: reset-password.php");
            exit;
        } else {
            $error = "This code has expired. Please request a new one.";
        }
    } else {
        $error = "Invalid code. Please check and try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enter Reset Code - MineTeh</title>
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
        .subtitle { text-align: center; color: #666; margin-bottom: 24px; font-size: 14px; }
        .info-box {
            background: #f0e8f4; border-left: 4px solid #945a9b;
            padding: 13px 16px; border-radius: 8px; margin-bottom: 22px;
            font-size: 14px; color: #555;
        }
        .info-box strong { color: #333; }

        <?php if (isset($_SESSION['temp_reset_code'])): ?>
        .dev-box {
            background: #fff8e1; border: 2px dashed #ffc107;
            border-radius: 10px; padding: 16px; text-align: center;
            margin-bottom: 20px;
        }
        .dev-code {
            font-size: 34px; font-weight: bold; color: #945a9b;
            letter-spacing: 8px; font-family: monospace; display: block;
            margin: 8px 0;
        }
        .dev-note { font-size: 12px; color: #888; }
        <?php endif; ?>

        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; font-size: 14px; }
        input[type="text"] {
            width: 100%; padding: 14px;
            border: 2px solid #e0e0e0; border-radius: 10px;
            font-size: 28px; text-align: center;
            letter-spacing: 10px; font-family: monospace;
            transition: border-color 0.2s;
        }
        input[type="text"]:focus { outline: none; border-color: #945a9b; }
        .error {
            background: #f8d7da; color: #721c24;
            padding: 13px 16px; border-radius: 10px; margin-bottom: 20px;
            font-size: 14px; border-left: 4px solid #dc3545;
        }
        .btn {
            width: 100%; padding: 13px;
            background: linear-gradient(135deg, #945a9b, #6a406e);
            color: white; border: none; border-radius: 10px;
            font-size: 15px; font-weight: 600; cursor: pointer;
            font-family: inherit; transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(148,90,155,0.35); }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #888; font-size: 13px; text-decoration: none; }
        .back-link a:hover { color: #945a9b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">📧</div>
        <h1>Enter Reset Code</h1>
        <p class="subtitle">We sent a 6-digit code to your email</p>

        <div class="info-box">
            Code sent to: <strong><?php echo htmlspecialchars($_SESSION['reset_email']); ?></strong>
        </div>

        <?php if (isset($_SESSION['temp_reset_code'])): ?>
            <div class="dev-box">
                <div style="font-size:13px;font-weight:600;color:#856404;margin-bottom:4px;">⚠️ Email delivery failed — your code is:</div>
                <span class="dev-code"><?php echo $_SESSION['temp_reset_code']; ?></span>
                <div class="dev-note">Configure Brevo API key in send_email.php to enable real emails.</div>
            </div>
            <?php unset($_SESSION['temp_reset_code']); ?>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="error">⚠️ <?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="code">6-Digit Code</label>
                <input type="text" id="code" name="code"
                       placeholder="000000" maxlength="6"
                       pattern="[0-9]{6}" inputmode="numeric"
                       required autofocus autocomplete="one-time-code">
            </div>
            <button type="submit" name="verify_code" class="btn">Verify Code</button>
        </form>

        <div class="back-link">
            <a href="forgot-password.php">← Request a new code</a>
        </div>
    </div>
</body>
</html>
