
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
            position: fixed;
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
        #main-content{
            display: flex;
            padding: 30px;
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
    <div id="main-content"></div>

    <script>
        const explore = document.getElementById('explore');
        const bids = document.getElementById('bids');
        const messages = document.getElementById('messages');
        const createListing = document.getElementById('create-listing');
        const profile = document.getElementById('profile');
        const mainContent = document.getElementById('main-content');
    </script>
</body>
</html>