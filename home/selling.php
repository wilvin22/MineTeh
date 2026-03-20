<?php
session_start();
require_once __DIR__ . '/../includes/block_admin_access.php';
date_default_timezone_set('Asia/Manila');
include '../config.php';
include '../database/supabase.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$tab     = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// Handle relist action
if (isset($_POST['relist_id'])) {
    $relist_id = (int)$_POST['relist_id'];
    $supabase->update('listings', [
        'status'     => 'active',
        'updated_at' => date('Y-m-d H:i:s')
    ], ['id' => $relist_id, 'seller_id' => $user_id]);
    header("Location: selling.php?tab=manage");
    exit;
}

// Handle status change
if (isset($_POST['change_status_id'], $_POST['new_status'])) {
    $change_id  = (int)$_POST['change_status_id'];
    $new_status = $_POST['new_status'];
    if (in_array($new_status, ['active', 'sold', 'inactive'])) {
        $supabase->update('listings', [
            'status'     => $new_status,
            'updated_at' => date('Y-m-d H:i:s')
        ], ['id' => $change_id, 'seller_id' => $user_id]);

        if ($new_status === 'sold') {
            $listing_info_arr = $supabase->customQuery('listings', 'id,title', 'id=eq.' . $change_id . '&limit=1');
            $listing_info     = !empty($listing_info_arr) ? $listing_info_arr[0] : null;
            $listing_title    = $listing_info ? $listing_info['title'] : 'a listing';

            $convs = $supabase->customQuery('conversations', 'user1_id,user2_id,conversation_id', 'listing_id=eq.' . $change_id);
            if (!empty($convs) && is_array($convs)) {
                $notified = [];
                foreach ($convs as $conv) {
                    $buyer_id = ($conv['user1_id'] == $user_id) ? $conv['user2_id'] : $conv['user1_id'];
                    if ($buyer_id == $user_id || in_array($buyer_id, $notified)) continue;
                    $notified[] = $buyer_id;
                    $supabase->insert('notifications', [
                        'user_id' => $buyer_id,
                        'type'    => 'review_request',
                        'title'   => 'How was your experience?',
                        'message' => 'The item "' . $listing_title . '" has been marked as sold. Leave a review for the seller!',
                        'link'    => 'marketplace-profile.php?id=' . $user_id . '&from=buying&review=1&listing_id=' . $change_id,
                        'is_read' => false
                    ]);
                }
            }
        }
    }
    header("Location: selling.php?tab=manage");
    exit;
}

// Get all listings
$listings = $supabase->customQuery('listings', '*', 'seller_id=eq.' . $user_id . '&order=created_at.desc');
$active_count = $sold_count = $inactive_count = 0;

if (!empty($listings)) {
    foreach ($listings as &$l) {
        $images = $supabase->select('listing_images', 'image_path', ['listing_id' => $l['id']]);
        $l['image'] = !empty($images) ? getImageUrl($images[0]['image_path']) : BASE_URL . '/assets/no-image.png';
        $convs = $supabase->customQuery('conversations', 'conversation_id', 'listing_id=eq.' . $l['id']);
        $l['inquiry_count'] = is_array($convs) ? count($convs) : 0;
        if ($l['status'] === 'active')   $active_count++;
        elseif ($l['status'] === 'sold') $sold_count++;
        else                             $inactive_count++;
    }
    unset($l);
}

$total_listings = count($listings ?? []);

// Get seller ratings
$reviews = $supabase->customQuery('reviews', '*', 'seller_id=eq.' . $user_id);
$total_reviews = is_array($reviews) ? count($reviews) : 0;
$avg_rating    = 0;
if ($total_reviews > 0) {
    $avg_rating = round(array_sum(array_column($reviews, 'rating')) / $total_reviews, 1);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selling - MineTeh</title>
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <style>
        body { background: #f5f7fa; margin: 0; font-family: Arial, sans-serif; display: block; }

        /* ── Selling sub-nav (280px, matches buying.php) ── */
        .selling-subnav {
            width: 280px; min-width: 280px; height: 100vh;
            background: #f4f4f4; border-right: 1px solid #e0e0e0;
            display: flex; flex-direction: column;
            position: fixed; left: 0; top: 0; z-index: 1000;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            overflow-y: auto; padding: 20px; box-sizing: border-box;
            transition: transform 0.3s ease;
        }
        .selling-subnav.collapsed { transform: translateX(-100%); }

        .subnav-header {
            margin-bottom: 16px;
            position: relative;
        }
        .subnav-close-btn {
            position: absolute;
            top: 0; right: 0;
            background: none; border: none;
            color: #999; font-size: 20px;
            cursor: pointer; padding: 4px 8px;
            border-radius: 6px; line-height: 1;
            transition: background 0.2s, color 0.2s;
        }
        .subnav-close-btn:hover { background: #e0d0e3; color: #6a406e; }

        .subnav-open-btn {
            position: fixed;
            left: 16px; top: 16px;
            z-index: 1002;
            background: #945a9b; color: white;
            border: none; width: 38px; height: 38px;
            border-radius: 8px; cursor: pointer;
            display: none;
            align-items: center; justify-content: center;
            font-size: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.18);
            transition: background 0.2s;
        }
        .subnav-open-btn:hover { background: #6a406e; }
        .subnav-open-btn.visible { display: flex; }
        .subnav-back {
            display: flex; align-items: center; gap: 6px;
            color: #945a9b; text-decoration: none;
            font-size: 13px; margin-bottom: 6px; font-weight: 600;
        }
        .subnav-back:hover { text-decoration: underline; }
        .subnav-title-small { font-size: 11px; color: #999; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; }
        .subnav-title-big   { font-size: 26px; font-weight: bold; color: #333; line-height: 1.1; }
        .subnav-create { margin-bottom: 8px; }
        .btn-create-listing {
            display: flex; align-items: center; justify-content: center;
            padding: 13px; background: #945a9b; color: white;
            border-radius: 8px; font-weight: bold; font-size: 15px;
            text-decoration: none; transition: background 0.2s;
        }
        .btn-create-listing:hover { background: #6a406e; }
        .subnav-links { flex: 1; }
        .subnav-link {
            display: flex; align-items: center;
            padding: 0 0 0 15px; height: 50px; font-size: 18px;
            color: #333; text-decoration: none; border-radius: 8px;
            margin: 4px 0; transition: background 0.2s, color 0.2s, transform 0.2s;
        }
        .subnav-link:hover  { background: #945a9b; color: white; transform: translateX(5px); }
        .subnav-link.active { background: #945a9b; color: white; }
        .subnav-link .nav-icon { margin-right: 10px; font-size: 18px; flex-shrink: 0; }
        .subnav-footer { margin-top: 10px; }
        .btn-manage {
            display: flex; align-items: center; justify-content: center;
            padding: 13px; background: #f0e6f2; color: #6a406e;
            border-radius: 8px; font-weight: bold; font-size: 15px;
            text-decoration: none; transition: background 0.2s, color 0.2s;
        }
        .btn-manage:hover { background: #945a9b; color: white; }

        /* ── Main content ── */
        .selling-main {
            margin-left: 280px; width: calc(100% - 280px);
            min-height: 100vh; padding: 30px; box-sizing: border-box;
            transition: margin-left 0.3s ease, width 0.3s ease;
        }
        .selling-subnav.collapsed ~ .selling-main {
            margin-left: 0;
            width: 100%;
        }
        .content-title { font-size: 24px; font-weight: bold; color: #333; margin-bottom: 20px; }

        /* ── Dashboard ── */
        .dash-section { margin-bottom: 28px; }
        .dash-section-header {
            display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px;
        }
        .dash-section-title { font-size: 18px; font-weight: bold; color: #333; }
        .dash-action-btn {
            padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: bold;
            text-decoration: none; display: inline-flex; align-items: center; gap: 6px;
            transition: all 0.2s; background: #945a9b; color: white; border: 2px solid #945a9b;
        }
        .dash-action-btn:hover { background: #6a406e; border-color: #6a406e; }
        .overview-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
        .overview-card {
            background: white; border-radius: 12px; padding: 22px 24px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07); text-decoration: none; color: #333;
            transition: box-shadow 0.2s, transform 0.2s; display: block;
        }
        .overview-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.12); transform: translateY(-2px); }
        .overview-card-icon  { font-size: 26px; margin-bottom: 8px; }
        .overview-card-label { font-size: 15px; color: #555; margin-bottom: 6px; }
        .overview-card-num   { font-size: 28px; font-weight: bold; color: #333; }
        .overview-card-sub   { font-size: 13px; color: #f5a623; margin-top: 4px; }
        .listings-stat-grid  { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
        .listings-stat-grid .stat-tile:nth-child(5),
        .listings-stat-grid .stat-tile:nth-child(6) { grid-column: span 2; }
        .stat-tile {
            background: white; border-radius: 12px; padding: 20px 22px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07); text-decoration: none; color: #333;
            transition: box-shadow 0.2s, transform 0.2s; display: block;
        }
        .stat-tile:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.12); transform: translateY(-2px); }
        .stat-tile-icon  { font-size: 22px; margin-bottom: 8px; }
        .stat-tile-label { font-size: 14px; color: #555; margin-bottom: 6px; }
        .stat-tile-num   { font-size: 26px; font-weight: bold; color: #333; }
        .see-all-link {
            display: block; text-align: center; margin-top: 16px;
            color: #945a9b; font-size: 14px; font-weight: bold; text-decoration: none;
        }
        .see-all-link:hover { text-decoration: underline; }

        /* ── Your Listings — card grid (homepage style) ── */
        .filter-tabs { display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap; }
        .filter-tab {
            padding: 8px 18px; border: 2px solid #ddd; border-radius: 20px;
            background: white; color: #555; font-size: 14px; cursor: pointer;
            transition: all 0.2s; border: none; font-family: inherit;
        }
        .filter-tab:hover, .filter-tab.active { background: #945a9b; color: white; }

        #listings-card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 18px;
        }
        .listing-card {
            background: #fff; border-radius: 12px; overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.2s, box-shadow 0.2s;
            cursor: pointer; position: relative; text-decoration: none; color: inherit;
            display: block;
        }
        .listing-card:hover { transform: translateY(-4px); box-shadow: 0 8px 24px rgba(0,0,0,0.15); }
        .listing-card.hidden { display: none; }
        .listing-card img { width: 100%; height: 160px; object-fit: cover; background: #e9ecef; display: block; }
        .listing-card-body { padding: 12px; }
        .lc-price { font-size: 18px; font-weight: bold; color: #945a9b; margin-bottom: 4px; }
        .lc-title { font-size: 14px; font-weight: bold; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .lc-meta  { font-size: 12px; color: #666; margin-top: 6px; display: flex; justify-content: space-between; align-items: center; }
        .lc-badge { font-size: 11px; padding: 3px 8px; border-radius: 12px; font-weight: bold; }
        .badge-active   { background: #d1e7dd; color: #0f5132; }
        .badge-sold     { background: #cfe2ff; color: #084298; }
        .badge-inactive { background: #e2e3e5; color: #41464b; }

        /* ── Manage Listings — row layout ── */
        .listing-list { display: flex; flex-direction: column; gap: 16px; }
        .listing-row {
            background: white; border-radius: 14px; padding: 18px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08); display: flex;
            gap: 18px; align-items: center; transition: box-shadow 0.2s;
        }
        .listing-row:hover { box-shadow: 0 4px 18px rgba(0,0,0,0.13); }
        .listing-row img { width: 110px; height: 110px; object-fit: cover; border-radius: 12px; flex-shrink: 0; }
        .listing-info { flex: 1; min-width: 0; }
        .listing-title { font-weight: bold; font-size: 17px; color: #333; margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .listing-price { color: #945a9b; font-weight: bold; font-size: 16px; margin-bottom: 5px; }
        .listing-meta  { font-size: 14px; color: #666; }
        .listing-actions { display: flex; flex-direction: column; gap: 8px; flex-shrink: 0; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 8px; font-size: 12px; font-weight: bold; }
        .status-active   { background: #d1e7dd; color: #0f5132; }
        .status-sold     { background: #cfe2ff; color: #084298; }
        .status-inactive { background: #e2e3e5; color: #41464b; }
        .inquiry-chip { display: inline-block; padding: 3px 10px; background: #f0e6f2; color: #6a406e; border-radius: 8px; font-size: 13px; margin-top: 4px; }

        .btn { padding: 9px 18px; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer; text-decoration: none; text-align: center; border: none; transition: all 0.2s; display: inline-block; font-family: inherit; }
        .btn-primary   { background: #945a9b; color: white; }
        .btn-primary:hover { background: #6a406e; }
        .btn-secondary { background: white; color: #945a9b; border: 2px solid #945a9b; }
        .btn-secondary:hover { background: #f8f4f9; }
        .btn-relist    { background: #28a745; color: white; width: 100%; }
        .btn-relist:hover { background: #1e7e34; }

        .status-select {
            width: 100%; padding: 8px 10px; border-radius: 8px;
            font-size: 13px; font-weight: bold; cursor: pointer;
            border: 2px solid #ddd; font-family: inherit;
            transition: border-color 0.2s;
        }
        .status-select:focus { outline: none; border-color: #945a9b; }
        .status-select-active   { border-color: #28a745; color: #0f5132; background: #d1e7dd; }
        .status-select-sold     { border-color: #084298; color: #084298; background: #cfe2ff; }
        .status-select-inactive { border-color: #6c757d; color: #41464b; background: #e2e3e5; }

        .empty-state { text-align: center; padding: 60px 20px; color: #999; }
        .empty-icon  { font-size: 60px; margin-bottom: 16px; }
        .btn-create  { display: inline-block; padding: 12px 28px; background: #945a9b; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 16px; font-size: 15px; }
        .btn-create:hover { background: #6a406e; }

        @media (max-width: 768px) {
            .selling-subnav { width: 220px; min-width: 220px; }
            .selling-main   { margin-left: 220px; width: calc(100% - 220px); padding: 20px; }
            .overview-grid  { grid-template-columns: 1fr; }
            .listings-stat-grid { grid-template-columns: repeat(2, 1fr); }
            .listings-stat-grid .stat-tile:nth-child(5),
            .listings-stat-grid .stat-tile:nth-child(6) { grid-column: span 1; }
            #listings-card-grid { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 12px; }
        }
    </style>
</head>
<body>

    <!-- Selling sub-nav -->
    <div class="selling-subnav" id="selling-subnav">
        <div class="subnav-header">
            <a href="homepage.php" class="subnav-back">‹ MineTeh</a>
            <div class="subnav-title-small">Marketplace</div>
            <div class="subnav-title-big">Selling</div>
            <button class="subnav-close-btn" id="subnav-close" title="Close sidebar">✕</button>
        </div>
        <div class="subnav-create">
            <a href="create-listing.php" class="btn-create-listing">＋ Create New Listing</a>
        </div>
        <div class="subnav-links">
            <a href="selling.php?tab=dashboard" class="subnav-link <?php echo $tab === 'dashboard' ? 'active' : ''; ?>">
                <span class="nav-icon">📊</span> Seller Dashboard
            </a>
            <a href="selling.php?tab=listings" class="subnav-link <?php echo $tab === 'listings' ? 'active' : ''; ?>">
                <span class="nav-icon">🏷️</span> Your Listings
            </a>
            <a href="marketplace-profile.php?id=<?php echo $user_id; ?>&from=selling" class="subnav-link">
                <span class="nav-icon">👤</span> Marketplace Profile
            </a>
        </div>
        <div class="subnav-footer">
            <a href="selling.php?tab=manage" class="btn-manage <?php echo $tab === 'manage' ? 'active' : ''; ?>" style="<?php echo $tab === 'manage' ? 'background:#945a9b;color:white;' : ''; ?>">Manage Listings</a>
        </div>
        <div class="user-info" style="margin-top:10px;">
            <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></strong>
            <p>User ID: <?php echo $user_id; ?></p>
        </div>
    </div>

    <!-- Main content -->
    <div class="selling-main">

    <?php if ($tab === 'dashboard'): ?>
        <div class="content-title">Seller Dashboard</div>

        <!-- Overview -->
        <div class="dash-section">
            <div class="dash-section-header">
                <div class="dash-section-title">Overview</div>
            </div>
            <div class="overview-grid">
                <a href="inbox.php" class="overview-card">
                    <div class="overview-card-icon">💬</div>
                    <div class="overview-card-label">Chats to answer</div>
                    <div class="overview-card-num"><?php
                        $unanswered = 0;
                        if (!empty($listings)) {
                            $ids = implode(',', array_column($listings, 'id'));
                            $seller_convs = $supabase->customQuery('conversations', 'conversation_id', 'listing_id=in.(' . $ids . ')');
                            if (!empty($seller_convs) && is_array($seller_convs)) {
                                foreach ($seller_convs as $sc) {
                                    $unread = $supabase->customQuery('messages', 'message_id',
                                        'conversation_id=eq.' . $sc['conversation_id'] . '&sender_id=neq.' . $user_id . '&is_read=eq.false');
                                    if (!empty($unread)) $unanswered++;
                                }
                            }
                        }
                        echo $unanswered;
                    ?></div>
                </a>
                <a href="marketplace-profile.php?id=<?php echo $user_id; ?>&from=selling" class="overview-card">
                    <div class="overview-card-icon">⭐</div>
                    <div class="overview-card-label">Seller rating</div>
                    <div class="overview-card-num"><?php echo $total_reviews; ?></div>
                    <?php if ($total_reviews > 0): ?>
                        <div class="overview-card-sub">
                            <?php
                            $full  = floor($avg_rating);
                            $half  = ($avg_rating - $full) >= 0.5 ? 1 : 0;
                            $empty = 5 - $full - $half;
                            echo str_repeat('★', $full) . ($half ? '½' : '') . str_repeat('☆', $empty);
                            ?> <?php echo $avg_rating; ?>/5
                        </div>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        <!-- Your Listings stats -->
        <div class="dash-section">
            <div class="dash-section-header">
                <div class="dash-section-title">Your listings</div>
                <a href="create-listing.php" class="dash-action-btn">＋ Create new listing</a>
            </div>
            <?php
            $needs_attention = 0;
            foreach ($listings ?: [] as $l) {
                if ($l['status'] === 'inactive' && $l['inquiry_count'] > 0) $needs_attention++;
            }
            ?>
            <div class="listings-stat-grid">
                <a href="selling.php?tab=listings&filter=active" class="stat-tile">
                    <div class="stat-tile-icon">⚠️</div>
                    <div class="stat-tile-label">Needs attention</div>
                    <div class="stat-tile-num"><?php echo $needs_attention; ?></div>
                </a>
                <a href="selling.php?tab=listings&filter=active" class="stat-tile">
                    <div class="stat-tile-icon">✅</div>
                    <div class="stat-tile-label">Active &amp; pending</div>
                    <div class="stat-tile-num"><?php echo $active_count; ?></div>
                </a>
                <a href="selling.php?tab=listings&filter=sold" class="stat-tile">
                    <div class="stat-tile-icon">🏷️</div>
                    <div class="stat-tile-label">Sold &amp; out of stock</div>
                    <div class="stat-tile-num"><?php echo $sold_count; ?></div>
                </a>
                <a href="selling.php?tab=listings&filter=inactive" class="stat-tile">
                    <div class="stat-tile-icon">📄</div>
                    <div class="stat-tile-label">Inactive</div>
                    <div class="stat-tile-num"><?php echo $inactive_count; ?></div>
                </a>
                <a href="selling.php?tab=manage&filter=inactive" class="stat-tile">
                    <div class="stat-tile-icon">🔄</div>
                    <div class="stat-tile-label">To renew</div>
                    <div class="stat-tile-num"><?php echo $inactive_count; ?></div>
                </a>
                <a href="selling.php?tab=manage&filter=sold" class="stat-tile">
                    <div class="stat-tile-icon">♻️</div>
                    <div class="stat-tile-label">To re-list</div>
                    <div class="stat-tile-num"><?php echo $sold_count; ?></div>
                </a>
            </div>
            <a href="selling.php?tab=listings" class="see-all-link">See all listings</a>
        </div>

    <?php elseif ($tab === 'listings'): ?>
        <!-- ── YOUR LISTINGS: card grid + JS filtering ── -->
        <div class="content-title">Your Listings</div>

        <?php
        // Determine initial filter from URL (so dashboard stat-tile links work)
        $init_filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
        ?>

        <div class="filter-tabs">
            <button class="filter-tab <?php echo $init_filter === 'all'      ? 'active' : ''; ?>" data-filter="all">All (<?php echo $total_listings; ?>)</button>
            <button class="filter-tab <?php echo $init_filter === 'active'   ? 'active' : ''; ?>" data-filter="active">Active (<?php echo $active_count; ?>)</button>
            <button class="filter-tab <?php echo $init_filter === 'sold'     ? 'active' : ''; ?>" data-filter="sold">Sold (<?php echo $sold_count; ?>)</button>
            <button class="filter-tab <?php echo $init_filter === 'inactive' ? 'active' : ''; ?>" data-filter="inactive">Inactive (<?php echo $inactive_count; ?>)</button>
        </div>

        <?php if (empty($listings)): ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <p>You haven't listed anything yet.</p>
                <a href="create-listing.php" class="btn-create">Create Your First Listing</a>
            </div>
        <?php else: ?>
            <div id="listings-card-grid">
                <?php foreach ($listings as $item): ?>
                    <a href="listing-details.php?id=<?php echo $item['id']; ?>"
                       class="listing-card"
                       data-status="<?php echo htmlspecialchars($item['status']); ?>">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>"
                             alt="<?php echo htmlspecialchars($item['title']); ?>">
                        <div class="listing-card-body">
                            <div class="lc-price">₱<?php echo number_format($item['price'], 2); ?></div>
                            <div class="lc-title"><?php echo htmlspecialchars($item['title']); ?></div>
                            <div class="lc-meta">
                                <span>📍 <?php echo htmlspecialchars($item['location'] ?? ''); ?></span>
                                <span class="lc-badge badge-<?php echo strtolower($item['status']); ?>">
                                    <?php echo ucfirst($item['status']); ?>
                                </span>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
            <div id="no-results" style="display:none;" class="empty-state">
                <div class="empty-icon">🔍</div>
                <p>No listings match this filter.</p>
            </div>
        <?php endif; ?>

        <script>
        (function() {
            const tabs   = document.querySelectorAll('.filter-tab');
            const cards  = document.querySelectorAll('#listings-card-grid .listing-card');
            const noRes  = document.getElementById('no-results');
            const initFilter = '<?php echo $init_filter; ?>';

            function applyFilter(f) {
                let visible = 0;
                cards.forEach(c => {
                    const match = f === 'all' || c.dataset.status === f;
                    c.classList.toggle('hidden', !match);
                    if (match) visible++;
                });
                if (noRes) noRes.style.display = visible === 0 ? 'block' : 'none';
            }

            tabs.forEach(btn => {
                btn.addEventListener('click', () => {
                    tabs.forEach(t => t.classList.remove('active'));
                    btn.classList.add('active');
                    applyFilter(btn.dataset.filter);
                });
            });

            // Apply initial filter from URL param
            applyFilter(initFilter);
        })();
        </script>

    <?php elseif ($tab === 'manage'): ?>
        <!-- ── MANAGE LISTINGS: row layout with edit/relist ── -->
        <div class="content-title">Manage Listings</div>

        <?php $manage_filter = isset($_GET['filter']) ? $_GET['filter'] : 'all'; ?>

        <div class="filter-tabs">
            <button class="filter-tab <?php echo $manage_filter === 'all'      ? 'active' : ''; ?>" data-filter="all">All (<?php echo $total_listings; ?>)</button>
            <button class="filter-tab <?php echo $manage_filter === 'active'   ? 'active' : ''; ?>" data-filter="active">Active (<?php echo $active_count; ?>)</button>
            <button class="filter-tab <?php echo $manage_filter === 'sold'     ? 'active' : ''; ?>" data-filter="sold">Sold (<?php echo $sold_count; ?>)</button>
            <button class="filter-tab <?php echo $manage_filter === 'inactive' ? 'active' : ''; ?>" data-filter="inactive">Inactive (<?php echo $inactive_count; ?>)</button>
        </div>

        <?php if (empty($listings)): ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <p>No listings found.</p>
                <a href="create-listing.php" class="btn-create">Create Your First Listing</a>
            </div>
        <?php else: ?>
            <div class="listing-list" id="manage-list">
                <?php foreach ($listings as $item): ?>
                    <div class="listing-row" data-status="<?php echo htmlspecialchars($item['status']); ?>">
                        <img src="<?php echo htmlspecialchars($item['image']); ?>"
                             alt="<?php echo htmlspecialchars($item['title']); ?>">
                        <div class="listing-info">
                            <div class="listing-title"><?php echo htmlspecialchars($item['title']); ?></div>
                            <div class="listing-price">₱<?php echo number_format($item['price'], 2); ?></div>
                            <div class="listing-meta">
                                <span class="status-badge status-<?php echo strtolower($item['status']); ?>"><?php echo ucfirst($item['status']); ?></span>
                                &nbsp;· <?php echo htmlspecialchars($item['listing_type'] ?? ''); ?>
                                &nbsp;· 📍 <?php echo htmlspecialchars($item['location'] ?? ''); ?>
                            </div>
                            <?php if ($item['inquiry_count'] > 0): ?>
                                <span class="inquiry-chip">💬 <?php echo $item['inquiry_count']; ?> inquir<?php echo $item['inquiry_count'] > 1 ? 'ies' : 'y'; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="listing-actions">
                            <a href="listing-details.php?id=<?php echo $item['id']; ?>" class="btn btn-secondary">View</a>
                            <a href="create-listing.php?edit=<?php echo $item['id']; ?>" class="btn btn-primary">Edit</a>
                            <form method="POST" style="margin:0;">
                                <input type="hidden" name="change_status_id" value="<?php echo $item['id']; ?>">
                                <select name="new_status" class="status-select status-select-<?php echo $item['status']; ?>"
                                        onchange="if(confirm('Change status to \'' + this.options[this.selectedIndex].text + '\'?')) this.form.submit(); else this.value='<?php echo $item['status']; ?>';">
                                    <option value="active"   <?php echo $item['status'] === 'active'   ? 'selected' : ''; ?>>Active</option>
                                    <option value="sold"     <?php echo $item['status'] === 'sold'     ? 'selected' : ''; ?>>Sold</option>
                                    <option value="inactive" <?php echo $item['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                </select>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <div id="manage-no-results" style="display:none;" class="empty-state">
                <div class="empty-icon">🔍</div>
                <p>No listings match this filter.</p>
            </div>
        <?php endif; ?>

        <script>
        (function() {
            const tabs  = document.querySelectorAll('.filter-tab');
            const rows  = document.querySelectorAll('#manage-list .listing-row');
            const noRes = document.getElementById('manage-no-results');
            const initFilter = '<?php echo $manage_filter; ?>';

            function applyFilter(f) {
                let visible = 0;
                rows.forEach(r => {
                    const match = f === 'all' || r.dataset.status === f;
                    r.style.display = match ? '' : 'none';
                    if (match) visible++;
                });
                if (noRes) noRes.style.display = visible === 0 ? 'block' : 'none';
            }

            tabs.forEach(btn => {
                btn.addEventListener('click', () => {
                    tabs.forEach(t => t.classList.remove('active'));
                    btn.classList.add('active');
                    applyFilter(btn.dataset.filter);
                });
            });

            applyFilter(initFilter);
        })();
        </script>

    <?php endif; ?>

    </div><!-- /.selling-main -->

    <!-- Open button — only visible when subnav is collapsed -->
    <button class="subnav-open-btn" id="subnav-open" title="Open sidebar">☰</button>

    <script>
    const sellingSubnav = document.getElementById('selling-subnav');
    const subnavOpen    = document.getElementById('subnav-open');
    const subnavClose   = document.getElementById('subnav-close');

    subnavClose.addEventListener('click', () => {
        sellingSubnav.classList.add('collapsed');
        subnavOpen.classList.add('visible');
    });
    subnavOpen.addEventListener('click', () => {
        sellingSubnav.classList.remove('collapsed');
        subnavOpen.classList.remove('visible');
    });
    </script>
</body>
</html>
