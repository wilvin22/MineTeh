<!-- Sidebar -->
<div id="sidebar">
    <div class="sidebar-header">
        <span id="logo">MineTeh</span>
    </div>
    
    <!-- Browse Section -->
    <div class="nav-section">
        <div class="nav-section-header">🏠 Browse</div>
        <a href="dashboard.php">
            <div class="navigation" id="dashboard">
                <span>📊</span>&nbsp;&nbsp;My Dashboard
            </div>
        </a>
        <a href="homepage.php">
            <div class="navigation" id="explore">
                <span>🏠</span>&nbsp;&nbsp;Home / Explore
            </div>
        </a>
        <a href="homepage.php">
            <div class="navigation" id="explore">
                <span>🏠</span>&nbsp;&nbsp;Home / Explore
            </div>
        </a>
        <a href="homepage.php">
            <div class="navigation" id="explore">
                <span>🏠</span>&nbsp;&nbsp;Home / Explore
            </div>
        </a>
        <a href="bids.php">
            <div class="navigation" id="bids" style="position: relative;">
                <span>💰</span>&nbsp;&nbsp;Your Bids
                <?php
                if (isset($_SESSION['user_id'])) {
                    if (!isset($supabase)) {
                        include_once __DIR__ . '/../database/supabase.php';
                    }
                    
                    // Get all bids by this user
                    $user_bids = $supabase->select('bids', '*', ['user_id' => $_SESSION['user_id']]);
                    $outbid_count = 0;
                    
                    if (!empty($user_bids) && is_array($user_bids)) {
                        foreach ($user_bids as $bid) {
                            // Get the listing (using different variable name to avoid conflicts)
                            $bid_listing = $supabase->select('listings', '*', ['id' => $bid['listing_id']]);
                            
                            if (!empty($bid_listing) && is_array($bid_listing)) {
                                $bid_listing = $bid_listing[0];
                                
                                // Check if auction is still ongoing
                                $end_time = strtotime($bid_listing['end_time']);
                                $now = time();
                                
                                if ($end_time > $now) {
                                    // Auction is still live, check if user is outbid
                                    $highest_bid = $supabase->customQuery('bids', '*', 'listing_id=eq.' . $bid_listing['id'] . '&order=bid_amount.desc&limit=1');
                                    
                                    if (!empty($highest_bid) && is_array($highest_bid)) {
                                        $highest_bid = $highest_bid[0];
                                        
                                        // If the highest bid is not from this user, they are outbid
                                        if ($highest_bid['user_id'] != $_SESSION['user_id']) {
                                            $outbid_count++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                    
                    if ($outbid_count > 0) {
                        echo '<span class="notification-badge outbid-badge">' . $outbid_count . '</span>';
                    }
                }
                ?>
            </div>
        </a>
        <a href="saved-items.php">
            <div class="navigation" id="saved-items">
                <span>❤️</span>&nbsp;&nbsp;Saved Items
            </div>
        </a>
        <a href="cart.php">
            <div class="navigation" id="cart">
                <span>🛒</span>&nbsp;&nbsp;Your Cart
            </div>
        </a>
        <a href="your-orders.php">
            <div class="navigation" id="your-orders">
                <span>📦</span>&nbsp;&nbsp;Your Orders
            </div>
        </a>
    </div>
    
    <!-- Sell Section -->
    <div class="nav-section">
        <div class="nav-section-header">🏪 Sell</div>
        <a href="create-listing.php">
            <div class="navigation" id="create-listing">
                <span>➕</span>&nbsp;&nbsp;Create Listing
            </div>
        </a>
        <a href="your-listings.php">
            <div class="navigation" id="your-listings">
                <span>📋</span>&nbsp;&nbsp;Your Listings
            </div>
        </a>
    </div>
    
    <!-- Communication Section -->
    <div class="nav-section">
        <div class="nav-section-header">💬 Communication</div>
        <a href="notifications.php">
            <div class="navigation" id="notifications" style="position: relative;">
                <span>🔔</span>&nbsp;&nbsp;Notifications
                <?php
                if (isset($_SESSION['user_id'])) {
                    if (!isset($supabase)) {
                        include_once __DIR__ . '/../database/supabase.php';
                    }
                    // Only count non-message notifications
                    $notif_count = $supabase->customQuery('notifications', 'id', 'user_id=eq.' . $_SESSION['user_id'] . '&is_read=eq.false&type=neq.new_message');
                    if ($notif_count && is_array($notif_count) && count($notif_count) > 0) {
                        echo '<span class="notification-badge">' . count($notif_count) . '</span>';
                    }
                }
                ?>
            </div>
        </a>
        <a href="messages.php">
            <div class="navigation" id="messages" style="position: relative;">
                <span>💬</span>&nbsp;&nbsp;Messages
                <?php
                if (isset($_SESSION['user_id'])) {
                    if (!isset($supabase)) {
                        include_once __DIR__ . '/../database/supabase.php';
                    }
                    $user_convs = $supabase->customQuery('conversations', 'conversation_id', 'or=(user1_id.eq.' . $_SESSION['user_id'] . ',user2_id.eq.' . $_SESSION['user_id'] . ')');
                    $unread_count = 0;
                    if (!empty($user_convs) && is_array($user_convs)) {
                        foreach ($user_convs as $conv) {
                            $unread = $supabase->customQuery('messages', 'message_id', 'conversation_id=eq.' . $conv['conversation_id'] . '&sender_id=neq.' . $_SESSION['user_id'] . '&is_read=eq.false');
                            if ($unread && is_array($unread)) {
                                $unread_count += count($unread);
                            }
                        }
                    }
                    if ($unread_count > 0) {
                        echo '<span class="notification-badge">' . $unread_count . '</span>';
                    }
                }
                ?>
            </div>
        </a>
    </div>
    
    <!-- Account Section -->
    <div class="nav-section">
        <div class="nav-section-header">⚙️ Account</div>
        <a href="account-settings.php">
            <div class="navigation" id="account-settings">
                <span>⚙️</span>&nbsp;&nbsp;Account Settings
            </div>
        </a>
    </div>
    
    <div class="user-info">
        <strong><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest'; ?></strong>
        <p><?php echo isset($_SESSION['user_id']) ? 'User ID: ' . $_SESSION['user_id'] : 'Not logged in'; ?></p>
    </div>
</div>

<!-- Sidebar Toggle Button (outside sidebar) -->
<button id="sidebar-toggle" title="Toggle Sidebar">
    <span id="toggle-icon">✕</span>
</button>

<script src="../sidebar/sidebar.js"></script>
