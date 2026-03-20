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

// Get user info
$user = $supabase->select('accounts', '*', ['account_id' => $user_id], true);

// === BUYING STATS ===
// All conversations this user is part of
$all_convs = $supabase->customQuery('conversations', '*', 'or=(user1_id.eq.' . $user_id . ',user2_id.eq.' . $user_id . ')');
$total_purchases = 0;
$total_sales     = 0;
if (!empty($all_convs) && is_array($all_convs)) {
    foreach ($all_convs as $c) {
        $l = $supabase->select('listings', 'seller_id', ['id' => $c['listing_id']], true);
        if (!$l) continue;
        if ($l['seller_id'] == $user_id) $total_sales++;
        else $total_purchases++;
    }
}
$pending_sales = 0;

// Get active bids
$active_bids = $supabase->select('bids', '*', ['user_id' => $user_id]);
$total_bids = is_array($active_bids) ? count($active_bids) : 0;
$outbid_count = 0;

if (!empty($active_bids) && is_array($active_bids)) {
    foreach ($active_bids as $bid) {
        $listing = $supabase->select('listings', '*', ['id' => $bid['listing_id']], true);
        if ($listing && strtotime($listing['end_time']) > time()) {
            $highest_bid = $supabase->customQuery('bids', '*', 'listing_id=eq.' . $listing['id'] . '&order=bid_amount.desc&limit=1');
            if (!empty($highest_bid) && is_array($highest_bid) && $highest_bid[0]['user_id'] != $user_id) {
                $outbid_count++;
            }
        }
    }
}

// Get saved items
$saved_items = $supabase->select('favorites', '*', ['user_id' => $user_id]);
$total_saved = is_array($saved_items) ? count($saved_items) : 0;

// === SELLING STATS ===
// Get all listings
$listings = $supabase->select('listings', '*', ['seller_id' => $user_id]);
$total_listings = is_array($listings) ? count($listings) : 0;
$active_listings = 0;
$sold_listings = 0;

if (!empty($listings) && is_array($listings)) {
    foreach ($listings as $listing) {
        if ($listing['status'] === 'active' && strtotime($listing['end_time']) > time()) {
            $active_listings++;
        } elseif ($listing['status'] === 'sold') {
            $sold_listings++;
        }
    }
}

// Get sales (conversations as seller) — already computed above
$total_revenue = 0;

// Get bids on user's listings
$bids_received = 0;
if (!empty($listings) && is_array($listings)) {
    foreach ($listings as $listing) {
        $listing_bids = $supabase->select('bids', '*', ['listing_id' => $listing['id']]);
        $bids_received += is_array($listing_bids) ? count($listing_bids) : 0;
    }
}

// === RECENT ACTIVITY ===
// Get recent conversations, split by role
$recent_all = $supabase->customQuery('conversations', '*', 'or=(user1_id.eq.' . $user_id . ',user2_id.eq.' . $user_id . ')&order=updated_at.desc&limit=10');
$recent_purchases = [];
$recent_sales     = [];
if (!empty($recent_all) && is_array($recent_all)) {
    foreach ($recent_all as $c) {
        $l = $supabase->select('listings', 'seller_id,title', ['id' => $c['listing_id']], true);
        if (!$l) continue;
        $c['listing_title'] = $l['title'];
        if ($l['seller_id'] == $user_id) {
            if (count($recent_sales) < 3) $recent_sales[] = $c;
        } else {
            if (count($recent_purchases) < 3) $recent_purchases[] = $c;
        }
    }
}

// Get unread notifications
$unread_notifications = $supabase->customQuery('notifications', 'id', 'user_id=eq.' . $user_id . '&is_read=eq.false&type=neq.new_message');
$unread_count = is_array($unread_notifications) ? count($unread_notifications) : 0;

// Get unread messages
$user_convs = $supabase->customQuery('conversations', 'conversation_id', 'or=(user1_id.eq.' . $user_id . ',user2_id.eq.' . $user_id . ')');
$unread_messages = 0;
if (!empty($user_convs) && is_array($user_convs)) {
    foreach ($user_convs as $conv) {
        $unread = $supabase->customQuery('messages', 'message_id', 'conversation_id=eq.' . $conv['conversation_id'] . '&sender_id=neq.' . $user_id . '&is_read=eq.false');
        if ($unread && is_array($unread)) {
            $unread_messages += count($unread);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - MineTeh</title>
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <style>
        body {
            background: #f5f5f5;
        }

        .dashboard-container {
            max-width: 1400px;
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

        .welcome-card {
            background: linear-gradient(135deg, #945a9b 0%, #6a406e 100%);
            color: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 4px 16px rgba(148, 90, 155, 0.3);
        }

        .welcome-title {
            font-size: 28px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .welcome-subtitle {
            font-size: 16px;
            opacity: 0.9;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #945a9b;
        }

        .stat-icon {
            font-size: 36px;
            margin-bottom: 12px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-change {
            font-size: 12px;
            margin-top: 8px;
            padding: 4px 8px;
            border-radius: 12px;
            display: inline-block;
        }

        .stat-change.positive {
            background: #d1e7dd;
            color: #0f5132;
        }

        .stat-change.negative {
            background: #f8d7da;
            color: #842029;
        }

        .stat-change.neutral {
            background: #fff3cd;
            color: #856404;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .section-card {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #945a9b;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .activity-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.2s ease;
        }

        .activity-item:hover {
            background: #f8f9fa;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            font-size: 24px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8f4f9;
            border-radius: 8px;
            flex-shrink: 0;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: bold;
            color: #333;
            margin-bottom: 4px;
        }

        .activity-desc {
            font-size: 14px;
            color: #666;
        }

        .activity-time {
            font-size: 12px;
            color: #999;
            margin-top: 4px;
        }

        .quick-action {
            display: block;
            padding: 15px;
            background: #f8f4f9;
            border-radius: 8px;
            text-decoration: none;
            color: #333;
            margin-bottom: 12px;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }

        .quick-action:hover {
            background: #945a9b;
            color: white;
            border-color: #945a9b;
            transform: translateX(5px);
        }

        .quick-action-icon {
            font-size: 20px;
            margin-right: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 12px;
            opacity: 0.5;
        }

        .chart-container {
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: #e9ecef;
            border-radius: 4px;
            overflow: hidden;
            margin-top: 8px;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #945a9b 0%, #6a406e 100%);
            transition: width 0.3s ease;
        }

        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-primary {
            background: #945a9b;
            color: white;
        }

        .badge-success {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #842029;
        }

        @media (max-width: 1024px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>
    
    <div class="main-wrapper">
        <div class="dashboard-container">
            <!-- Welcome Card -->
            <div class="welcome-card">
                <div class="welcome-title">👋 Welcome back, <?php echo htmlspecialchars($user['username']); ?>!</div>
                <div class="welcome-subtitle">Here's what's happening with your account today</div>
            </div>

            <!-- Stats Overview -->
            <div class="stats-grid">
                <!-- Buying Stats -->
                <div class="stat-card">
                    <div class="stat-icon">💬</div>
                    <div class="stat-value"><?php echo $total_purchases; ?></div>
                    <div class="stat-label">Inquiries Sent</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">🔨</div>
                    <div class="stat-value"><?php echo $total_bids; ?></div>
                    <div class="stat-label">Active Bids</div>
                    <?php if ($outbid_count > 0): ?>
                        <div class="stat-change negative"><?php echo $outbid_count; ?> outbid</div>
                    <?php endif; ?>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">❤️</div>
                    <div class="stat-value"><?php echo $total_saved; ?></div>
                    <div class="stat-label">Saved Items</div>
                </div>

                <!-- Selling Stats -->
                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-value"><?php echo $total_listings; ?></div>
                    <div class="stat-label">Total Listings</div>
                    <div class="stat-change positive"><?php echo $active_listings; ?> active</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-value"><?php echo $sold_listings; ?></div>
                    <div class="stat-label">Listings Sold</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">📩</div>
                    <div class="stat-value"><?php echo $total_sales; ?></div>
                    <div class="stat-label">Inquiries Received</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">🔔</div>
                    <div class="stat-value"><?php echo $bids_received; ?></div>
                    <div class="stat-label">Bids Received</div>
                </div>
            </div>

            <!-- Content Grid -->
            <div class="content-grid">
                <!-- Recent Activity -->
                <div class="section-card">
                    <div class="section-title">
                        <span>📊 Recent Activity</span>
                    </div>

                    <?php if (empty($recent_purchases) && empty($recent_sales)): ?>
                        <div class="empty-state">
                            <div class="empty-state-icon">📭</div>
                            <p>No recent activity</p>
                        </div>
                    <?php else: ?>
                        <?php if (!empty($recent_purchases)): ?>
                            <?php foreach ($recent_purchases as $conv): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">💬</div>
                                    <div class="activity-content">
                                        <div class="activity-title">Inquiry Sent</div>
                                        <div class="activity-desc"><?php echo htmlspecialchars($conv['listing_title'] ?? 'Item'); ?></div>
                                        <div class="activity-time"><?php echo date('M d, Y g:i A', strtotime($conv['created_at'])); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <?php if (!empty($recent_sales)): ?>
                            <?php foreach ($recent_sales as $conv): ?>
                                <div class="activity-item">
                                    <div class="activity-icon">📩</div>
                                    <div class="activity-content">
                                        <div class="activity-title">Inquiry Received</div>
                                        <div class="activity-desc"><?php echo htmlspecialchars($conv['listing_title'] ?? 'Item'); ?></div>
                                        <div class="activity-time"><?php echo date('M d, Y g:i A', strtotime($conv['created_at'])); ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Quick Actions & Alerts -->
                <div>
                    <!-- Alerts -->
                    <div class="section-card" style="margin-bottom: 20px;">
                        <div class="section-title">
                            <span>🔔 Alerts</span>
                        </div>

                        <?php if ($unread_count > 0): ?>
                            <a href="notifications.php" class="activity-item" style="text-decoration: none; color: inherit;">
                                <div class="activity-icon">🔔</div>
                                <div class="activity-content">
                                    <div class="activity-title">Unread Notifications</div>
                                    <div class="activity-desc">You have <?php echo $unread_count; ?> unread notification<?php echo $unread_count > 1 ? 's' : ''; ?></div>
                                </div>
                            </a>
                        <?php endif; ?>

                        <?php if ($unread_messages > 0): ?>
                            <a href="inbox.php" class="activity-item" style="text-decoration: none; color: inherit;">
                                <div class="activity-icon">💬</div>
                                <div class="activity-content">
                                    <div class="activity-title">Unread Messages</div>
                                    <div class="activity-desc">You have <?php echo $unread_messages; ?> unread message<?php echo $unread_messages > 1 ? 's' : ''; ?></div>
                                </div>
                            </a>
                        <?php endif; ?>

                        <?php if ($outbid_count > 0): ?>
                            <a href="buying.php?tab=bids" class="activity-item" style="text-decoration: none; color: inherit;">
                                <div class="activity-icon">⚠️</div>
                                <div class="activity-content">
                                    <div class="activity-title">Outbid Alert</div>
                                    <div class="activity-desc">You've been outbid on <?php echo $outbid_count; ?> item<?php echo $outbid_count > 1 ? 's' : ''; ?></div>
                                </div>
                            </a>
                        <?php endif; ?>

                        <?php if ($pending_sales > 0): ?>
                            <a href="inbox.php?view=seller" class="activity-item" style="text-decoration: none; color: inherit;">
                                <div class="activity-icon">📩</div>
                                <div class="activity-content">
                                    <div class="activity-title">New Inquiries</div>
                                    <div class="activity-desc"><?php echo $pending_sales; ?> new inquiry on your listings</div>
                                </div>
                            </a>
                        <?php endif; ?>

                        <?php if ($unread_count == 0 && $unread_messages == 0 && $outbid_count == 0 && $pending_sales == 0): ?>
                            <div class="empty-state">
                                <div class="empty-state-icon">✅</div>
                                <p>All caught up!</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Quick Actions -->
                    <div class="section-card">
                        <div class="section-title">
                            <span>⚡ Quick Actions</span>
                        </div>

                        <a href="create-listing.php" class="quick-action">
                            <span class="quick-action-icon">➕</span>
                            Create New Listing
                        </a>

                        <a href="homepage.php" class="quick-action">
                            <span class="quick-action-icon">🔍</span>
                            Browse Marketplace
                        </a>

                        <a href="selling.php?tab=manage" class="quick-action">
                            <span class="quick-action-icon">📋</span>
                            Manage Listings
                        </a>

                        <a href="inbox.php" class="quick-action">
                            <span class="quick-action-icon">💬</span>
                            My Conversations
                        </a>

                        <a href="buying.php?tab=bids" class="quick-action">
                            <span class="quick-action-icon">🔨</span>
                            My Bids
                        </a>

                        <a href="buying.php?tab=saved" class="quick-action">
                            <span class="quick-action-icon">❤️</span>
                            Saved Items
                        </a>
                    </div>
                </div>
            </div>

            <!-- Performance Overview -->
            <div class="section-card">
                <div class="section-title">
                    <span>📈 Performance Overview</span>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
                    <!-- Selling Performance -->
                    <div>
                        <h4 style="margin-bottom: 15px; color: #333;">Selling Performance</h4>
                        
                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span style="font-size: 14px; color: #666;">Listings Sold</span>
                                <span style="font-weight: bold;"><?php echo $sold_listings; ?> / <?php echo $total_listings; ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $total_listings > 0 ? ($sold_listings / $total_listings * 100) : 0; ?>%"></div>
                            </div>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span style="font-size: 14px; color: #666;">Active Listings</span>
                                <span style="font-weight: bold;"><?php echo $active_listings; ?> / <?php echo $total_listings; ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $total_listings > 0 ? ($active_listings / $total_listings * 100) : 0; ?>%"></div>
                            </div>
                        </div>

                        <div style="padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Inquiries Received</div>
                            <div style="font-size: 24px; font-weight: bold; color: #945a9b;">
                                <?php echo $total_sales; ?> conversations
                            </div>
                        </div>
                    </div>

                    <!-- Buying Activity -->
                    <div>
                        <h4 style="margin-bottom: 15px; color: #333;">Buying Activity</h4>
                        
                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span style="font-size: 14px; color: #666;">Bid Success Rate</span>
                                <span style="font-weight: bold;">
                                    <?php 
                                    // Won = bids where user has highest bid on a closed/sold listing
                                    $won_count = 0;
                                    if (!empty($active_bids) && is_array($active_bids)) {
                                        $checked = [];
                                        foreach ($active_bids as $bid) {
                                            if (in_array($bid['listing_id'], $checked)) continue;
                                            $checked[] = $bid['listing_id'];
                                            $bl = $supabase->select('listings', 'status,end_time', ['id' => $bid['listing_id']], true);
                                            if (!$bl) continue;
                                            $ended = in_array($bl['status'], ['sold','CLOSED']) || (!empty($bl['end_time']) && strtotime($bl['end_time']) < time());
                                            if (!$ended) continue;
                                            $top = $supabase->customQuery('bids', 'user_id', 'listing_id=eq.' . $bid['listing_id'] . '&order=bid_amount.desc&limit=1');
                                            if (!empty($top) && $top[0]['user_id'] == $user_id) $won_count++;
                                        }
                                    }
                                    $unique_auctions = count(array_unique(array_column($active_bids ?: [], 'listing_id')));
                                    $bid_success = $unique_auctions > 0 ? round(($won_count / $unique_auctions) * 100) : 0;
                                    echo $bid_success; ?>%
                                </span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo $bid_success; ?>%"></div>
                            </div>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span style="font-size: 14px; color: #666;">Saved Items</span>
                                <span style="font-weight: bold;"><?php echo $total_saved; ?></span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: <?php echo min($total_saved * 10, 100); ?>%"></div>
                            </div>
                        </div>

                        <div style="padding: 15px; background: #f8f9fa; border-radius: 8px;">
                            <div style="font-size: 12px; color: #666; margin-bottom: 5px;">Inquiries Sent</div>
                            <div style="font-size: 24px; font-weight: bold; color: #945a9b;">
                                <?php echo $total_purchases; ?> conversations
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
