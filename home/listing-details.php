<?php
session_start();
include '../database/supabase.php';

if (!isset($_GET['id'])) {
    header("Location: homepage.php");
    exit;
}

$listing_id = (int)$_GET['id'];

// Get listing details
$listing = $supabase->select('listings', '*', ['id' => $listing_id], true);

if (!$listing) {
    die("Listing not found");
}

// Get seller information
$seller = $supabase->select('accounts', 'username,first_name,last_name', ['account_id' => $listing['seller_id']], true);

// Get all images for this listing
$images = $supabase->select('listing_images', '*', ['listing_id' => $listing_id]);

// Get bids if it's a bid listing
$bids = [];
$highest_bid = null;
if ($listing['listing_type'] === 'BID') {
    $bids = $supabase->customQuery('bids', '*', 'listing_id=eq.' . $listing_id . '&order=bid_amount.desc');
    if (!empty($bids)) {
        $highest_bid = $bids[0];
    }
}

// Check if user has favorited this listing
$is_favorited = false;
if (isset($_SESSION['user_id'])) {
    $favorite_check = $supabase->select('favorites', '*', [
        'user_id' => $_SESSION['user_id'],
        'listing_id' => $listing_id
    ]);
    $is_favorited = !empty($favorite_check);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($listing['title']); ?> - MineTeh</title>
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <style>
        body {
            background: #f5f5f5;
        }

        .listing-detail-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            margin-bottom: 20px;
            transition: all 0.2s ease;
        }

        .back-btn:hover {
            background: #945a9b;
            color: white;
            border-color: #945a9b;
        }

        .listing-content {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }

        .listing-main {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .image-gallery {
            position: relative;
            background: #f0f0f0;
            height: 500px;
        }

        .main-image {
            width: 100%;
            height: 100%;
            object-fit: contain;
            background: #000;
        }

        .image-thumbnails {
            display: flex;
            gap: 10px;
            padding: 15px;
            overflow-x: auto;
            background: #fafafa;
        }

        .thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.2s ease;
        }

        .thumbnail:hover,
        .thumbnail.active {
            border-color: #945a9b;
            transform: scale(1.05);
        }

        .listing-info {
            padding: 30px;
        }

        .listing-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .listing-price {
            font-size: 32px;
            font-weight: bold;
            color: #945a9b;
            margin-bottom: 20px;
        }

        .listing-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #666;
            font-size: 14px;
        }

        .badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge.bid {
            background: #fff3cd;
            color: #856404;
        }

        .badge.fixed {
            background: #d1e7dd;
            color: #0f5132;
        }

        .listing-description {
            line-height: 1.6;
            color: #555;
            margin-bottom: 20px;
        }

        .listing-sidebar {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .sidebar-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .seller-info {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .seller-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #945a9b;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
        }

        .seller-name {
            font-weight: bold;
            font-size: 16px;
        }

        .action-btn {
            width: 100%;
            padding: 15px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 10px;
        }

        .btn-primary {
            background: #945a9b;
            color: white;
        }

        .btn-primary:hover {
            background: #6a406e;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(148, 90, 155, 0.3);
        }

        .btn-secondary {
            background: white;
            color: #945a9b;
            border: 2px solid #945a9b;
        }

        .btn-secondary:hover {
            background: #f8f9fa;
        }

        .favorite-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .bid-form {
            margin-top: 15px;
        }

        .bid-form input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .bid-history {
            max-height: 300px;
            overflow-y: auto;
        }

        .bid-item {
            padding: 12px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .bid-item:last-child {
            border-bottom: none;
        }

        .bid-amount {
            font-weight: bold;
            color: #945a9b;
        }

        .bid-user {
            font-size: 14px;
            color: #666;
        }

        @media (max-width: 968px) {
            .listing-content {
                grid-template-columns: 1fr;
            }
        }

        /* Auction Countdown Styles */
        .auction-countdown-container {
            margin: 20px 0;
            padding: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            color: white;
            text-align: center;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .countdown-label {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 10px;
            opacity: 0.9;
        }

        .countdown-timer {
            font-size: 24px;
            font-weight: bold;
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
        }

        .auction-countdown.ending-soon {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            animation: pulse-countdown 1s infinite;
        }

        .auction-countdown.ended {
            background: linear-gradient(135deg, #6c757d, #495057);
        }

        @keyframes pulse-countdown {
            0%, 100% {
                transform: scale(1);
                box-shadow: 0 4px 15px rgba(255, 107, 107, 0.3);
            }
            50% {
                transform: scale(1.02);
                box-shadow: 0 6px 20px rgba(255, 107, 107, 0.5);
            }
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>
    
    <div class="main-wrapper">
        <div class="listing-detail-container">
            <a href="homepage.php" class="back-btn">
                <span>←</span> Back to Listings
            </a>

            <div class="listing-content">
                <!-- Main Content -->
                <div class="listing-main">
                    <!-- Image Gallery -->
                    <div class="image-gallery">
                        <?php if (!empty($images)): ?>
                            <img src="<?php echo htmlspecialchars($images[0]['image_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($listing['title']); ?>" 
                                 class="main-image" 
                                 id="mainImage">
                        <?php else: ?>
                            <img src="../assets/no-image.png" alt="No image" class="main-image" id="mainImage">
                        <?php endif; ?>
                    </div>

                    <?php if (count($images) > 1): ?>
                    <div class="image-thumbnails">
                        <?php foreach ($images as $index => $image): ?>
                            <img src="<?php echo htmlspecialchars($image['image_path']); ?>" 
                                 alt="Thumbnail <?php echo $index + 1; ?>" 
                                 class="thumbnail <?php echo $index === 0 ? 'active' : ''; ?>"
                                 onclick="changeImage(this)">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Listing Info -->
                    <div class="listing-info">
                        <h1 class="listing-title"><?php echo htmlspecialchars($listing['title']); ?></h1>
                        
                        <div class="listing-price">
                            ₱<?php echo number_format($listing['price'], 2); ?>
                            <?php if ($highest_bid): ?>
                                <span style="font-size: 18px; color: #666;">
                                    (Highest bid: ₱<?php echo number_format($highest_bid['bid_amount'], 2); ?>)
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="listing-meta">
                            <div class="meta-item">
                                <span>📍</span>
                                <?php echo htmlspecialchars($listing['location']); ?>
                            </div>
                            <div class="meta-item">
                                <span class="badge <?php echo $listing['listing_type'] === 'BID' ? 'bid' : 'fixed'; ?>">
                                    <?php echo $listing['listing_type']; ?>
                                </span>
                            </div>
                            <div class="meta-item">
                                <span>🕒</span>
                                <?php echo date('M d, Y', strtotime($listing['created_at'])); ?>
                            </div>
                        </div>

                        <?php if ($listing['listing_type'] === 'BID' && !empty($listing['end_time'])): ?>
                        <div class="auction-countdown-container">
                            <div class="auction-countdown" data-end-time="<?php echo $listing['end_time']; ?>">
                                <div class="countdown-label">⏰ Auction Ends In:</div>
                                <div class="countdown-timer"></div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <h3>Description</h3>
                        <p class="listing-description">
                            <?php echo nl2br(htmlspecialchars($listing['description'])); ?>
                        </p>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="listing-sidebar">
                    <!-- Seller Info -->
                    <div class="sidebar-card">
                        <h3>Seller Information</h3>
                        <div class="seller-info">
                            <div class="seller-avatar">
                                <?php echo strtoupper(substr($seller['first_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <div class="seller-name">
                                    <?php echo htmlspecialchars($seller['first_name'] . ' ' . $seller['last_name']); ?>
                                </div>
                                <div style="font-size: 14px; color: #666;">
                                    @<?php echo htmlspecialchars($seller['username']); ?>
                                </div>
                            </div>
                        </div>
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $listing['seller_id']): ?>
                            <a href="messages.php?seller_id=<?php echo $listing['seller_id']; ?>&listing_id=<?php echo $listing_id; ?>" 
                               class="action-btn btn-secondary" 
                               style="text-decoration: none; display: block; text-align: center;">
                                Contact Seller
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Owner Management (only visible to listing owner) -->
                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $listing['seller_id']): ?>
                    <div class="sidebar-card" style="border: 2px solid #945a9b;">
                        <h3 style="color: #945a9b;">Manage Your Listing</h3>
                        <div style="margin-bottom: 15px; padding: 12px; background: #f8f9fa; border-radius: 8px;">
                            <div style="font-size: 13px; color: #666; margin-bottom: 4px;">Status:</div>
                            <div style="font-size: 16px; font-weight: bold; color: <?php echo $listing['status'] === 'active' ? '#28a745' : '#dc3545'; ?>;">
                                <?php echo strtoupper($listing['status']); ?>
                            </div>
                        </div>
                        
                        <?php if ($listing['status'] === 'active' || $listing['status'] === 'OPEN'): ?>
                            <?php if ($listing['listing_type'] === 'BID'): ?>
                                <button onclick="closeListing(<?php echo $listing_id; ?>, 'auction')" 
                                        class="action-btn btn-secondary" 
                                        style="background: #ffc107; border-color: #ffc107; color: #000; margin-bottom: 10px;">
                                    🔨 Close Auction
                                </button>
                            <?php endif; ?>
                            <button onclick="disableListing(<?php echo $listing_id; ?>)" 
                                    class="action-btn btn-secondary" 
                                    style="background: #dc3545; border-color: #dc3545; color: white;">
                                🚫 Disable Listing
                            </button>
                        <?php else: ?>
                            <button onclick="enableListing(<?php echo $listing_id; ?>)" 
                                    class="action-btn btn-primary">
                                ✅ Enable Listing
                            </button>
                        <?php endif; ?>
                        
                        <div style="margin-top: 12px; font-size: 12px; color: #666; text-align: center;">
                            <?php if ($listing['listing_type'] === 'BID'): ?>
                                Close auction to select winner
                            <?php else: ?>
                                Disable to hide from buyers
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="sidebar-card">
                        <?php if ($listing['listing_type'] === 'BID'): ?>
                            <h3>Place Your Bid</h3>
                            <?php 
                            $min_bid_increment = isset($listing['min_bid_increment']) ? floatval($listing['min_bid_increment']) : 1.00;
                            $min_next_bid = $highest_bid ? $highest_bid['bid_amount'] + $min_bid_increment : $listing['starting_price'];
                            
                            // Check if auction has ended
                            $auction_ended = false;
                            if (!empty($listing['end_time'])) {
                                $end_time = new DateTime($listing['end_time']);
                                $now = new DateTime();
                                $auction_ended = $now > $end_time;
                            }
                            ?>
                            <?php if ($auction_ended): ?>
                                <div style="text-align: center; padding: 20px; background: #f8d7da; color: #721c24; border-radius: 8px;">
                                    <strong>🔚 Auction Has Ended</strong>
                                    <p style="margin: 8px 0 0 0; font-size: 14px;">This auction is no longer accepting bids.</p>
                                </div>
                            <?php elseif (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $listing['seller_id']): ?>
                                <form method="POST" action="../api/place-bid.php" class="bid-form">
                                    <input type="hidden" name="listing_id" value="<?php echo $listing_id; ?>">
                                    <input type="number" 
                                           name="bid_amount" 
                                           placeholder="Enter bid amount" 
                                           min="<?php echo $min_next_bid; ?>"
                                           step="<?php echo $min_bid_increment; ?>"
                                           required>
                                    <div style="font-size: 12px; color: #666; margin-top: 8px;">
                                        Minimum bid: ₱<?php echo number_format($min_next_bid, 2); ?>
                                        (increment: ₱<?php echo number_format($min_bid_increment, 2); ?>)
                                    </div>
                                    <button type="submit" name="place_bid" class="action-btn btn-primary">
                                        Place Bid
                                    </button>
                                </form>
                            <?php elseif (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $listing['seller_id']): ?>
                                <a href="create-listing.php?edit=<?php echo $listing_id; ?>" 
                                   class="action-btn btn-primary" 
                                   style="text-decoration: none; display: block; text-align: center; margin-bottom: 10px;">
                                    ✏️ Edit Listing
                                </a>
                                <a href="your-listings.php" 
                                   class="action-btn btn-secondary" 
                                   style="text-decoration: none; display: block; text-align: center;">
                                    📦 Your Listings
                                </a>
                            <?php else: ?>
                                <p style="text-align: center; color: #666;">
                                    <a href="../login.php" style="color: #945a9b;">Login</a> to place a bid
                                </p>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $listing['seller_id']): ?>
                                <a href="checkout.php?listing_id=<?php echo $listing_id; ?>" class="action-btn btn-primary" style="text-decoration: none; display: block; text-align: center;">
                                    Buy Now
                                </a>
                                <button onclick="addToCart(<?php echo $listing_id; ?>)" class="action-btn btn-secondary">
                                    🛒 Add to Cart
                                </button>
                            <?php elseif (!isset($_SESSION['user_id'])): ?>
                                <a href="../login.php" class="action-btn btn-primary" style="text-decoration: none; display: block; text-align: center;">
                                    Login to Buy
                                </a>
                            <?php else: ?>
                                <a href="create-listing.php?edit=<?php echo $listing_id; ?>" class="action-btn btn-primary" style="text-decoration: none; display: block; text-align: center; margin-bottom: 10px;">
                                    ✏️ Edit Listing
                                </a>
                                <a href="your-listings.php" class="action-btn btn-secondary" style="text-decoration: none; display: block; text-align: center;">
                                    📦 Your Listings
                                </a>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $listing['seller_id']): ?>
                            <button class="action-btn btn-secondary favorite-btn" onclick="toggleFavorite(<?php echo $listing_id; ?>)">
                                <span id="favorite-icon"><?php echo $is_favorited ? '❤️' : '🤍'; ?></span>
                                <span id="favorite-text"><?php echo $is_favorited ? 'Favorited' : 'Add to Favorites'; ?></span>
                            </button>
                        <?php endif; ?>
                    </div>

                    <!-- Bid History -->
                    <?php if ($listing['listing_type'] === 'BID' && !empty($bids)): ?>
                    <div class="sidebar-card">
                        <h3>Bid History (<?php echo count($bids); ?>)</h3>
                        <div class="bid-history">
                            <?php foreach ($bids as $bid): 
                                $bidder = $supabase->select('accounts', 'username', ['account_id' => $bid['user_id']], true);
                            ?>
                                <div class="bid-item">
                                    <div>
                                        <div class="bid-amount">₱<?php echo number_format($bid['bid_amount'], 2); ?></div>
                                        <div class="bid-user">by @<?php echo htmlspecialchars($bidder['username']); ?></div>
                                    </div>
                                    <div style="font-size: 12px; color: #999;">
                                        <?php echo date('M d, h:i A', strtotime($bid['bid_time'])); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function changeImage(thumbnail) {
            const mainImage = document.getElementById('mainImage');
            mainImage.src = thumbnail.src;
            
            // Update active thumbnail
            document.querySelectorAll('.thumbnail').forEach(t => t.classList.remove('active'));
            thumbnail.classList.add('active');
        }

        function toggleFavorite(listingId) {
            fetch('../api/favorite-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    listing_id: listingId,
                    favorite: document.getElementById('favorite-icon').textContent === '🤍'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const icon = document.getElementById('favorite-icon');
                    const text = document.getElementById('favorite-text');
                    
                    if (icon.textContent === '🤍') {
                        icon.textContent = '❤️';
                        text.textContent = 'Favorited';
                    } else {
                        icon.textContent = '🤍';
                        text.textContent = 'Add to Favorites';
                    }
                }
            });
        }

        function closeListing(listingId, type) {
            const message = type === 'auction' 
                ? 'Close this auction? The highest bidder will win.' 
                : 'Close this listing?';
            
            if (!confirm(message)) return;

            fetch('../api/manage-listing.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    listing_id: listingId,
                    action: 'close'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Listing closed successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to close listing'));
                }
            })
            .catch(error => {
                alert('Error closing listing');
                console.error(error);
            });
        }

        function disableListing(listingId) {
            if (!confirm('Disable this listing? It will be hidden from buyers.')) return;

            fetch('../api/manage-listing.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    listing_id: listingId,
                    action: 'disable'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Listing disabled successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to disable listing'));
                }
            })
            .catch(error => {
                alert('Error disabling listing');
                console.error(error);
            });
        }

        function enableListing(listingId) {
            if (!confirm('Enable this listing? It will be visible to buyers again.')) return;

            fetch('../api/manage-listing.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    listing_id: listingId,
                    action: 'enable'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Listing enabled successfully!');
                    location.reload();
                } else {
                    alert('Error: ' + (data.message || 'Failed to enable listing'));
                }
            })
            .catch(error => {
                alert('Error enabling listing');
                console.error(error);
            });
        }

        function addToCart(listingId) {
            fetch('../api/cart-action.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    listing_id: listingId,
                    action: 'add'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Item added to cart!');
                } else {
                    alert(data.message || 'Failed to add to cart');
                }
            })
            .catch(error => {
                alert('Error adding to cart');
                console.error(error);
            });
        }

        // Countdown Timer Functionality
        function updateCountdown() {
            const countdownElement = document.querySelector('.auction-countdown');
            if (!countdownElement) return;
            
            const timerElement = countdownElement.querySelector('.countdown-timer');
            const endTime = new Date(countdownElement.dataset.endTime).getTime();
            const now = new Date().getTime();
            const timeLeft = endTime - now;
            
            if (timeLeft <= 0) {
                timerElement.textContent = 'AUCTION ENDED';
                countdownElement.classList.add('ended');
                countdownElement.querySelector('.countdown-label').textContent = '🔚 Auction Has Ended';
                return;
            }
            
            const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
            const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
            
            let timeString = '';
            if (days > 0) {
                timeString = `${days}d ${hours.toString().padStart(2, '0')}h ${minutes.toString().padStart(2, '0')}m ${seconds.toString().padStart(2, '0')}s`;
            } else if (hours > 0) {
                timeString = `${hours.toString().padStart(2, '0')}h ${minutes.toString().padStart(2, '0')}m ${seconds.toString().padStart(2, '0')}s`;
            } else {
                timeString = `${minutes.toString().padStart(2, '0')}m ${seconds.toString().padStart(2, '0')}s`;
            }
            
            timerElement.textContent = timeString;
            
            // Add ending soon class if less than 1 hour left
            if (timeLeft < 3600000) { // 1 hour in milliseconds
                countdownElement.classList.add('ending-soon');
                countdownElement.querySelector('.countdown-label').textContent = '🚨 Auction Ending Soon!';
            } else {
                countdownElement.classList.remove('ending-soon');
            }
        }
        
        // Update countdown immediately and then every second
        updateCountdown();
        setInterval(updateCountdown, 1000);
    </script>
</body>
</html>
