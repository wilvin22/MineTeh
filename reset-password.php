<?php
session_start();
include 'database/supabase.php';

if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_code_verified'])) {
    header("Location: forgot-password.php");
    exit;
}

$error   = '';
$success = '';

if (isset($_POST['reset_password'])) {
    $new_password     = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 6 || strlen($new_password) > 20) {
        $error = "Password must be between 6 and 20 characters.";
    } elseif (!preg_match('/[A-Z]/', $new_password)) {
        $error = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/\d/', $new_password)) {
        $error = "Password must contain at least one number.";
    } elseif (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $new_password)) {
        $error = "Password must contain at least one special character.";
    } else {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

        $updated = $supabase->update('accounts', [
            'password_hash' => $password_hash
        ], ['email' => $_SESSION['reset_email']]);

        if ($updated !== false) {
            // Mark reset code as used
            if (isset($_SESSION['reset_id'])) {
                $supabase->update('password_resets', ['used' => true], ['id' => $_SESSION['reset_id']]);
            }
            unset($_SESSION['reset_email'], $_SESSION['reset_code_verified'], $_SESSION['reset_id']);
            $success = "Password reset successfully! Redirecting to login...";
            header("refresh:2;url=login.php?reset=success");
        } else {
            $error = "Failed to update password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - MineTeh</title>
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
        .form-group { margin-bottom: 18px; }
        label { display: block; margin-bottom: 8px; color: #333; font-weight: 600; font-size: 14px; }
        .pw-wrap { position: relative; }
        .pw-wrap input {
            width: 100%; padding: 13px 44px 13px 14px;
            border: 2px solid #e0e0e0; border-radius: 10px;
            font-size: 15px; font-family: inherit;
            transition: border-color 0.2s;
        }
        .pw-wrap input:focus { outline: none; border-color: #945a9b; }
        .toggle-pw {
            position: absolute; right: 4px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer;
            padding: 8px 10px; color: #888; font-size: 18px;
            transition: color 0.2s;
        }
        .toggle-pw:hover { color: #945a9b; }
        .requirements {
            font-size: 12px; color: #888; margin-top: 8px; line-height: 1.7;
        }
        .requirements div { transition: color 0.2s; }
        .requirements div.met   { color: #28a745; }
        .requirements div.unmet { color: #dc3545; }
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
            width: 100%; padding: 13px; margin-top: 6px;
            background: linear-gradient(135deg, #945a9b, #6a406e);
            color: white; border: none; border-radius: 10px;
            font-size: 15px; font-weight: 600; cursor: pointer;
            font-family: inherit; transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 14px rgba(148,90,155,0.35); }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo">🔑</div>
        <h1>Reset Password</h1>
        <p class="subtitle">Choose a new password for your account</p>

        <?php if ($error): ?>
            <div class="error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success">✓ <?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST">
            <div class="form-group">
                <label for="new_password">New Password</label>
                <div class="pw-wrap">
                    <input type="password" id="new_password" name="new_password"
                           placeholder="Create a new password" required
                           minlength="6" maxlength="20"
                           oninput="checkStrength(this.value)">
                    <button type="button" class="toggle-pw" onclick="togglePw('new_password')">👁</button>
                </div>
                <div class="requirements" id="reqs">
                    <div id="r-len">✗ 6–20 characters</div>
                    <div id="r-upper">✗ 1 uppercase letter</div>
                    <div id="r-num">✗ 1 number</div>
                    <div id="r-special">✗ 1 special character (!@#$%^&*)</div>
                </div>
            </div>
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="pw-wrap">
                    <input type="password" id="confirm_password" name="confirm_password"
                           placeholder="Re-enter your password" required
                           minlength="6" maxlength="20">
                    <button type="button" class="toggle-pw" onclick="togglePw('confirm_password')">👁</button>
                </div>
            </div>
            <button type="submit" name="reset_password" class="btn">Reset Password</button>
        </form>
        <?php endif; ?>
    </div>

    <script>
    function togglePw(id) {
        const el = document.getElementById(id);
        el.type = el.type === 'password' ? 'text' : 'password';
    }
    function checkStrength(val) {
        const set = (id, met) => {
            const el = document.getElementById(id);
            el.classList.toggle('met',   met);
            el.classList.toggle('unmet', !met);
            el.textContent = (met ? '✓ ' : '✗ ') + el.textContent.slice(2);
        };
        set('r-len',     val.length >= 6 && val.length <= 20);
        set('r-upper',   /[A-Z]/.test(val));
        set('r-num',     /\d/.test(val));
        set('r-special', /[!@#$%^&*(),.?":{}|<>]/.test(val));
    }
    </script>
</body>
</html>
