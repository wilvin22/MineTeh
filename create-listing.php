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
        .upload-btn {
            display: inline-block;
            padding: 10px 16px;
            background: #d6d6d6;
            color: black;
            border-radius: 6px;
            cursor: pointer;
        }
        .upload-btn:hover {
            background: #1d7add;
            color: white;
        }

        #preview {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 10px;
        }

        #preview img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.15);
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
    <div>
        <h2>Create New Listing</h2>
        <form action="process_listing.php" method="post">
            <div id="preview"></div><br>
            <label for="photos" class="upload-btn">Add Photo</label>
            <input type="file" id="photos" name="photos[]" accept="image/*" multiple hidden>
            <br><br>
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" required><br><br>
            <label for="description">Description:</label>
            <textarea id="description" name="description" required></textarea><br><br>
            <label for="price">Price:</label>
            <input type="number" id="price" name="price" required><br><br>
            <label for="category">Category:</label>
            <select id="category" name="category" required>
                <option value="item">Item</option>
                <option value="vehicle">Vehicle</option>
                <option value="home">Home/Housing</option>
            </select><br><br>
            <label for="location">Location:</label>
            <input type="text" id="location" name="location" required><br><br>
            <input type="submit" value="Create Listing">
        </form>
    </div>
    <script>
        document.getElementById('photos').addEventListener('change', function () {
            const preview = document.getElementById('preview');
            preview.innerHTML = '';

            [...this.files].forEach(file => {
                if (!file.type.startsWith('image/')) return;

                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                preview.appendChild(img);
            });
        });
    </script>
</body>
</html>