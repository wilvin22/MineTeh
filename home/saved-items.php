<?php
session_start();
include '../database/supabase.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get all favorited listings
$favorites = $supabase->select('favorites', '*', ['user_id' => $user_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Items - MineTeh</title>
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            display: flex;
            background: #f5f5f5;
        }

        #main-content {
            flex-grow: 1;
            padding: 30px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }

        .page-subtitle {
            font-size: 16px;
            color: #666;
        }

        #listings-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }

        .listing-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
            position: relative;
        }

        .listing-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }

        .listing-image img {
            width: 100%;
            height: 160px;
            object-fit: cover;
            background: #e9ecef;
        }

        .listing-content {
            padding: 12px;
        }

        .listing-price {
            font-size: 18px;
            font-weight: bold;
            color: #945a9b;
            margin-bottom: 4px;
        }

        .listing-title {
            font-size: 14px;
            font-weight: bold;
            color: #333;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .listing-meta {
            font-size: 12px;
            color: #666;
            margin-top: 6px;
            display: flex;
            justify-content: space-between;
        }

        .remove-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: rgba(255, 255, 255, 0.9);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
            z-index: 10;
        }

        .remove-btn:hover {
            background: #ff4444;
            color: white;
            transform: scale(1.1);
        }

        .empty-state {
            grid-column: 1/-1;
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .browse-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background: #945a9b;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .browse-btn:hover {
            background: #6a406e;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>
    
    <div id="main-content" class="main-wrapper">
        <div class="page-header">
            <div class="page-title">❤️ Saved Items</div>
            <div class="page-subtitle">Your Favorite Items</div>
        </div>

        <div id="listings-grid">
            <?php
            if (empty($favorites)) {
                echo '<div class="empty-state">
                        <div class="empty-state-icon">💔</div>
                        <h2>No saved items yet</h2>
                        <p>Start adding items to your favorites</p>
                        <a href="homepage.php" class="browse-btn">Browse Items</a>
                      </div>';
            } else {
                foreach ($favorites as $fav) {
                    $listing = $supabase->select('listings', '*', ['id' => $fav['listing_id']], true);
                    
                    if ($listing) {
                        $listing_id = $listing['id'];
                        $title = htmlspecialchars($listing['title']);
                        $price = $listing['price'];
                        $location = htmlspecialchars($listing['location']);
                        
                        // Get first image
                        $images = $supabase->select('listing_images', 'image_path', ['listing_id' => $listing_id]);
                        $listing_image = !empty($images) ? $images[0]['image_path'] : '../assets/no-image.png';
                        
                        echo "
                        <div class='listing-card' onclick='viewListing($listing_id)'>
                            <button class='remove-btn' onclick='event.stopPropagation(); removeFavorite($listing_id)' title='Remove from saved'>
                                ❌
                            </button>
                            <div class='listing-image'>
                                <img src='$listing_image' alt='Listing Image'>
                            </div>
                            <div class='listing-content'>
                                <div class='listing-price'>₱" . number_format($price, 2) . "</div>
                                <div class='listing-title'>$title</div>
                                <div class='listing-meta'>
                                    <span>$location</span>
                                </div>
                            </div>
                        </div>
                        ";
                    }
                }
            }
            ?>
        </div>
    </div>

    <script>
        function viewListing(id) {
            window.location.href = 'listing-details.php?id=' + id;
        }

        function removeFavorite(listingId) {
            if (!confirm('Remove this item from your saved items?')) return;

            fetch('../actions/favorite-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    listing_id: listingId,
                    action: 'remove'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Failed to remove item: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to remove item');
            });
        }
    </script>
</body>
</html>
