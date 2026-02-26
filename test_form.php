<?php
session_start();
include 'database/supabase.php';

$error = '';
$success = '';

if (isset($_POST['test_submit'])) {
    $error = "TEST ERROR - Form was submitted!";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Form Test</title>
    <style>
        body { font-family: Arial; padding: 40px; }
        .error { background: red; color: white; padding: 10px; margin: 10px 0; }
        .success { background: green; color: white; padding: 10px; margin: 10px 0; }
        input, button { padding: 10px; margin: 5px 0; display: block; }
    </style>
</head>
<body>
    <h2>Form Submission Test</h2>
    
    <?php if (!empty($error)): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="test_form.php">
        <label>Test Input:</label>
        <input type="text" name="test_input" required>
        <button type="submit" name="test_submit">Submit Test</button>
    </form>
    
    <hr>
    
    <h3>Now test actual login:</h3>
    <form method="POST" action="login.php">
        <label>Email/Username:</label>
        <input type="text" name="login-email" required>
        <label>Password:</label>
        <input type="password" name="login-password" required>
        <button type="submit" name="log-in">Test Login</button>
    </form>
    
    <hr>
    <p><strong>Debug Info:</strong></p>
    <p>POST data: <?php echo !empty($_POST) ? 'YES' : 'NO'; ?></p>
    <p>Supabase loaded: <?php echo isset($supabase) ? 'YES' : 'NO'; ?></p>
    
    <?php if (!empty($_POST)): ?>
        <pre><?php print_r($_POST); ?></pre>
    <?php endif; ?>
</body>
</html>
