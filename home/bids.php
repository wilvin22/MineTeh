<?php
session_start();

// Block admin access to user pages
require_once __DIR__ . '/../includes/block_admin_access.php';

include '../database/supabase.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Get all bids by this user
$user_bids = $supabase->customQuery('bids', '*', 'user_id=eq.' . $user_id . '&order=bid_time.desc');

// Organize bids into categories
$live_bids = [];
$won_bids = [];
$lost_bids = [];

if (!empty($user_bids)) {
    foreach ($user_bids as $bid) {
        // Get listing details
        $listing = $supabase->select('listings', '*', ['id' => $bid['listing_id']], true);
        
        if (!$listing) continue;
        
        // Get highest bid for this listing
        $highest_bid = $supabase->customQuery('bids', '*', 'listing_id=eq.' . $bid['listing_id'] . '&order=bid_amount.desc&limit=1');
        $highest_bid = !empty($highest_bid) ? $highest_bid[0] : null;
        
        // Get listing image
        $images = $supabase->select('listing_images', 'image_path', ['listing_id' => $listing['id']]);
        $listing['image'] = !empty($images) ? $images[0]['image_path'] : '../assets/no-image.png';
        
        // Check auction status
        $now = new DateTime();
        $end_time = new DateTime($listing['end_time']);
        $auction_ended = $now > $end_time || $listing['status'] === 'CLOSED';
        
        $bid_data = [
            'bid' => $bid,
            'listing' => $listing,
            'highest_bid' => $highest_bid,
            'is_winning' => $highest_bid && $highest_bid['user_id'] == $user_id,
            'auction_ended' => $auction_ended
        ];
        
        if (!$auction_ended) {
            // Live auction
            $live_bids[] = $bid_data;
        } else {
            // Auction ended - check if won or lost
            if ($highest_bid && $highest_bid['user_id'] == $user_id) {
                $won_bids[] = $bid_data;
            } else {
                $lost_bids[] = $bid_data;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Bids - MineTeh</title>
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <style>
        body {
            background: #f5f5f5;
        }

        .bids-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-title {
            font-size: 36px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
        }

        .page-subtitle {
            font-size: 16px;
            color: #666;
        }

        .tabs-container {
            display: flex;
            gap: 12px;
            margin-bottom: 30px;
            border-bottom: 2px solid #e9ecef;
            padding-bottom: 0;
        }

        .tab-button {
            padding: 12px 24px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            font-size: 16px;
            font-weight: 600;
            color: #666;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            bottom: -2px;
        }

        .tab-button:hover {
            color: #945a9b;
        }

        .tab-button.active {
            color: #945a9b;
            border-bottom-color: #945a9b;
        }

        .tab-badge {
            display: inline-block;
            background: #e9ecef;
            color: #666;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            margin-left: 6px;
        }

        .tab-button.active .tab-badge {
            background: #945a9b;
            color: white;
        }

        .tab-content {
            display: none;
        }

        .tab-content.active {
            display: block;
        }

        .bids-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }

        .bid-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .bid-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }

        .bid-card-image {
            position: relative;
            height: 200px;
            overflow: hidden;
        }

        .bid-card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .bid-status-badge {
            position: absolute;
            top: 12px;
            right: 12px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .badge-winning {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
        }

        .badge-outbid {
            background: linear-gradient(135deg, #ffc107, #fd7e14);
            color: #000;
        }

        .badge-won {
            background: linear-gradient(135deg, #945a9b, #6a406e);
            color: white;
        }

        .badge-lost {
            background: linear-gradient(135deg, #6c757d, #495057);
            color: white;
        }

        .bid-card-content {
            padding: 16px;
        }

        .bid-card-title {
            font-size: 18px;
            font-weight: bold;
            color: #333;
            margin-bottom: 8px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .bid-info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .bid-label {
            color: #666;
        }

        .bid-value {
            font-weight: bold;
            color: #333;
        }

        .bid-value.highlight {
            color: #945a9b;
            font-size: 16px;
        }

        .bid-timer {
            margin-top: 12px;
            padding: 8px;
            background: linear-gradient(135deg, #945a9b, #6a406e);
            color: white;
            border-radius: 8px;
            text-align: center;
            font-size: 13px;
            font-weight: bold;
        }

        .bid-timer.ending-soon {
            background: linear-gradient(135deg, #ff4757, #c44569);
            animation: pulse 1s infinite;
        }

        .bid-timer.ended {
            background: #6c757d;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 24px;
            color: #666;
            margin-bottom: 8px;
        }

        .empty-state p {
            font-size: 16px;
            color: #999;
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
            transition: all 0.3s ease;
        }

        .browse-btn:hover {
            background: #6a406e;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>
    
    <div class="main-wrapper">
        <div class="bids-container">
            <div class="page-header">
                <div class="page-title">💰 Your Bids</div>
                <div class="page-subtitle">Track all your auction bids in one place</div>
            </div>

            <div class="tabs-container">
                <button class="tab-button active" onclick="showTab('live')">
                    🔴 Live
                    <span class="tab-badge"><?php echo count($live_bids); ?></span>
                </button>
                <button class="tab-button" onclick="showTab('won')">
                    🏆 Won
                    <span class="tab-badge"><?php echo count($won_bids); ?></span>
                </button>
                <button class="tab-button" onclick="showTab('lost')">
                    😔 Lost
                    <span class="tab-badge"><?php echo count($lost_bids); ?></span>
                </button>
            </div>

            <!-- Live Bids Tab -->
            <div id="live-tab" class="tab-content active">
                <?php if (empty($live_bids)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🔴</div>
                        <h3>No Live Bids</h3>
                        <p>You don't have any active bids on ongoing auctions</p>
                        <a href="homepage.php" class="browse-btn">Browse Auctions</a>
                    </div>
                <?php else: ?>
                    <div class="bids-grid">
                        <?php foreach ($live_bids as $item): 
                            $bid = $item['bid'];
                            $listing = $item['listing'];
                            $is_winning = $item['is_winning'];
                        ?>
                            <div class="bid-card" onclick="window.location.href='listing-details.php?id=<?php echo $listing['id']; ?>'">
                                <div class="bid-card-image">
                                    <img src="<?php echo htmlspecialchars($listing['image']); ?>" alt="<?php echo htmlspecialchars($listing['title']); ?>">
                                    <div class="bid-status-badge <?php echo $is_winning ? 'badge-winning' : 'badge-outbid'; ?>">
                                        <?php echo $is_winning ? '✓ Winning' : '⚠️ Outbid'; ?>
                                    </div>
                                </div>
                                <div class="bid-card-content">
                                    <div class="bid-card-title"><?php echo htmlspecialchars($listing['title']); ?></div>
                                    
                                    <div class="bid-info-row">
                                        <span class="bid-label">Your Bid:</span>
                                        <span class="bid-value">₱<?php echo number_format($bid['bid_amount'], 2); ?></span>
                                    </div>
                                    
                                    <div class="bid-info-row">
                                        <span class="bid-label">Highest Bid:</span>
                                        <span class="bid-value highlight">₱<?php echo number_format($item['highest_bid']['bid_amount'], 2); ?></span>
                                    </div>
                                    
                                    <div class="bid-timer" data-end-time="<?php echo $listing['end_time']; ?>">
                                        Calculating...
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Won Bids Tab -->
            <div id="won-tab" class="tab-content">
                <?php if (empty($won_bids)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🏆</div>
                        <h3>No Won Auctions</h3>
                        <p>You haven't won any auctions yet</p>
                        <a href="homepage.php" class="browse-btn">Browse Auctions</a>
                    </div>
                <?php else: ?>
                    <div class="bids-grid">
                        <?php foreach ($won_bids as $item): 
                            $bid = $item['bid'];
                            $listing = $item['listing'];
                        ?>
                            <div class="bid-card" onclick="window.location.href='listing-details.php?id=<?php echo $listing['id']; ?>'">
                                <div class="bid-card-image">
                                    <img src="<?php echo htmlspecialchars($listing['image']); ?>" alt="<?php echo htmlspecialchars($listing['title']); ?>">
                                    <div class="bid-status-badge badge-won">
                                        🏆 Won
                                    </div>
                                </div>
                                <div class="bid-card-content">
                                    <div class="bid-card-title"><?php echo htmlspecialchars($listing['title']); ?></div>
                                    
                                    <div class="bid-info-row">
                                        <span class="bid-label">Winning Bid:</span>
                                        <span class="bid-value highlight">₱<?php echo number_format($item['highest_bid']['bid_amount'], 2); ?></span>
                                    </div>
                                    
                                    <div class="bid-info-row">
                                        <span class="bid-label">Ended:</span>
                                        <span class="bid-value"><?php echo date('M d, Y', strtotime($listing['end_time'])); ?></span>
                                    </div>
                                    
                                    <div class="bid-timer ended">
                                        🎉 Auction Ended - You Won!
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Lost Bids Tab -->
            <div id="lost-tab" class="tab-content">
                <?php if (empty($lost_bids)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">😔</div>
                        <h3>No Lost Auctions</h3>
                        <p>You haven't lost any auctions</p>
                        <a href="homepage.php" class="browse-btn">Browse Auctions</a>
                    </div>
                <?php else: ?>
                    <div class="bids-grid">
                        <?php foreach ($lost_bids as $item): 
                            $bid = $item['bid'];
                            $listing = $item['listing'];
                        ?>
                            <div class="bid-card" onclick="window.location.href='listing-details.php?id=<?php echo $listing['id']; ?>'">
                                <div class="bid-card-image">
                                    <img src="<?php echo htmlspecialchars($listing['image']); ?>" alt="<?php echo htmlspecialchars($listing['title']); ?>">
                                    <div class="bid-status-badge badge-lost">
                                        😔 Lost
                                    </div>
                                </div>
                                <div class="bid-card-content">
                                    <div class="bid-card-title"><?php echo htmlspecialchars($listing['title']); ?></div>
                                    
                                    <div class="bid-info-row">
                                        <span class="bid-label">Your Bid:</span>
                                        <span class="bid-value">₱<?php echo number_format($bid['bid_amount'], 2); ?></span>
                                    </div>
                                    
                                    <div class="bid-info-row">
                                        <span class="bid-label">Winning Bid:</span>
                                        <span class="bid-value highlight">₱<?php echo number_format($item['highest_bid']['bid_amount'], 2); ?></span>
                                    </div>
                                    
                                    <div class="bid-timer ended">
                                        Auction Ended
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active from all buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Add active to clicked button
            event.target.classList.add('active');
        }

        // Update countdown timers
        function updateTimers() {
            const timers = document.querySelectorAll('.bid-timer[data-end-time]');
            
            timers.forEach(timer => {
                const endTime = new Date(timer.dataset.endTime).getTime();
                const now = new Date().getTime();
                const timeLeft = endTime - now;
                
                if (timeLeft <= 0) {
                    timer.textContent = '⏰ Auction Ended';
                    timer.classList.add('ended');
                    return;
                }
                
                const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
                
                let timeString = '';
                if (days > 0) {
                    timeString = `⏰ Ends in ${days}d ${hours}h ${minutes}m`;
                } else if (hours > 0) {
                    timeString = `⏰ Ends in ${hours}h ${minutes}m ${seconds}s`;
                } else {
                    timeString = `⏰ Ends in ${minutes}m ${seconds}s`;
                }
                
                timer.textContent = timeString;
                
                // Add ending soon class if less than 1 hour
                if (timeLeft < 3600000) {
                    timer.classList.add('ending-soon');
                }
            });
        }
        
        // Update timers immediately and every second
        updateTimers();
        setInterval(updateTimers, 1000);
    </script>
</body>
</html>
