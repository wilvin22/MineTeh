<?php
session_start();
include 'database/supabase.php';

$message = '';

// Handle login
if (isset($_POST['simple_login'])) {
    $message = "LOGIN FORM SUBMITTED!<br>";
    $message .= "Email: " . htmlspecialchars($_POST['email']) . "<br>";
    $message .= "Password length: " . strlen($_POST['password']) . "<br>";
    
    try {
        $user = $supabase->customQuery('accounts', '*', 'or=(email.eq.' . urlencode($_POST['email']) . ',username.eq.' . urlencode($_POST['email']) . ')&limit=1');
        
        if (!empty($user)) {
            $message .= "User found!<br>";
            if (password_verify($_POST['password'], $user[0]['password_hash'])) {
                $message .= "Password correct! Redirecting...<br>";
                $_SESSION['user_id'] = $user[0]['account_id'];
                $_SESSION['username'] = $user[0]['username'];
                header("Location: home/homepage.php");
                exit;
            } else {
                $message .= "Password incorrect!<br>";
            }
        } else {
            $message .= "User not found!<br>";
        }
    } catch (Exception $e) {
        $message .= "Error: " . $e->getMessage() . "<br>";
    }
}

// Handle signup
if (isset($_POST['simple_signup'])) {
    $message = "SIGNUP FORM SUBMITTED!<br>";
    $message .= "Username: " . htmlspecialchars($_POST['username']) . "<br>";
    $message .= "Email: " . htmlspecialchars($_POST['email']) . "<br>";
    
    try {
        $hashed = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $result = $supabase->insert('accounts', [
            'username' => $_POST['username'],
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email'],
            'password_hash' => $hashed,
            'is_admin' => false
        ]);
        
        if ($result && !empty($result[0])) {
            $message .= "Account created successfully! ID: " . $result[0]['account_id'] . "<br>";
        } else {
            $message .= "Failed to create account!<br>";
        }
    } catch (Exception $e) {
        $message .= "Error: " . $e->getMessage() . "<br>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Simple Login Test</title>
    <style>
        body { font-family: Arial; padding: 40px; max-width: 800px; margin: 0 auto; }
        .message { background: #f0f0f0; padding: 15px; margin: 20px 0; border-left: 4px solid #945a9b; }
        form { background: white; padding: 20px; margin: 20px 0; border: 1px solid #ddd; }
        input { display: block; width: 100%; padding: 10px; margin: 10px 0; }
        button { padding: 12px 24px; background: #945a9b; color: white; border: none; cursor: pointer; }
        button:hover { background: #6a406e; }
        h2 { color: #945a9b; }
    </style>
</head>
<body>
    <h1>Simple Login/Signup Test</h1>
    
    <?php if ($message): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>
    
    <h2>Login Test</h2>
    <form method="POST">
        <input type="text" name="email" placeholder="Email or Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" name="simple_login">Login</button>
    </form>
    
    <h2>Signup Test</h2>
    <form method="POST">
        <input type="text" name="username" placeholder="Username (6+ chars with number)" required>
        <input type="text" name="first_name" placeholder="First Name" required>
        <input type="text" name="last_name" placeholder="Last Name" required>
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password (6-20 chars, 1 upper, 1 number, 1 special)" required>
        <button type="submit" name="simple_signup">Sign Up</button>
    </form>
    
    <hr>
    <p><a href="login.php">← Back to Fancy Login Page</a></p>
    <p><a href="test_login.php">→ Go to Debug Tool</a></p>
</body>
</html>
