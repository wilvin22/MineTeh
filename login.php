<?php
session_start();
include 'database/supabase.php';

$error_message = '';
$success_message = '';

// ----------------------- SIGNUP -----------------------
if (isset($_POST['create-account'])) {
    $username = trim($_POST['username']);
    $first_name = trim($_POST['first-name']);
    $last_name = trim($_POST['last-name']);
    $email = trim($_POST['signup-email']);
    $password = $_POST['signup-password'];
    $confirm_password = isset($_POST['confirm-password']) ? $_POST['confirm-password'] : '';
    
    // Validation
    $errors = [];
    
    // Password match validation
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    // Username validation: at least 6 characters and contains a number
    if (strlen($username) < 6) {
        $errors[] = "Username must be at least 6 characters long";
    }
    if (!preg_match('/\d/', $username)) {
        $errors[] = "Username must contain at least one number";
    }
    
    // Name validation
    if (strlen($first_name) < 2) {
        $errors[] = "First name must be at least 2 characters";
    }
    if (preg_match('/\d/', $first_name)) {
        $errors[] = "First name cannot contain numbers";
    }
    if (strlen($last_name) < 2) {
        $errors[] = "Last name must be at least 2 characters";
    }
    if (preg_match('/\d/', $last_name)) {
        $errors[] = "Last name cannot contain numbers";
    }
    
    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Password validation: 6-20 chars, 1 uppercase, 1 number, 1 special char
    if (strlen($password) < 6 || strlen($password) > 20) {
        $errors[] = "Password must be between 6 and 20 characters";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    }
    if (!preg_match('/\d/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = "Password must contain at least one special character";
    }
    
    if (empty($errors)) {
        // Check if username already exists
        $username_check = $supabase->select('accounts', 'account_id', ['username' => $username]);
        
        // Check if email already exists
        $email_check = $supabase->select('accounts', 'account_id', ['email' => $email]);

        if (!empty($username_check)) {
            $error_message = "Username already taken!";
        } elseif (!empty($email_check)) {
            $error_message = "Email already registered!";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $result = $supabase->insert('accounts', [
                'username' => $username,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'email' => $email,
                'password_hash' => $hashed_password,
                'is_admin' => false
            ]);
            
            if ($result && !empty($result[0])) {
                header("Location: login.php?signup=success");
                exit;
            } else {
                $error_message = "Error creating account. Please try again.";
            }
        }
    } else {
        $error_message = implode("<br>", $errors);
    }
}

// ----------------------- LOGIN -----------------------
if (isset($_POST['log-in'])) {
    $login_input = trim($_POST['login-email']);
    $password = $_POST['login-password'];
    
    if (empty($login_input) || empty($password)) {
        $error_message = "Please enter both email/username and password";
    } else {
        try {
            // Try to find user by email or username
            $user = $supabase->customQuery('accounts', '*', 'or=(email.eq.' . urlencode($login_input) . ',username.eq.' . urlencode($login_input) . ')&limit=1');

            if (!empty($user)) {
                $user = $user[0];

                if (password_verify($password, $user['password_hash'])) {
                    // Check user status
                    $user_status = isset($user['user_status']) ? $user['user_status'] : 'active';
                    
                    if ($user_status === 'banned') {
                        $reason = isset($user['status_reason']) ? $user['status_reason'] : 'No reason provided';
                        $error_message = "Your account has been banned. Reason: " . htmlspecialchars($reason);
                    } elseif ($user_status === 'restricted') {
                        // Check if restriction has expired
                        $restriction_until = isset($user['restriction_until']) ? $user['restriction_until'] : null;
                        if ($restriction_until && strtotime($restriction_until) <= time()) {
                            // Restriction expired, reactivate user
                            $supabase->update('accounts', [
                                'user_status' => 'active',
                                'restriction_until' => null,
                                'status_reason' => null
                            ], ['account_id' => $user['account_id']]);
                            $user_status = 'active';
                        }
                        
                        // Allow login for restricted users
                        $_SESSION['user_id'] = $user['account_id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['is_admin'] = $user['is_admin'] ?? false;
                        $_SESSION['is_rider'] = $user['is_rider'] ?? false;
                        $_SESSION['user_status'] = $user_status;
                        
                        // redirect based on role
                        if ($user['is_admin']) {
                            header("Location: admin-dashboard.php");
                        } elseif ($user['is_rider']) {
                            header("Location: rider/dashboard.php");
                        } else {
                            header("Location: home/homepage.php");
                        }
                        exit;
                    } else {
                        // Active user, allow login
                        $_SESSION['user_id'] = $user['account_id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['is_admin'] = $user['is_admin'] ?? false;
                        $_SESSION['is_rider'] = $user['is_rider'] ?? false;
                        $_SESSION['user_status'] = $user_status;
                        
                        // redirect based on role
                        if ($user['is_admin']) {
                            header("Location: admin-dashboard.php");
                        } elseif ($user['is_rider']) {
                            header("Location: rider/dashboard.php");
                        } else {
                            header("Location: home/homepage.php");
                        }
                        exit;
                    }

                } else {
                    $error_message = "Incorrect password! Please check your password and try again.";
                }
            } else {
                $error_message = "Account not found with email/username: " . htmlspecialchars($login_input) . ". Please check your credentials or sign up.";
            }
        } catch (Exception $e) {
            $error_message = "Login error: " . $e->getMessage();
        }
    }
}

// Check for success message
if (isset($_GET['signup']) && $_GET['signup'] === 'success') {
    $success_message = "Account created successfully! Please log in.";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MineTeh Login</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-attachment: fixed;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
        }
        
        .page-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        body::before,
        body::after {
            content: '';
            position: absolute;
            border-radius: 50%;
            opacity: 0.05;
        }
        
        body::before {
            width: 500px;
            height: 500px;
            background: white;
            top: -200px;
            right: -100px;
        }
        
        body::after {
            width: 400px;
            height: 400px;
            background: white;
            bottom: -150px;
            left: -100px;
        }

        #content {
            width: 100%;
            max-width: 480px;
            position: relative;
            z-index: 1;
            margin: 0 auto;
        }
        
        #content.wide {
            max-width: 900px;
        }

        #header {
            text-align: center;
            margin-bottom: 25px;
            color: white;
        }
        
        .logo {
            margin-bottom: 15px;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }

        #header h2 {
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 8px;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }

        #header p {
            font-size: 16px;
            opacity: 0.95;
        }

        #login-container,
        #signup-container {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            animation: slideUp 0.4s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        #signup-container {
            display: none;
        }
        
        #signup-container .form-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        
        #signup-container .form-column {
            display: flex;
            flex-direction: column;
        }
        
        #signup-container .form-actions {
            grid-column: 1 / -1;
            margin-top: 5px;
        }
        
        .form-title {
            font-size: 24px;
            font-weight: 700;
            color: #333;
            margin-bottom: 20px;
            text-align: center;
        }
        
        label {
            display: block;
            font-weight: 600;
            font-size: 13px;
            color: #555;
            margin-bottom: 6px;
        }

        input {
            width: 100%;
            padding: 12px 14px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            margin-bottom: 6px;
            background: #f8f9fa;
            outline: none;
            transition: all 0.3s ease;
            font-family: inherit;
        }
        
        input:focus {
            border-color: #945a9b;
            background: white;
            box-shadow: 0 0 0 4px rgba(148, 90, 155, 0.1);
            transform: translateY(-1px);
        }
        
        input.invalid {
            border-color: #dc3545;
            background: #fff5f5;
        }
        
        input.valid {
            border-color: #28a745;
            background: #f0fff4;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: inherit;
            margin-top: 8px;
        }

        #log-in,
        #create-account {
            background: linear-gradient(135deg, #945a9b, #6a406e);
            color: white;
            box-shadow: 0 4px 15px rgba(148, 90, 155, 0.3);
        }

        #log-in:hover,
        #create-account:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(148, 90, 155, 0.4);
        }
        
        #log-in:disabled,
        #create-account:disabled {
            opacity: 0.7;
            cursor: not-allowed;
            transform: none;
        }

        #log-in:active,
        #create-account:active {
            transform: translateY(0);
        }

        .signup-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }
        
        .signup-link a {
            color: #945a9b;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
        }
        
        .signup-link a:hover {
            color: #6a406e;
            text-decoration: underline;
        }
        
        #back-to-login {
            background: transparent;
            color: #945a9b;
            border: 2px solid #945a9b;
            margin-top: 12px;
        }

        #back-to-login:hover {
            background: #945a9b;
            color: white;
        }
        
        .forgot-password {
            text-align: right;
            margin-top: -4px;
            margin-bottom: 16px;
        }
        
        .forgot-password a {
            color: #888;
            font-size: 13px;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        
        .forgot-password a:hover {
            color: #945a9b;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 16px;
            font-size: 14px;
            color: #555;
        }
        
        .remember-me input[type="checkbox"] {
            width: auto;
            margin-right: 8px;
            margin-bottom: 0;
            cursor: pointer;
        }
        
        .terms-agreement {
            display: flex;
            align-items: flex-start;
            margin-bottom: 16px;
            font-size: 12px;
            color: #555;
        }
        
        .terms-agreement input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
            margin-top: 2px;
            margin-bottom: 0;
            cursor: pointer;
            flex-shrink: 0;
        }
        
        .terms-agreement label {
            margin: 0;
            font-weight: normal;
            cursor: pointer;
        }
        
        .terms-agreement a {
            color: #945a9b;
            text-decoration: none;
        }
        
        .terms-agreement a:hover {
            text-decoration: underline;
        }
        
        .password-strength {
            height: 3px;
            background: #e0e0e0;
            border-radius: 2px;
            margin-top: 6px;
            margin-bottom: 6px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
            border-radius: 2px;
        }
        
        .password-strength-bar.weak {
            width: 33%;
            background: #dc3545;
        }
        
        .password-strength-bar.medium {
            width: 66%;
            background: #ffc107;
        }
        
        .password-strength-bar.strong {
            width: 100%;
            background: #28a745;
        }
        
        .password-requirements {
            font-size: 11px;
            color: #888;
            margin-top: 6px;
            margin-bottom: 8px;
        }
        
        .password-requirements div {
            margin: 3px 0;
            transition: color 0.2s ease;
        }
        
        .password-requirements div.met {
            color: #28a745;
        }
        
        .password-requirements div.unmet {
            color: #dc3545;
        }
        
        .spinner {
            display: inline-block;
            width: 14px;
            height: 14px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
            margin-left: 8px;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .error-message {
            background: linear-gradient(135deg, #ff6b6b, #ee5a6f);
            color: white;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(255, 107, 107, 0.3);
            animation: shake 0.5s ease;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        .success-message {
            background: linear-gradient(135deg, #51cf66, #37b24d);
            color: white;
            padding: 14px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 14px;
            box-shadow: 0 4px 12px rgba(81, 207, 102, 0.3);
        }
        
        .validation-hint {
            font-size: 11px;
            color: #888;
            margin-bottom: 12px;
            padding-left: 4px;
        }
        
        .field-error {
            font-size: 12px;
            color: #dc3545;
            margin-bottom: 8px;
            padding-left: 4px;
            display: none;
            font-weight: 500;
        }
        
        .field-error.show {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .password-wrapper {
            position: relative;
            margin-bottom: 8px;
        }
        
        .password-wrapper input {
            padding-right: 50px;
            margin-bottom: 0;
        }
        
        .toggle-password {
            position: absolute;
            right: 4px;
            top: 50%;
            transform: translateY(-50%);
            background: transparent;
            border: none;
            cursor: pointer;
            font-size: 20px;
            padding: 8px 12px;
            width: auto;
            margin: 0;
            color: #888;
            transition: all 0.2s ease;
        }
        
        .toggle-password:hover {
            color: #945a9b;
            background: transparent;
            transform: translateY(-50%) scale(1.1);
            box-shadow: none;
        }
        
        .form-group {
            margin-bottom: 16px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .form-row .form-group {
            margin-bottom: 0;
        }
        
        .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }
        
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e0e0e0;
        }
        
        .divider span {
            background: white;
            padding: 0 15px;
            position: relative;
            color: #999;
            font-size: 14px;
        }

        @media (max-width: 768px) {
            #content.wide {
                max-width: 480px;
            }
            
            #signup-container .form-content {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            #signup-container .form-actions {
                grid-column: 1;
            }
        }
        
        @media (max-width: 480px) {
            #content {
                max-width: 100%;
            }
            
            #login-container,
            #signup-container {
                padding: 30px 25px;
            }
            
            #header h2 {
                font-size: 32px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 0;
            }
            
            .form-row .form-group {
                margin-bottom: 20px;
            }
        }
    </style>
    
    <!-- Responsive CSS -->
    <link rel="stylesheet" href="css/responsive.css">
</head>

<body>
    <div class="page-wrapper">
        <div id="content">
        <div id="header">
            <div class="logo">
                <svg width="60" height="60" viewBox="0 0 60 60" fill="none">
                    <circle cx="30" cy="30" r="28" fill="white" opacity="0.2"/>
                    <path d="M30 10L40 25H35V40H25V25H20L30 10Z" fill="white"/>
                    <rect x="20" y="42" width="20" height="6" rx="2" fill="white"/>
                </svg>
            </div>
            <h2>Welcome Back!</h2>
            <p>Please login to your account.</p>
        </div>

        <!-- LOGIN FORM -->
        <div id="login-container">
            <div class="form-title">Welcome Back</div>
            
            <?php if (!empty($error_message) && !isset($_POST['create-account'])): ?>
                <div class="error-message">⚠️ <?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="success-message">✓ <?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" id="login-form">
                <div class="form-group">
                    <label for="login-email">Email or Username</label>
                    <input type="text" id="login-email" name="login-email" placeholder="Enter your email or username" required>
                </div>

                <div class="form-group">
                    <label for="login-password">Password</label>
                    <div class="password-wrapper">
                        <input type="password" id="login-password" name="login-password" placeholder="Enter your password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword('login-password', this)">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                    <div class="forgot-password">
                        <a href="forgot-password.php">Forgot Password?</a>
                    </div>
                </div>

                <div class="remember-me">
                    <input type="checkbox" id="remember-me" name="remember-me">
                    <label for="remember-me">Remember me</label>
                </div>

                <button type="submit" name="log-in" id="log-in">Log in</button>
            </form>
            
            <div class="signup-link">
                Don't have an account? <a href="#" id="sign-up">Sign up</a>
            </div>
        </div>

        <!-- SIGNUP FORM -->
        <div id="signup-container">
            <div class="form-title">Create Account</div>
            
            <?php if (!empty($error_message) && isset($_POST['create-account'])): ?>
                <div class="error-message">⚠️ <?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" id="signup-form">
                <div class="form-content">
                    <!-- LEFT COLUMN -->
                    <div class="form-column">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" placeholder="Choose a username" required minlength="6" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                            <div class="field-error" id="username-error"></div>
                            <div class="validation-hint">At least 6 characters with a number</div>
                        </div>

                        <div class="form-group">
                            <label for="first-name">First Name</label>
                            <input type="text" id="first-name" name="first-name" placeholder="Your first name" required minlength="2" value="<?php echo isset($_POST['first-name']) ? htmlspecialchars($_POST['first-name']) : ''; ?>">
                            <div class="field-error" id="firstname-error"></div>
                        </div>

                        <div class="form-group">
                            <label for="last-name">Last Name</label>
                            <input type="text" id="last-name" name="last-name" placeholder="Your last name" required minlength="2" value="<?php echo isset($_POST['last-name']) ? htmlspecialchars($_POST['last-name']) : ''; ?>">
                            <div class="field-error" id="lastname-error"></div>
                        </div>

                        <div class="form-group">
                            <label for="signup-email">Email</label>
                            <input type="email" id="signup-email" name="signup-email" placeholder="your@email.com" required value="<?php echo isset($_POST['signup-email']) ? htmlspecialchars($_POST['signup-email']) : ''; ?>">
                            <div class="field-error" id="email-error"></div>
                        </div>
                    </div>

                    <!-- RIGHT COLUMN -->
                    <div class="form-column">
                        <div class="form-group">
                            <label for="signup-password">Password</label>
                            <div class="password-wrapper">
                                <input type="password" id="signup-password" name="signup-password" placeholder="Create a password" required minlength="6" maxlength="20">
                                <button type="button" class="toggle-password" onclick="togglePassword('signup-password', this)">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                            <div class="password-strength">
                                <div class="password-strength-bar" id="strength-bar"></div>
                            </div>
                            <div class="password-requirements">
                                <div id="req-length">✗ 6-20 characters</div>
                                <div id="req-uppercase">✗ 1 uppercase letter</div>
                                <div id="req-number">✗ 1 number</div>
                                <div id="req-special">✗ 1 special character (!@#$%^&*)</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="confirm-password">Confirm Password</label>
                            <div class="password-wrapper">
                                <input type="password" id="confirm-password" name="confirm-password" placeholder="Re-enter your password" required minlength="6" maxlength="20">
                                <button type="button" class="toggle-password" onclick="togglePassword('confirm-password', this)">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                            <div class="field-error" id="confirm-password-error"></div>
                        </div>
                    </div>

                    <!-- FULL WIDTH ACTIONS -->
                    <div class="form-actions">
                        <div class="terms-agreement">
                            <input type="checkbox" id="terms-checkbox" name="terms-checkbox" required>
                            <label for="terms-checkbox">I agree to the <a href="#" onclick="alert('Terms & Conditions coming soon!'); return false;">Terms & Conditions</a> and <a href="#" onclick="alert('Privacy Policy coming soon!'); return false;">Privacy Policy</a></label>
                        </div>

                        <button type="submit" name="create-account" id="create-account">Create Account</button>
                        <button type="button" id="back-to-login">Back to Login</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        console.log('=== LOGIN PAGE DEBUG ===');
        console.log('Login form exists:', document.getElementById('login-form') !== null);
        console.log('Signup form exists:', document.getElementById('signup-form') !== null);
        
        // TEMPORARILY DISABLE ALL FORM VALIDATION - JUST LET FORMS SUBMIT
        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            console.log('Login form found, adding listener');
            loginForm.addEventListener('submit', function(e) {
                console.log('LOGIN FORM SUBMITTING - NOT PREVENTING');
                // Don't prevent, just let it submit
            });
        } else {
            console.error('Login form NOT found!');
        }
        
        const signupForm = document.getElementById('signup-form');
        if (signupForm) {
            console.log('Signup form found, adding listener');
            signupForm.addEventListener('submit', function(e) {
                console.log('SIGNUP FORM SUBMITTING - NOT PREVENTING');
                // Don't prevent, just let it submit
            });
        } else {
            console.log('Signup form not found (probably hidden)');
        }
        
        // Toggle password visibility
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                button.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';
            } else {
                input.type = 'password';
                button.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
            }
        }
        
        // Show signup form if there was a signup error
        <?php if (!empty($error_message) && isset($_POST['create-account'])): ?>
        document.getElementById('signup-container').style.display = 'block';
        document.getElementById('login-container').style.display = 'none';
        document.getElementById('content').classList.add('wide');
        <?php endif; ?>
        
        document.getElementById('sign-up').onclick = function(e) {
            e.preventDefault();
            console.log('Switching to signup form');
            document.getElementById('signup-container').style.display = 'block';
            document.getElementById('login-container').style.display = 'none';
            document.getElementById('content').classList.add('wide');
            
            // Re-check if signup form exists after showing it
            const signupForm = document.getElementById('signup-form');
            console.log('Signup form now exists:', signupForm !== null);
        };
        
        document.getElementById('back-to-login').onclick = function() {
            console.log('Switching back to login form');
            document.getElementById('login-container').style.display = 'block';
            document.getElementById('signup-container').style.display = 'none';
            document.getElementById('content').classList.remove('wide');
        };
        
        // Form submission validation and loading state for signup
        if (signupForm) {
            signupForm.addEventListener('submit', function(e) {
                console.log('Signup form submitting...');
                
                const termsCheckbox = document.getElementById('terms-checkbox');
                if (!termsCheckbox.checked) {
                    e.preventDefault();
                    alert('Please agree to the Terms & Conditions and Privacy Policy');
                    return false;
                }
                
                const username = document.getElementById('username').value;
                const firstName = document.getElementById('first-name').value;
                const lastName = document.getElementById('last-name').value;
                const email = document.getElementById('signup-email').value;
                const password = document.getElementById('signup-password').value;
                const confirmPassword = document.getElementById('confirm-password').value;
                
                let isValid = true;
                let errors = [];
                
                // Validate all fields
                if (username.length < 6) {
                    errors.push('Username must be at least 6 characters');
                    showError('username', 'username-error', 'Username must be at least 6 characters');
                    isValid = false;
                } else if (!/\d/.test(username)) {
                    errors.push('Username must contain at least one number');
                    showError('username', 'username-error', 'Username must contain at least one number');
                    isValid = false;
                }
                
                if (firstName.length < 2) {
                    errors.push('First name must be at least 2 characters');
                    showError('first-name', 'firstname-error', 'First name must be at least 2 characters');
                    isValid = false;
                } else if (/\d/.test(firstName)) {
                    errors.push('First name cannot contain numbers');
                    showError('first-name', 'firstname-error', 'First name cannot contain numbers');
                    isValid = false;
                }
                
                if (lastName.length < 2) {
                    errors.push('Last name must be at least 2 characters');
                    showError('last-name', 'lastname-error', 'Last name must be at least 2 characters');
                    isValid = false;
                } else if (/\d/.test(lastName)) {
                    errors.push('Last name cannot contain numbers');
                    showError('last-name', 'lastname-error', 'Last name cannot contain numbers');
                    isValid = false;
                }
                
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    errors.push('Please enter a valid email address');
                    showError('signup-email', 'email-error', 'Please enter a valid email address');
                    isValid = false;
                }
                
                if (password.length < 6 || password.length > 20) {
                    errors.push('Password must be 6-20 characters');
                    showError('signup-password', 'password-error', 'Password must be 6-20 characters');
                    isValid = false;
                } else if (!/[A-Z]/.test(password)) {
                    errors.push('Password must contain at least one uppercase letter');
                    showError('signup-password', 'password-error', 'Password must contain at least one uppercase letter');
                    isValid = false;
                } else if (!/\d/.test(password)) {
                    errors.push('Password must contain at least one number');
                    showError('signup-password', 'password-error', 'Password must contain at least one number');
                    isValid = false;
                } else if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                    errors.push('Password must contain at least one special character');
                    showError('signup-password', 'password-error', 'Password must contain at least one special character');
                    isValid = false;
                }
                
                if (password !== confirmPassword) {
                    errors.push('Passwords do not match');
                    showError('confirm-password', 'confirm-password-error', 'Passwords do not match');
                    isValid = false;
                }
                
                if (!isValid) {
                    console.log('Validation failed:', errors);
                    e.preventDefault();
                    return false;
                }
                
                console.log('Validation passed, submitting form...');
                // If validation passes, show loading state
                const btn = document.getElementById('create-account');
                btn.disabled = true;
                btn.innerHTML = 'Creating Account...<span class="spinner"></span>';
                // Let form submit naturally
                return true;
            });
        }
        
        // Real-time validation functions
        function showError(inputId, errorId, message) {
            const input = document.getElementById(inputId);
            const error = document.getElementById(errorId);
            input.classList.add('invalid');
            input.classList.remove('valid');
            error.textContent = '✗ ' + message;
            error.classList.add('show');
            return false;
        }
        
        function showSuccess(inputId, errorId) {
            const input = document.getElementById(inputId);
            const error = document.getElementById(errorId);
            input.classList.add('valid');
            input.classList.remove('invalid');
            error.classList.remove('show');
            return true;
        }
        
        function clearValidation(inputId, errorId) {
            const input = document.getElementById(inputId);
            const error = document.getElementById(errorId);
            input.classList.remove('valid', 'invalid');
            error.classList.remove('show');
        }
        
        // Username validation
        document.getElementById('username').addEventListener('input', function() {
            const value = this.value;
            if (value.length === 0) {
                clearValidation('username', 'username-error');
            } else if (value.length < 6) {
                showError('username', 'username-error', 'Username must be at least 6 characters (currently ' + value.length + ')');
            } else if (!/\d/.test(value)) {
                showError('username', 'username-error', 'Username must contain at least one number');
            } else {
                showSuccess('username', 'username-error');
            }
        });
        
        // First name validation
        document.getElementById('first-name').addEventListener('input', function() {
            const value = this.value;
            if (value.length === 0) {
                clearValidation('first-name', 'firstname-error');
            } else if (value.length < 2) {
                showError('first-name', 'firstname-error', 'First name must be at least 2 characters');
            } else if (/\d/.test(value)) {
                showError('first-name', 'firstname-error', 'First name cannot contain numbers');
            } else {
                showSuccess('first-name', 'firstname-error');
            }
        });
        
        // Last name validation
        document.getElementById('last-name').addEventListener('input', function() {
            const value = this.value;
            if (value.length === 0) {
                clearValidation('last-name', 'lastname-error');
            } else if (value.length < 2) {
                showError('last-name', 'lastname-error', 'Last name must be at least 2 characters');
            } else if (/\d/.test(value)) {
                showError('last-name', 'lastname-error', 'Last name cannot contain numbers');
            } else {
                showSuccess('last-name', 'lastname-error');
            }
        });
        
        // Email validation
        document.getElementById('signup-email').addEventListener('input', function() {
            const value = this.value;
            if (value.length === 0) {
                clearValidation('signup-email', 'email-error');
            } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                showError('signup-email', 'email-error', 'Please enter a valid email address (e.g., user@example.com)');
            } else {
                showSuccess('signup-email', 'email-error');
            }
        });
        
        // Confirm password validation
        document.getElementById('confirm-password').addEventListener('input', function() {
            const password = document.getElementById('signup-password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword.length === 0) {
                clearValidation('confirm-password', 'confirm-password-error');
            } else if (password !== confirmPassword) {
                showError('confirm-password', 'confirm-password-error', 'Passwords do not match');
            } else {
                showSuccess('confirm-password', 'confirm-password-error');
            }
        });
        
        // Also validate confirm password when main password changes
        document.getElementById('signup-password').addEventListener('input', function() {
            const confirmPassword = document.getElementById('confirm-password').value;
            if (confirmPassword.length > 0) {
                if (this.value !== confirmPassword) {
                    showError('confirm-password', 'confirm-password-error', 'Passwords do not match');
                } else {
                    showSuccess('confirm-password', 'confirm-password-error');
                }
            }
            
            // Password strength and requirements validation
            const value = this.value;
            const strengthBar = document.getElementById('strength-bar');
            const reqLength = document.getElementById('req-length');
            const reqUppercase = document.getElementById('req-uppercase');
            const reqNumber = document.getElementById('req-number');
            const reqSpecial = document.getElementById('req-special');
            
            if (value.length === 0) {
                strengthBar.className = 'password-strength-bar';
                reqLength.className = '';
                reqUppercase.className = '';
                reqNumber.className = '';
                reqSpecial.className = '';
                reqLength.textContent = '✗ 6-20 characters';
                reqUppercase.textContent = '✗ 1 uppercase letter';
                reqNumber.textContent = '✗ 1 number';
                reqSpecial.textContent = '✗ 1 special character (!@#$%^&*)';
                return;
            }
            
            let strength = 0;
            
            // Check length
            if (value.length >= 6 && value.length <= 20) {
                reqLength.textContent = '✓ 6-20 characters';
                reqLength.className = 'met';
                strength++;
            } else {
                reqLength.textContent = '✗ 6-20 characters';
                reqLength.className = 'unmet';
            }
            
            // Check uppercase
            if (/[A-Z]/.test(value)) {
                reqUppercase.textContent = '✓ 1 uppercase letter';
                reqUppercase.className = 'met';
                strength++;
            } else {
                reqUppercase.textContent = '✗ 1 uppercase letter';
                reqUppercase.className = 'unmet';
            }
            
            // Check number
            if (/\d/.test(value)) {
                reqNumber.textContent = '✓ 1 number';
                reqNumber.className = 'met';
                strength++;
            } else {
                reqNumber.textContent = '✗ 1 number';
                reqNumber.className = 'unmet';
            }
            
            // Check special character
            if (/[!@#$%^&*(),.?":{}|<>]/.test(value)) {
                reqSpecial.textContent = '✓ 1 special character (!@#$%^&*)';
                reqSpecial.className = 'met';
                strength++;
            } else {
                reqSpecial.textContent = '✗ 1 special character (!@#$%^&*)';
                reqSpecial.className = 'unmet';
            }
            
            // Update strength bar
            strengthBar.className = 'password-strength-bar';
            if (strength <= 2) {
                strengthBar.classList.add('weak');
            } else if (strength === 3) {
                strengthBar.classList.add('medium');
            } else if (strength === 4) {
                strengthBar.classList.add('strong');
            }
        });
    </script>
    </div>
    
    <!-- Responsive JavaScript -->
    <script src="js/responsive.js"></script>
</body>
</html>
