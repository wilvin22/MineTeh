<?php
session_start();
include '../database/supabase.php';

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <style>

    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>
    
    <div class="main-wrapper">
        <h1>Your Bids</h1>
    <script>

    </script>
</body>
</html>