<?php
session_start();
include 'database.php';

if (!isset($_SESSION['account_id'])) {
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
            width: 250px;
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
        .main-content{
            display: flex;
            padding: 50px;
            border: 1px solid black;
            width: 100%;
        }
        #explore-content{
            display: block;
            color: orange;
        }
        #bids-content{
            display: none;
            color: yellow;
        }
        #messages-content{
            display: none;
            color: green;
        }
        #create-listing-content{
            display: none;
            color: blue;
        }
        #profile-content{
            display: none;
        }
    </style>
</head>
<body>
    <div id="sidebar">
        <div id="logo">MineTeh Logo</div>
        <div class="navigation" id="explore">Explore</div>
        <div class="navigation" id="bids">Bids</div>
        <div class="navigation" id="messages">Messages</div>
        <div class="navigation" id="create-listing">Create Listing</div>
        <div class="navigation" id="profile">Profile</div>
        <br>
        <div>
            Username
            <p>user@gmail.com</p>
        </div>
    </div>
    <div id="explore-content" class="main-content">
        <h1>Lorem ipsum dolor sit amet consectetur adipisicing elit. Quidem earum laborum dolor quos amet? Exercitationem eveniet eius autem similique odio vero facere voluptas velit possimus dignissimos deserunt temporibus, saepe at.</h1>
    </div>

    <div id="bids-content" class="main-content">
        <h1>Lorem ipsum dolor sit amet consectetur adipisicing elit. Quidem earum laborum dolor quos amet? Exercitationem eveniet eius autem similique odio vero facere voluptas velit possimus dignissimos deserunt temporibus, saepe at.</h1>
    </div>

    <div id="messages-content" class="main-content">
        <h1>Lorem ipsum dolor sit amet consectetur adipisicing elit. Quidem earum laborum dolor quos amet? Exercitationem eveniet eius autem similique odio vero facere voluptas velit possimus dignissimos deserunt temporibus, saepe at.</h1>
    </div>

    <div id="create-listing-content" class="main-content">
        <h1>Lorem ipsum dolor sit amet consectetur adipisicing elit. Quidem earum laborum dolor quos amet? Exercitationem eveniet eius autem similique odio vero facere voluptas velit possimus dignissimos deserunt temporibus, saepe at.</h1>
    </div>

    <div id="profile-content" class="main-content">
        <h1>Lorem ipsum dolor sit amet consectetur adipisicing elit. Quidem earum laborum dolor quos amet? Exercitationem eveniet eius autem similique odio vero facere voluptas velit possimus dignissimos deserunt temporibus, saepe at.</h1>
    </div>
    <script>
        const explore = document.getElementById('explore');
        const exploreContent = document.getElementById('explore-content');
        const bids = document.getElementById('bids');
        const bidsContent = document.getElementById('bids-content');
        const messages = document.getElementById('messages');
        const messagesContent = document.getElementById('messages-content');
        const createListing = document.getElementById('create-listing');
        const createListingContent = document.getElementById('create-listing-content');
        const profile = document.getElementById('profile');
        const profileContent = document.getElementById('profile-content');
        const mainContent = document.querySelector('.main-content');

        explore.addEventListener('click', () => {
            exploreContent.style.display = 'block';
            bidsContent.style.display = 'none';
            messagesContent.style.display = 'none';
            createListingContent.style.display = 'none';
            profileContent.style.display = 'none';
        });
        bids.addEventListener('click', () => {
            exploreContent.style.display = 'none';
            bidsContent.style.display = 'block';
            messagesContent.style.display = 'none';
            createListingContent.style.display = 'none';
            profileContent.style.display = 'none';
        });
        messages.addEventListener('click', () => {
            exploreContent.style.display = 'none';
            bidsContent.style.display = 'none';
            messagesContent.style.display = 'block';
            createListingContent.style.display = 'none';
            profileContent.style.display = 'none';
        });
        createListing.addEventListener('click', () => {
            exploreContent.style.display = 'none';
            bidsContent.style.display = 'none';
            messagesContent.style.display = 'none';
            createListingContent.style.display = 'block';
            profileContent.style.display = 'none';
        });
        profile.addEventListener('click', () => {
            exploreContent.style.display = 'none';
            bidsContent.style.display = 'none';
            messagesContent.style.display = 'none';
            createListingContent.style.display = 'none';
            profileContent.style.display = 'block';
        });
        
    </script>
</body>
</html>