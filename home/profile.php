<?php
session_start();
include '../database/supabase.php';

if (!isset($_SESSION['user_id'])) {
    die("You must be logged in");
}

if (isset($_POST['logout-btn'])) {
    session_unset();
    session_destroy();
    header("Location: ../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background: #f1f3f5;
            display: flex;
        }

        .profile-container {
            flex-grow: 1;
            padding: 40px;
            display: flex;
            justify-content: center;
        }

        .profile-card {
            background: #fff;
            width: 100%;
            max-width: 800px;
            border-radius: 12px;
            box-shadow: 0 6px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        /* Header */
        .profile-header {
            background: linear-gradient(135deg, #945a9b, #6a406e);
            color: white;
            padding: 30px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .profile-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            background: #fff;
            color: #945a9b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: bold;
        }

        .profile-header h2 {
            margin: 0;
        }

        .profile-header span {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Body */
        .profile-body {
            padding: 30px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
        }

        .info-box label {
            font-size: 12px;
            color: #666;
            display: block;
            margin-bottom: 5px;
        }

        .info-box div {
            font-weight: bold;
            color: #333;
        }

        /* Actions */
        .profile-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 20px;
        }

        .profile-actions a {
            text-decoration: none;
            color: #945a9b;
            font-weight: bold;
        }

        .logout-btn {
            padding: 10px 20px;
            border-radius: 20px;
            border: none;
            background: #dc3545;
            color: white;
            cursor: pointer;
        }

        .logout-btn:hover {
            background: #bb2d3b;
        }

        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>
    
    <div class="profile-container main-wrapper">
    <div class="profile-card">

        <!-- Header -->
        <div class="profile-header">
            <div class="profile-avatar">U</div>
            <div>
                <h2>Your Name</h2>
                <span>Member since 2024</span>
            </div>
        </div>

        <!-- Body -->
        <div class="profile-body">
            <div class="info-grid">
                <div class="info-box">
                    <label>Email</label>
                    <div>user@email.com</div>
                </div>

                <div class="info-box">
                    <label>Phone</label>
                    <div>09XXXXXXXXX</div>
                </div>

                <div class="info-box">
                    <label>Location</label>
                    <div>Philippines</div>
                </div>

                <div class="info-box">
                    <label>Total Listings</label>
                    <div>12</div>
                </div>
            </div>

            <div class="profile-actions">
                <a href="#">Edit Profile</a>

                <form method="post">
                    <button type="submit" name="logout-btn" class="logout-btn">
                        Logout
                    </button>
                </form>
            </div>
        </div>

    </div>
</div>

</body>
</html>
