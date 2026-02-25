<!-- Sidebar -->
<div id="sidebar">
    <div class="sidebar-header">
        <span id="logo">MineTeh</span>
    </div>
    
    <a href="homepage.php">
        <div class="navigation" id="explore">
            <span>🏠</span>&nbsp;&nbsp;Explore
        </div>
    </a>
    <a href="bids.php">
        <div class="navigation" id="bids">
            <span>💰</span>&nbsp;&nbsp;Your Bids
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
    <a href="create-listing.php">
        <div class="navigation" id="create-listing">
            <span>➕</span>&nbsp;&nbsp;Create Listing
        </div>
    </a>
    <a href="your-listings.php">
        <div class="navigation" id="your-listings">
            <span>📦</span>&nbsp;&nbsp;Your Listings
        </div>
    </a>
    <a href="account-settings.php">
        <div class="navigation" id="account-settings">
            <span>⚙️</span>&nbsp;&nbsp;Account Settings
        </div>
    </a>
    
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
