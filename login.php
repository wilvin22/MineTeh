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
    
    // Validation
    $errors = [];
    
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
    if (strlen($last_name) < 2) {
        $errors[] = "Last name must be at least 2 characters";
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
        // Try to find user by email or username
        $user = $supabase->customQuery('accounts', '*', 'or=(email.eq.' . urlencode($login_input) . ',username.eq.' . urlencode($login_input) . ')&limit=1');

        if (!empty($user)) {
            $user = $user[0];

            if (password_verify($password, $user['password_hash'])) {
                // Correct login, set session
                $_SESSION['user_id'] = $user['account_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['is_admin'] ?? false;
                
                // redirect based on role
                if ($user['is_admin']) {
                    header("Location: admin-dashboard.php");
                } else {
                    header("Location: home/homepage.php");
                }
                exit;

            } else {
                $error_message = "Incorrect password!";
            }
        } else {
            $error_message = "Account not found. Please check your email/username.";
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
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            background: #f0f0f0;
            text-align: center;
        }

        #content {
            margin: 5% 10%;
        }

        #header h2 {
            font-size: 36px;
            margin-bottom: 10px;
        }

        #header p {
            font-size: 18px;
            color: gray;
        }

        #login-container,
        #signup-container {
            width: 380px;
            background: #f8f8f8;
            margin: 40px auto;
            padding: 30px;
            border-radius: 18px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
            text-align: start;
            transition: 0.5s ease;
        }

        #signup-container {
            display: none;
        }
        
        label {
            font-weight: 700;
            font-size: 14px;
            color: #333;
        }

        input {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            margin-bottom: 20px;
            margin-top: 10px;
            background: #eeeded;
            outline: none;
        }
        
        input:focus {
            box-shadow: 0 0 6px rgba(0, 0, 0, 0.3);
            transition: 0.3s ease;
        }
        
        input.error {
            border: 2px solid #dc3545;
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.5s ease;
            background-color: #cfcfcf;
        }

        #log-in,
        #create-account {
            background: #ffffff;
            color: black;
            margin-top: 10px;
        }

        button:hover {
            background: #112d55;
            color: white;
            box-shadow: 0 5px 14px rgba(124, 124, 124, 0.4);
            transform: translateY(-1px);
            transition: 0.3s ease;
        }

        button:active {
            transform: translateY(2px);
        }

        #signup-container p {
            font-size: 12px;
            color: gray;
            margin-left: 5px;
            width: 100%;
            margin-top: -10px;
        }
        
        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #dc3545;
            font-size: 14px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid #28a745;
            font-size: 14px;
        }
        
        .validation-hint {
            font-size: 11px;
            color: #666;
            margin-top: -15px;
            margin-bottom: 15px;
        }
        
        .field-error {
            font-size: 12px;
            color: #dc3545;
            margin-top: -15px;
            margin-bottom: 10px;
            display: none;
        }
        
        .field-error.show {
            display: block;
        }
        
        input.invalid {
            border: 2px solid #dc3545;
            background: #fff5f5;
        }
        
        input.valid {
            border: 2px solid #28a745;
        }
    </style>
</head>

<body>
    <div id="content">
        <div id="header">
            <h2>Welcome Back!</h2>
            <p>Please login to your account.</p>
        </div>

        <!-- LOGIN FORM -->
        <div id="login-container">
            <?php if (!empty($error_message) && !isset($_POST['create-account'])): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="success-message"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" id="login-form">
                <label for="login-email">Email or Username</label>
                <input type="text" id="login-email" name="login-email" placeholder="Enter your email or username" required>

                <label for="login-password">Password</label>
                <input type="password" id="login-password" name="login-password" placeholder="Enter your password" required>

                <button type="submit" name="log-in" id="log-in">Log in</button><br><br>
            </form>
            <button id="sign-up">Sign up</button>
        </div>

        <!-- SIGNUP FORM -->
        <div id="signup-container">
            <?php if (!empty($error_message) && isset($_POST['create-account'])): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <form method="POST" action="login.php" id="signup-form">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required minlength="6">
                <div class="field-error" id="username-error"></div>
                <div class="validation-hint">Must be at least 6 characters and contain a number</div>

                <label for="first-name">First Name</label>
                <input type="text" id="first-name" name="first-name" placeholder="Enter your first name" required minlength="2">
                <div class="field-error" id="firstname-error"></div>

                <label for="last-name">Last Name</label>
                <input type="text" id="last-name" name="last-name" placeholder="Enter your last name" required minlength="2">
                <div class="field-error" id="lastname-error"></div>

                <label for="signup-email">Email</label>
                <input type="email" id="signup-email" name="signup-email" placeholder="Enter your email" required>
                <div class="field-error" id="email-error"></div>

                <label for="signup-password">Password</label>
                <input type="password" id="signup-password" name="signup-password" placeholder="Enter your password" required minlength="6" maxlength="20">
                <div class="field-error" id="password-error"></div>
                <div class="validation-hint">6-20 characters, 1 uppercase, 1 number, 1 special character (!@#$%^&*)</div>

                <button type="submit" name="create-account" id="create-account">Create Account</button><br><br>
                <button type="button" id="back-to-login">Back to Login</button>
            </form>
        </div>
    </div>

    <script>
        // Show signup form if there was a signup error
        <?php if (!empty($error_message) && isset($_POST['create-account'])): ?>
        document.getElementById('signup-container').style.display = 'block';
        document.getElementById('login-container').style.display = 'none';
        <?php endif; ?>
        
        document.getElementById('sign-up').onclick = function() {
            document.getElementById('signup-container').style.display = 'block';
            document.getElementById('login-container').style.display = 'none';
        };
        
        document.getElementById('back-to-login').onclick = function() {
            document.getElementById('login-container').style.display = 'block';
            document.getElementById('signup-container').style.display = 'none';
        };
        
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
        
        // Password validation
        document.getElementById('signup-password').addEventListener('input', function() {
            const value = this.value;
            if (value.length === 0) {
                clearValidation('signup-password', 'password-error');
                return;
            }
            
            let errors = [];
            
            if (value.length < 6) {
                errors.push('at least 6 characters (currently ' + value.length + ')');
            } else if (value.length > 20) {
                errors.push('maximum 20 characters (currently ' + value.length + ')');
            }
            
            if (!/[A-Z]/.test(value)) {
                errors.push('one uppercase letter');
            }
            
            if (!/\d/.test(value)) {
                errors.push('one number');
            }
            
            if (!/[!@#$%^&*(),.?":{}|<>]/.test(value)) {
                errors.push('one special character (!@#$%^&*)');
            }
            
            if (errors.length > 0) {
                showError('signup-password', 'password-error', 'Password needs: ' + errors.join(', '));
            } else {
                showSuccess('signup-password', 'password-error');
            }
        });
        
        // Form submission validation
        document.getElementById('signup-form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value;
            const firstName = document.getElementById('first-name').value;
            const lastName = document.getElementById('last-name').value;
            const email = document.getElementById('signup-email').value;
            const password = document.getElementById('signup-password').value;
            
            let isValid = true;
            
            // Validate all fields
            if (username.length < 6) {
                showError('username', 'username-error', 'Username must be at least 6 characters');
                isValid = false;
            } else if (!/\d/.test(username)) {
                showError('username', 'username-error', 'Username must contain at least one number');
                isValid = false;
            }
            
            if (firstName.length < 2) {
                showError('first-name', 'firstname-error', 'First name must be at least 2 characters');
                isValid = false;
            }
            
            if (lastName.length < 2) {
                showError('last-name', 'lastname-error', 'Last name must be at least 2 characters');
                isValid = false;
            }
            
            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                showError('signup-email', 'email-error', 'Please enter a valid email address');
                isValid = false;
            }
            
            if (password.length < 6 || password.length > 20) {
                showError('signup-password', 'password-error', 'Password must be 6-20 characters');
                isValid = false;
            } else if (!/[A-Z]/.test(password)) {
                showError('signup-password', 'password-error', 'Password must contain at least one uppercase letter');
                isValid = false;
            } else if (!/\d/.test(password)) {
                showError('signup-password', 'password-error', 'Password must contain at least one number');
                isValid = false;
            } else if (!/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                showError('signup-password', 'password-error', 'Password must contain at least one special character');
                isValid = false;
            }
            
            if (!isValid) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>