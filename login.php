<?php
    include 'database.php';
    if (isset($_POST['create-account'])) {
        $username = $_POST['username'];
        $first_name = $_POST['first-name'];
        $last_name = $_POST['last-name'];
        $email = $_POST['email'];
        $password = $_POST['password'];
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $username_check_sql = "SELECT * FROM accounts WHERE username='$username'";
        $username_result = mysqli_query($conn, $username_check_sql);

        $email_check_sql = "SELECT * FROM accounts WHERE email='$email'";
        $email_result = mysqli_query($conn, $email_check_sql);

        if (mysqli_num_rows($username_result) > 0) {
            echo "<script>alert('Username already taken!');</script>";
        } 
        else if (mysqli_num_rows($email_result) > 0) {
            echo "<script>alert('Email already taken!');</script>";
        }
        else {
            $sql = "INSERT INTO accounts (username, first_name, last_name, email, password_hash) VALUES ('$username', '$first_name', '$last_name', '$email', '$hashed_password')";

            if (mysqli_query($conn, $sql)) {
                echo "<script>alert('New record created successfully');</script>";
                header("Location: signup.php?success=1");
                exit;
            } else {
                echo "<script>alert('Error creating account!');</script>";
            }
        }

        mysqli_close($conn);
    }



    if(isset($_POST['log-in'])){
        $email = $_POST['email'];
        $password = $_POST['password'];
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $login_sql = "SELECT * FROM accounts WHERE email='$email' OR username='$username' AND password_hash='$hashed_password'";
        $login_result = mysqli_query($conn, $login_sql);

        if (mysqli_num_rows($login_result) > 0) {
            header("Location: homepage.php");
            exit;
        } 
        else {
            echo "<script>alert('Invalid email/username or password!');</script>";
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in</title>
    <style>
        body{
            margin: 0;
            padding: 0;
            text-align: center;
            background-color: #f0f0f0;
            font-family: Arial, Helvetica, sans-serif;
        }
        #content{
            height: 100%;
            margin-right: 10%;
            margin-left: 10%;
            margin-top: 5%;
        }
        #header{
            text-align: center;
            width: 100%;
        }
        #header h2{
            margin-bottom: 10px;
            font-size: 36px;
            text-align: center;
        }
        #header p{
            font-size: 18px;
            color: gray;
            text-align: center;
        }
        #login-container{
            text-align: start;
            height: auto;
            background: white;
            width: 380px;
            padding: 28px;
            border-radius: 18px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            margin: 40px auto;
            transition: 0.3s ease;
        }
        #signup-container{
            text-align: start;
            height: auto;
            background: white;
            width: 380px;
            padding: 28px;
            border-radius: 18px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            margin: 40px auto;
            transition: 0.3s ease;
            display: none;
        }
        label {
            font-size: 14px;
            font-weight: 700;
            color: #333;
        }
        input {
            width: 92%;
            padding: 12px;
            border:none;
            border-radius: 12px;
            font-size: 16px;
            outline: none;
            transition: 0.3s;
            background-color: #f5f4f4;
        }

        input:focus {
            box-shadow: 0 0 6px rgba(0, 0, 0, 0.3);
        }

        button {
            width: 100%;
            background: #000000;
            border: none;
            padding: 12px;
            border-radius: 12px;
            color: white;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.3s;

        }

        button:hover {
            background: #112d55;
            transform: translateY(-1px);
            box-shadow: 0 5px 14px rgba(0,123,255,0.4);
        }

        button:active {
            transform: translateY(1px);
        }

        #log-in {
            background: #ffffff;
            color: black;
            margin-top: 10px;
            box-shadow: #33333333 0px 4px 12px;
        }
        #create-account{
            background: #ffffff;
            color: black;
            margin-top: 10px;
            box-shadow: #33333333 0px 4px 12px;
        }
        #signup-container p {
            font-size: 12px;
            color: gray;
            margin-left: 5px;
            width: 80%;
        }
    </style>
</head>
<body>
       <div id="content">
        <div id="header">
            <h2>Welcome Back!</h2>
            <p>Please login to your account.</p>
        </div>

        <div id="login-container">
            <form action="">
                <label for="email">Email or Username
                </label><br><br>
                <input type="text" id="email" name="email" placeholder="Enter your email or username" required><br><br>
                <label for="password">Password</label><br><br>
                <input type="password" id="password" name="password" placeholder="Enter your password" required><br><br>
                <button id="log-in">Log in</button><br><br>
            </form>
            <button id="sign-up">Sign up
            </button>
        </div>
        <div id="signup-container">
            <form method="post" action="">
                <label for="username">Username</label><br><br>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
                <p>Must contain at least 6 characters and a number.</p>
                <br>

                <label for="first-name">First Name</label><br><br>
                <input type="text" id="first-name" name="first-name" placeholder="Enter your first name" required><br><br>

                <label for="last-name">Last Name</label><br><br>
                <input type="text" id="last-name" name="last-name" placeholder="Enter your last name" required><br><br>

                <label for="email">Email</label><br><br>
                <input type="text" id="email" name="email" placeholder="Enter your email" required><br><br>

                <label for="password">Password</label><br><br>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <p>Must be 6-20 characters and include at least 1 uppercase letter, 1 number, and 1 special character.</p>

                <button type="submit" name="create-account" id="create-account">Create Account</button><br><br>
                <button id="back-to-login">Back to Login</button>
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
</body>
</html>
<?php
if (isset($_GET['success'])) {
    echo "<script>alert('Account created successfully');</script>";
}
?>