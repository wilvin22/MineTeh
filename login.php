<?php
session_start();
include 'database.php';

// ----------------------- SIGNUP -----------------------
if (isset($_POST['create-account'])) {
    // Escape user input
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first-name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last-name']);
    $email = mysqli_real_escape_string($conn, $_POST['signup-email']);
    $password = $_POST['signup-password'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if username/email already exists
    $username_check = mysqli_query($conn, "SELECT account_id FROM accounts WHERE username='$username'");
    $email_check = mysqli_query($conn, "SELECT account_id FROM accounts WHERE email='$email'");

    if (mysqli_num_rows($username_check) > 0) {
        echo "<script>alert('Username already taken!');</script>";
    } elseif (mysqli_num_rows($email_check) > 0) {
        echo "<script>alert('Email already taken!');</script>";
    } else {
        $insert_sql = "INSERT INTO accounts (username, first_name, last_name, email, password_hash) 
                       VALUES ('$username', '$first_name', '$last_name', '$email', '$hashed_password')";
        if (mysqli_query($conn, $insert_sql)) {
            header("Location: login.php?signup=success");
            exit;
        } else {
            echo "<script>alert('Error creating account!');</script>";
        }
    }
}

// ----------------------- LOGIN -----------------------
if (isset($_POST['log-in'])) {
    $login_input = mysqli_real_escape_string($conn, $_POST['login-email']);
    $password = $_POST['login-password'];

    $login_sql = "SELECT * FROM accounts WHERE email='$login_input' OR username='$login_input'";
    $login_result = mysqli_query($conn, $login_sql);

    if ($login_result && mysqli_num_rows($login_result) === 1) {
        $user = mysqli_fetch_assoc($login_result);

        if (password_verify($password, $user['password_hash'])) {
            // Correct login, set session
            $_SESSION['id'] = $user['account_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // No output before header!
            header("Location: homepage.php");
            exit;
        } else {
            echo "<script>alert('Incorrect password!');</script>";
        }
    } else {
        echo "<script>alert('Invalid email/username or password!');</script>";
    }
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
            <form method="POST" action="login.php">
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
            <form method="POST" action="login.php">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
                <p>Must contain at least 6 characters and a number.</p>

                <label for="first-name">First Name</label>
                <input type="text" id="first-name" name="first-name" placeholder="Enter your first name" required>

                <label for="last-name">Last Name</label>
                <input type="text" id="last-name" name="last-name" placeholder="Enter your last name" required>

                <label for="signup-email">Email</label>
                <input type="text" id="signup-email" name="signup-email" placeholder="Enter your email" required>

                <label for="signup-password">Password</label>
                <input type="password" id="signup-password" name="signup-password" placeholder="Enter your password" required>
                <p>Must be 6-20 characters and include at least 1 uppercase letter, 1 number, and 1 special character.</p>

                <button type="submit" name="create-account" id="create-account">Create Account</button><br><br>
                <button type="button" id="back-to-login">Back to Login</button>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('sign-up').onclick = function() {
            document.getElementById('signup-container').style.display = 'block';
            document.getElementById('login-container').style.display = 'none';
        };
        document.getElementById('back-to-login').onclick = function() {
            document.getElementById('login-container').style.display = 'block';
            document.getElementById('signup-container').style.display = 'none';
        };
    </script>

    <?php
    // Show signup success alert
    if (isset($_GET['signup']) && $_GET['signup'] === 'success') {
        echo "<script>alert('Account created successfully! Please log in.');</script>";
    }
    ?>
</body>
</html>
<?php


mysqli_close($conn);
?>