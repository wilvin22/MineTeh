<?php
$current_page = basename($_SERVER['PHP_SELF']);
$buying_pages  = ['buying.php', 'saved-items.php', 'bids.php', 'marketplace-profile.php'];
$selling_pages = ['selling.php', 'your-listings.php', 'create-listing.php'];
$is_buying_active  = in_array($current_page, $buying_pages);
$is_selling_active = in_array($current_page, $selling_pages);

// Badge counts
$notif_count   = 0;
$unread_msgs   = 0;
$outbid_count  = 0;
if (isset($_SESSION['user_id'])) {
    if (!isset($supabase)) include_once __DIR__ . '/../database/supabase.php';
    $uid = $_SESSION['user_id'];

    $notifs = $supabase->customQuery('notifications', 'id', 'user_id=eq.' . $uid . '&is_read=eq.false&type=neq.new_message');
    $notif_count = ($notifs && is_array($notifs)) ? count($notifs) : 0;

    $convs = $supabase->customQuery('conversations', 'conversation_id', 'or=(user1_id.eq.' . $uid . ',user2_id.eq.' . $uid . ')');
    if (!empty($convs) && is_array($convs)) {
        foreach ($convs as $c) {
            $u = $supabase->customQuery('messages', 'message_id', 'conversation_id=eq.' . $c['conversation_id'] . '&sender_id=neq.' . $uid . '&is_read=eq.false');
            if ($u && is_array($u)) $unread_msgs += count($u);
        }
    }

    $user_bids = $supabase->select('bids', '*', ['user_id' => $uid]);
    if (!empty($user_bids) && is_array($user_bids)) {
        foreach ($user_bids as $bid) {
            $bl = $supabase->select('listings', '*', ['id' => $bid['listing_id']]);
            if (!empty($bl) && is_array($bl)) {
                $bl = $bl[0];
                if (!empty($bl['end_time']) && strtotime($bl['end_time']) > time()) {
                    $top = $supabase->customQuery('bids', '*', 'listing_id=eq.' . $bl['id'] . '&order=bid_amount.desc&limit=1');
                    if (!empty($top) && $top[0]['user_id'] != $uid) $outbid_count++;
                }
            }
        }
    }
}
?>

<div id="sidebar">

    <!-- ═══════════════════════════════════════════ -->
    <!-- PANEL: MAIN                                 -->
    <!-- ═══════════════════════════════════════════ -->
    <div class="sidebar-panel" id="panel-main" style="display:flex; flex-direction: column; height: 100%;">

        <div class="sidebar-header">
            <span id="logo">MineTeh</span>
            <button id="sidebar-close" title="Close sidebar" aria-label="Close sidebar">✕</button>
        </div>

        <div class="sidebar-nav">
            <!-- Browse All -->
            <a href="homepage.php">
                <div class="navigation <?php echo $current_page === 'homepage.php' ? 'active' : ''; ?>">
                    <span class="nav-icon">🏠</span> Browse All
                </div>
            </a>

            <!-- Notifications -->
            <a href="notifications.php">
                <div class="navigation <?php echo $current_page === 'notifications.php' ? 'active' : ''; ?>" style="position:relative;">
                    <span class="nav-icon">🔔</span> Notifications
                    <?php if ($notif_count > 0): ?>
                        <span class="notification-badge"><?php echo $notif_count; ?></span>
                    <?php endif; ?>
                </div>
            </a>

            <!-- Inbox -->
            <a href="inbox.php">
                <div class="navigation <?php echo $current_page === 'inbox.php' ? 'active' : ''; ?>" style="position:relative;">
                    <span class="nav-icon">💬</span> Inbox
                    <?php if ($unread_msgs > 0): ?>
                        <span class="notification-badge"><?php echo $unread_msgs; ?></span>
                    <?php endif; ?>
                </div>
            </a>

            <!-- Buying (navigates to buying.php) -->
            <a href="buying.php">
                <div class="navigation nav-panel-trigger <?php echo $is_buying_active ? 'active' : ''; ?>">
                    <span class="nav-icon">🛒</span> Buying
                    <span class="nav-arrow">›</span>
                </div>
            </a>

            <!-- Selling (navigates to selling.php) -->
            <a href="selling.php">
                <div class="navigation nav-panel-trigger <?php echo $is_selling_active ? 'active' : ''; ?>">
                    <span class="nav-icon">🏷️</span> Selling
                    <span class="nav-arrow">›</span>
                </div>
            </a>
        </div>

        <div class="sidebar-footer">
            <a href="create-listing.php" class="btn-create-listing">
                ＋ Create New Listing
            </a>
            <div class="user-info">
                <strong><?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Guest'; ?></strong>
                <p><?php echo isset($_SESSION['user_id']) ? 'User ID: ' . $_SESSION['user_id'] : 'Not logged in'; ?></p>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════ -->
    <!-- PANEL: MAIN only — Buying/Selling have     -->
    <!-- their own built-in sub-nav on their pages  -->
    <!-- ═══════════════════════════════════════════ -->

</div><!-- /#sidebar -->

<!-- Open button — only visible when sidebar is collapsed -->
<button id="sidebar-open" title="Open sidebar" aria-label="Open sidebar">☰</button>

<script src="../sidebar/sidebar.js"></script>
