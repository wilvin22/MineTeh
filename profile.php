<?php
session_start();
include 'database.php';

if (!isset($_SESSION['id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>
    <style>
        body{
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
        }
        #sidebar{
            width: 200px;
            height: 100vh;
            background-color: #f4f4f4;
            padding: 20px;
        }
        .navigation{
            list-style-type: none;
            margin: 15px 0;
            font-size: 18px;
            cursor: pointer;
            border-radius: 5px;
            height: 50px;
            display: flex;
            align-items: center;
            padding-left: 15px;
        }
        .navigation:hover{
            background-color: #1e2e47;
            color: white;
        }
        #logo{
            text-align: center;
            height: 100px;
        }
        a{
            text-decoration: none;
            color: black;
        }
    </style>
</head>
<body>
    <div id="sidebar">
        <div id="logo">MineTeh Logo</div>
        <a href="homepage.php"><div class="navigation" id="explore">Explore</div></a>
        <a href="bids.php"><div class="navigation" id="bids">Bids</div></a>
        <a href="messages.php"><div class="navigation" id="messages">Messages</div></a>
        <a href="create-listing.php"><div class="navigation" id="create-listing">Create Listing</div></a>
        <a href="profile.php"><div class="navigation" id="profile">Profile</div></a>
        <br>
        <div>
            Username
            <p>user@gmail.com</p>
        </div>
    </div>
    <script>

    </script>
</body>
</html>