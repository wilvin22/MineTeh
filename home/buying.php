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
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'recent';

// Handle quick star rating submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quick_rate'])) {
    $rating     = (int)$_POST['rating'];
    $seller_id  = (int)$_POST['seller_id'];
    $listing_id = (int)$_POST['listing_id'];
    if ($rating >= 1 && $rating <= 5 && $seller_id && $listing_id) {
        $existing = $supabase->customQuery('reviews', 'review_id',
            'seller_id=eq.' . $seller_id . '&reviewer_id=eq.' . $user_id . '&listing_id=eq.' . $listing_id);
        if (empty($existing)) {
            $supabase->insert('reviews', [
                'seller_id'   => $seller_id,
                'reviewer_id' => $user_id,
                'listing_id'  => $listing_id,
                'rating'      => $rating,
                'comment'     => ''
            ]);
        }
    }
    header("Location: buying.php?tab=recent");
    exit;
}

// Handle remove saved item (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_favorite'])) {
    $listing_id = (int)$_POST['listing_id'];
    $fav = $supabase->customQuery('favorites', 'id', 'user_id=eq.' . $user_id . '&listing_id=eq.' . $listing_id);
    if (!empty($fav)) {
        $supabase->delete('favorites', ['id' => $fav[0]['id']]);
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true]);
    exit;
}

// Recent activity
$recent = [];
$conversations = $supabase->customQuery('conversations', '*', 'or=(user1_id.eq.' . $user_id . ',user2_id.eq.' . $user_id . ')&order=updated_at.desc&limit=20');
if (!empty($conversations)) {
    foreach ($conversations as $conv) {
        if (empty($conv['listing_id'])) continue;
        $listing = $supabase->select('listings', '*', ['id' => $conv['listing_id']], true);
        if (!$listing || $listing['seller_id'] == $user_id) continue;
        $images = $supabase->select('listing_images', 'image_path', ['listing_id' => $listing['id']]);
        $conv['image']          = !empty($images) ? getImageUrl($images[0]['image_path']) : BASE_URL . '/assets/no-image.png';
        $conv['listing_title']  = $listing['title'];
        $conv['listing_price']  = $listing['price'];
        $conv['listing_status'] = $listing['status'];
        $seller = $supabase->select('accounts', 'username', ['account_id' => $listing['seller_id']], true);
        $conv['seller_name'] = $seller ? $seller['username'] : 'Unknown';
        $conv['seller_id']   = $listing['seller_id'];
        $msgs = $supabase->customQuery('messages', 'message_text,created_at', 'conversation_id=eq.' . $conv['conversation_id'] . '&order=created_at.desc&limit=1');
        $conv['last_message']      = !empty($msgs) ? $msgs[0]['message_text'] : 'No messages yet';
        $conv['last_message_time'] = !empty($msgs) ? $msgs[0]['created_at'] : $conv['created_at'];
        // Check if already reviewed this listing
        $conv['already_reviewed'] = false;
        if ($listing['status'] === 'sold') {
            $rev = $supabase->customQuery('reviews', 'review_id',
                'seller_id=eq.' . $listing['seller_id'] . '&reviewer_id=eq.' . $user_id . '&listing_id=eq.' . $listing['id']);
            $conv['already_reviewed'] = !empty($rev);
            $conv['listing_id'] = $listing['id'];
        }
        $recent[] = $conv;
    }
}

// Saved items
$saved = [];
$favorites = $supabase->select('favorites', '*', ['user_id' => $user_id]);
if (!empty($favorites)) {
    foreach ($favorites as $fav) {
        $listing = $supabase->select('listings', '*', ['id' => $fav['listing_id']], true);
        if (!$listing) continue;
        $images = $supabase->select('listing_images', 'image_path', ['listing_id' => $listing['id']]);
        $listing['image'] = !empty($images) ? getImageUrl($images[0]['image_path']) : BASE_URL . '/assets/no-image.png';
        $saved[] = $listing;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buying - MineTeh</title>
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <style>
        * { box-sizing: border-box; }
        body { background: #f5f7fa; margin: 0; font-family: Arial, sans-serif; display: flex; }

        /* ── Buying sub-nav (same width/style as main sidebar) ── */
        .buying-subnav {
            width: 280px;
            min-width: 280px;
            height: 100vh;
            background: #f4f4f4;
            border-right: 1px solid #e0e0e0;
            display: flex;
            flex-direction: column;
            position: fixed;
            left: 0;
            top: 0;
            z-index: 1000;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            overflow-y: auto;
            padding: 20px;
            padding-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .buying-subnav.collapsed { transform: translateX(-100%); }

        .subnav-header {
            margin-bottom: 16px;
            display: flex;
            flex-direction: column;
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

        /* Open button — only visible when subnav is collapsed */
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
            display: flex;
            align-items: center;
            gap: 6px;
            color: #945a9b;
            text-decoration: none;
            font-size: 13px;
            margin-bottom: 6px;
            font-weight: 600;
        }
        .subnav-back:hover { text-decoration: underline; }

        .subnav-title-small { font-size: 11px; color: #999; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; }
        .subnav-title-big   { font-size: 26px; font-weight: bold; color: #333; line-height: 1.1; }

        .subnav-links { flex: 1; }

        .subnav-link {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0 15px;
            height: 50px;
            font-size: 17px;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            margin: 4px 0;
            transition: background 0.2s, color 0.2s, transform 0.2s;
        }
        .subnav-link:hover  { background: #945a9b; color: white; transform: translateX(5px); }
        .subnav-link.active { background: #945a9b; color: white; }

        /* ── Main content ── */
        .buying-main {
            margin-left: 280px;
            width: calc(100% - 280px);
            min-height: 100vh;
            padding: 30px;
            transition: margin-left 0.3s ease, width 0.3s ease;
        }
        .buying-subnav.collapsed ~ .buying-main {
            margin-left: 0;
            width: 100%;
        }

        .content-title { font-size: 24px; font-weight: bold; color: #333; margin-bottom: 22px; }

        /* Recent activity */
        .conv-list { display: flex; flex-direction: column; gap: 4px; }
        .conv-card {
            display: flex;
            gap: 16px;
            align-items: center;
            padding: 14px 16px;
            text-decoration: none;
            color: inherit;
            border-radius: 10px;
            background: white;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            margin-bottom: 8px;
            transition: box-shadow 0.2s, background 0.15s;
        }
        .conv-card:hover { background: #f8f4f9; box-shadow: 0 4px 14px rgba(0,0,0,0.1); }
        .conv-image { width: 90px; height: 90px; object-fit: cover; border-radius: 10px; flex-shrink: 0; }
        .conv-info { flex: 1; min-width: 0; }
        .conv-title { font-weight: bold; font-size: 16px; color: #333; margin-bottom: 3px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .conv-price { color: #945a9b; font-weight: bold; font-size: 15px; margin-bottom: 3px; }
        .conv-meta  { font-size: 13px; color: #666; }
        .conv-last  { font-size: 13px; color: #999; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-top: 3px; }
        .conv-time  { font-size: 12px; color: #bbb; flex-shrink: 0; align-self: flex-start; padding-top: 4px; }
        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 8px; font-size: 11px; font-weight: bold; margin-left: 6px; }
        .status-active   { background: #d1e7dd; color: #0f5132; }
        .status-sold     { background: #f8d7da; color: #842029; }
        .status-inactive { background: #e2e3e5; color: #41464b; }

        /* Saved grid */
        .saved-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 18px; }
        .saved-card-wrap { position: relative; }
        .saved-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            text-decoration: none;
            color: inherit;
            display: block;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .saved-card:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.13); }
        .saved-card img { width: 100%; height: 160px; object-fit: cover; }
        .saved-card-info { padding: 12px 14px; }
        .saved-price { font-weight: bold; color: #945a9b; font-size: 17px; margin-bottom: 3px; }
        .saved-title { font-size: 14px; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .saved-loc   { font-size: 12px; color: #888; margin-top: 3px; }
        .remove-btn {
            position: absolute;
            top: 8px; right: 8px;
            width: 30px; height: 30px;
            background: rgba(0,0,0,0.55);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 16px;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            transition: background 0.2s;
            z-index: 2;
        }
        .remove-btn:hover { background: #dc3545; }

        .empty-state { text-align: center; padding: 50px 20px; color: #999; }
        .empty-icon  { font-size: 52px; margin-bottom: 14px; }
        .btn-browse  { display: inline-block; padding: 11px 24px; background: #945a9b; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; margin-top: 14px; }
        .btn-browse:hover { background: #6a406e; }

        /* Rate seller row */
        .conv-card-wrap { margin-bottom: 8px; }
        .rate-seller-row {
            display: flex; align-items: center; gap: 10px;
            padding: 8px 16px 10px 122px; /* align with text, past the image */
            background: white;
            border-radius: 0 0 10px 10px;
            margin-top: -8px;
            border-top: 1px solid #f0e6f2;
        }
        .rate-label { font-size: 13px; font-weight: 600; color: #555; white-space: nowrap; }
        .star-dropdown {
            padding: 5px 10px; border: 2px solid #945a9b; border-radius: 8px;
            font-size: 13px; cursor: pointer; background: white; color: #333;
            font-family: inherit;
        }
        .star-dropdown:focus { outline: none; }
        .rate-submit-btn {
            padding: 5px 14px; background: #945a9b; color: white;
            border: none; border-radius: 8px; font-size: 13px; font-weight: bold;
            cursor: pointer; transition: background 0.2s;
        }
        .rate-submit-btn:hover { background: #6a406e; }
        .rated-label { font-size: 13px; color: #28a745; font-weight: 600; }

        @media (max-width: 768px) {
            .buying-subnav { width: 220px; min-width: 220px; }
            .buying-main   { margin-left: 220px; width: calc(100% - 220px); padding: 20px; }
            .saved-grid    { grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); }
        }
    </style>
</head>
<body>

    <!-- Buying sub-nav -->
    <div class="buying-subnav" id="buying-subnav">
        <div class="subnav-header">
            <a href="homepage.php" class="subnav-back">‹ MineTeh</a>
            <div class="subnav-title-small">Marketplace</div>
            <div class="subnav-title-big">Buying</div>
            <button class="subnav-close-btn" id="subnav-close" title="Close sidebar">✕</button>
        </div>

        <div class="subnav-links">
            <a href="buying.php?tab=recent" class="subnav-link <?php echo $tab === 'recent' ? 'active' : ''; ?>">
                🕐&nbsp;&nbsp;Recent Activity
            </a>
            <a href="buying.php?tab=saved" class="subnav-link <?php echo $tab === 'saved' ? 'active' : ''; ?>">
                ❤️&nbsp;&nbsp;Saved
            </a>
            <a href="buying.php?tab=bids" class="subnav-link <?php echo $tab === 'bids' ? 'active' : ''; ?>">
                🔨&nbsp;&nbsp;Your Bids
            </a>
            <a href="marketplace-profile.php?id=<?php echo $user_id; ?>&from=buying" class="subnav-link">
                👤&nbsp;&nbsp;Marketplace Profile
            </a>
            <a href="create-listing.php" class="subnav-link" style="background:#945a9b; color:white; font-weight:bold; margin-top:6px;">
                ➕&nbsp;&nbsp;Create New Listing
            </a>
        </div>

        <div class="user-info" style="margin-top:auto;">
            <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></strong>
            <p>User ID: <?php echo $user_id; ?></p>
        </div>
    </div>

    <!-- Main content -->
    <div class="buying-main">

        <?php if ($tab === 'recent'): ?>
            <div class="content-title">Recent Activity</div>
            <?php if (empty($recent)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📭</div>
                    <p>You haven't contacted any sellers yet.</p>
                    <a href="homepage.php" class="btn-browse">Browse Listings</a>
                </div>
            <?php else: ?>
                <div class="conv-list">
                    <?php foreach ($recent as $conv): ?>
                        <div class="conv-card-wrap">
                            <a href="inbox.php?conversation_id=<?php echo $conv['conversation_id']; ?>" class="conv-card">
                                <img src="<?php echo htmlspecialchars($conv['image']); ?>" class="conv-image" alt="">
                                <div class="conv-info">
                                    <div class="conv-title">
                                        <?php echo htmlspecialchars($conv['listing_title']); ?>
                                        <span class="status-badge status-<?php echo strtolower($conv['listing_status']); ?>"><?php echo ucfirst($conv['listing_status']); ?></span>
                                    </div>
                                    <div class="conv-price">₱<?php echo number_format($conv['listing_price'], 2); ?></div>
                                    <div class="conv-meta">Seller: <?php echo htmlspecialchars($conv['seller_name']); ?></div>
                                    <div class="conv-last">💬 <?php echo htmlspecialchars(mb_strimwidth($conv['last_message'], 0, 80, '...')); ?></div>
                                </div>
                                <div class="conv-time"><?php echo date('M d', strtotime($conv['last_message_time'])); ?></div>
                            </a>
                            <?php if ($conv['listing_status'] === 'sold'): ?>
                                <div class="rate-seller-row">
                                    <?php if ($conv['already_reviewed']): ?>
                                        <span class="rated-label">✅ Rated</span>
                                    <?php else: ?>
                                        <form method="POST" class="rate-form" onsubmit="return confirmRate(this)">
                                            <input type="hidden" name="quick_rate" value="1">
                                            <input type="hidden" name="seller_id"  value="<?php echo $conv['seller_id']; ?>">
                                            <input type="hidden" name="listing_id" value="<?php echo $conv['listing_id']; ?>">
                                            <label class="rate-label">Rate Seller:</label>
                                            <select name="rating" class="star-dropdown" required>
                                                <option value="" disabled selected>⭐ Stars</option>
                                                <option value="5">⭐⭐⭐⭐⭐ 5 — Excellent</option>
                                                <option value="4">⭐⭐⭐⭐ 4 — Good</option>
                                                <option value="3">⭐⭐⭐ 3 — Okay</option>
                                                <option value="2">⭐⭐ 2 — Poor</option>
                                                <option value="1">⭐ 1 — Terrible</option>
                                            </select>
                                            <button type="submit" class="rate-submit-btn">Submit</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php elseif ($tab === 'saved'): ?>
            <div class="content-title">Saved Items</div>
            <?php if (empty($saved)): ?>
                <div class="empty-state">
                    <div class="empty-icon">❤️</div>
                    <p>No saved items yet. Heart a listing to save it.</p>
                    <a href="homepage.php" class="btn-browse">Browse Listings</a>
                </div>
            <?php else: ?>
                <div class="saved-grid">
                    <?php foreach ($saved as $item): ?>
                        <div class="saved-card-wrap">
                            <a href="listing-details.php?id=<?php echo $item['id']; ?>" class="saved-card">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                                <div class="saved-card-info">
                                    <div class="saved-price">₱<?php echo number_format($item['price'], 2); ?></div>
                                    <div class="saved-title"><?php echo htmlspecialchars($item['title']); ?></div>
                        
                                    <div class="saved-loc">📍 <?php echo htmlspecialchars($item['location'] ?? ''); ?></div>
                                </div>
                            </a>
                            <button class="remove-btn" onclick="removeSaved(<?php echo $item['id']; ?>, this)" title="Remove from saved">✕</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php elseif ($tab === 'bids'): ?>
            <?php
            // Load bids inline
            $user_bids = $supabase->customQuery('bids', '*', 'user_id=eq.' . $user_id . '&order=bid_time.desc');
            $live_bids = []; $won_bids = []; $lost_bids = [];
            if (!empty($user_bids)) {
                foreach ($user_bids as $bid) {
                    $listing = $supabase->select('listings', '*', ['id' => $bid['listing_id']], true);
                    if (!$listing) continue;
                    $highest = $supabase->customQuery('bids', '*', 'listing_id=eq.' . $bid['listing_id'] . '&order=bid_amount.desc&limit=1');
                    $highest = !empty($highest) ? $highest[0] : null;
                    $images  = $supabase->select('listing_images', 'image_path', ['listing_id' => $listing['id']]);
                    $listing['image'] = !empty($images) ? getImageUrl($images[0]['image_path']) : BASE_URL . '/assets/no-image.png';
                    $ended = (strtotime($listing['end_time']) < time()) || $listing['status'] === 'CLOSED';
                    $entry = ['bid' => $bid, 'listing' => $listing, 'highest' => $highest, 'is_winning' => $highest && $highest['user_id'] == $user_id, 'ended' => $ended];
                    if (!$ended) $live_bids[] = $entry;
                    elseif ($highest && $highest['user_id'] == $user_id) $won_bids[] = $entry;
                    else $lost_bids[] = $entry;
                }
            }
            ?>
            <div class="content-title">Your Bids</div>
            <div style="display:flex; gap:10px; margin-bottom:20px; border-bottom:2px solid #e9ecef; padding-bottom:0;">
                <?php foreach ([['live','🔴 Live', count($live_bids)],['won','🏆 Won',count($won_bids)],['lost','😔 Lost',count($lost_bids)]] as [$tid,$label,$cnt]): ?>
                    <button class="bid-tab-btn <?php echo $tid==='live'?'active':''; ?>" onclick="showBidTab('<?php echo $tid; ?>', this)">
                        <?php echo $label; ?> <span style="background:#e9ecef;padding:2px 8px;border-radius:12px;font-size:12px;margin-left:4px;"><?php echo $cnt; ?></span>
                    </button>
                <?php endforeach; ?>
            </div>
            <style>
                .bid-tab-btn { padding:10px 20px; background:transparent; border:none; border-bottom:3px solid transparent; font-size:15px; font-weight:600; color:#666; cursor:pointer; position:relative; bottom:-2px; transition:color 0.2s; }
                .bid-tab-btn:hover { color:#945a9b; }
                .bid-tab-btn.active { color:#945a9b; border-bottom-color:#945a9b; }
                .bid-tab-content { display:none; } .bid-tab-content.active { display:block; }
                .bids-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(260px,1fr)); gap:18px; }
                .bid-card { background:white; border-radius:12px; overflow:hidden; box-shadow:0 2px 8px rgba(0,0,0,0.09); cursor:pointer; transition:transform 0.2s,box-shadow 0.2s; }
                .bid-card:hover { transform:translateY(-4px); box-shadow:0 6px 18px rgba(0,0,0,0.14); }
                .bid-card-img { position:relative; height:180px; overflow:hidden; }
                .bid-card-img img { width:100%; height:100%; object-fit:cover; }
                .bid-badge { position:absolute; top:10px; right:10px; padding:5px 12px; border-radius:20px; font-size:12px; font-weight:bold; }
                .badge-winning { background:linear-gradient(135deg,#28a745,#20c997); color:white; }
                .badge-outbid  { background:linear-gradient(135deg,#ffc107,#fd7e14); color:#000; }
                .badge-won     { background:linear-gradient(135deg,#945a9b,#6a406e); color:white; }
                .badge-lost    { background:linear-gradient(135deg,#6c757d,#495057); color:white; }
                .bid-card-body { padding:14px; }
                .bid-card-title { font-size:16px; font-weight:bold; color:#333; margin-bottom:8px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
                .bid-row { display:flex; justify-content:space-between; font-size:13px; margin-bottom:6px; }
                .bid-timer { margin-top:10px; padding:7px; background:linear-gradient(135deg,#945a9b,#6a406e); color:white; border-radius:8px; text-align:center; font-size:13px; font-weight:bold; }
                .bid-timer.ended { background:#6c757d; }
            </style>
            <?php foreach ([['live',$live_bids],['won',$won_bids],['lost',$lost_bids]] as [$tid,$items]): ?>
                <div id="bid-tab-<?php echo $tid; ?>" class="bid-tab-content <?php echo $tid==='live'?'active':''; ?>">
                    <?php if (empty($items)): ?>
                        <div class="empty-state"><div class="empty-icon">📭</div><p>No bids here.</p><a href="homepage.php" class="btn-browse">Browse Auctions</a></div>
                    <?php else: ?>
                        <div class="bids-grid">
                            <?php foreach ($items as $it): $b=$it['bid']; $l=$it['listing']; $h=$it['highest']; ?>
                                <div class="bid-card" onclick="location.href='listing-details.php?id=<?php echo $l['id']; ?>'">
                                    <div class="bid-card-img">
                                        <img src="<?php echo htmlspecialchars($l['image']); ?>" alt="">
                                        <span class="bid-badge <?php echo $tid==='live'?($it['is_winning']?'badge-winning':'badge-outbid'):($tid==='won'?'badge-won':'badge-lost'); ?>">
                                            <?php echo $tid==='live'?($it['is_winning']?'✓ Winning':'⚠ Outbid'):($tid==='won'?'🏆 Won':'😔 Lost'); ?>
                                        </span>
                                    </div>
                                    <div class="bid-card-body">
                                        <div class="bid-card-title"><?php echo htmlspecialchars($l['title']); ?></div>
                                        <div class="bid-row"><span style="color:#666">Your Bid:</span><span style="font-weight:bold">₱<?php echo number_format($b['bid_amount'],2); ?></span></div>
                                        <?php if ($h): ?><div class="bid-row"><span style="color:#666">Highest:</span><span style="font-weight:bold;color:#945a9b">₱<?php echo number_format($h['bid_amount'],2); ?></span></div><?php endif; ?>
                                        <div class="bid-timer <?php echo $it['ended']?'ended':''; ?>" <?php echo !$it['ended']?'data-end="'.htmlspecialchars($l['end_time']).'"':''; ?>>
                                            <?php echo $it['ended']?($tid==='won'?'🎉 You Won!':'Auction Ended'):'Calculating...'; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>

    </div><!-- /.buying-main -->

    <!-- Open button — only visible when subnav is collapsed -->
    <button class="subnav-open-btn" id="subnav-open" title="Open sidebar">☰</button>

    <script>
    const buyingSubnav = document.getElementById('buying-subnav');
    const subnavOpen   = document.getElementById('subnav-open');
    const subnavClose  = document.getElementById('subnav-close');

    subnavClose.addEventListener('click', () => {
        buyingSubnav.classList.add('collapsed');
        subnavOpen.classList.add('visible');
    });
    subnavOpen.addEventListener('click', () => {
        buyingSubnav.classList.remove('collapsed');
        subnavOpen.classList.remove('visible');
    });
    
    function confirmRate(form) {
        const sel = form.querySelector('select[name="rating"]');
        if (!sel.value) { alert('Please select a star rating.'); return false; }
        return confirm('Submit ' + sel.value + '-star rating for this seller?');
    }

    function removeSaved(listingId, btn) {
        if (!confirm('Remove this item from saved?')) return;
        const card = btn.closest('.saved-card-wrap');
        fetch('buying.php?tab=saved', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'remove_favorite=1&listing_id=' + listingId
        })
        .then(r => r.json())
        .then(d => { if (d.success) card.remove(); });
    }

    function showBidTab(id, btn) {
        document.querySelectorAll('.bid-tab-content').forEach(t => t.classList.remove('active'));
        document.querySelectorAll('.bid-tab-btn').forEach(b => b.classList.remove('active'));
        document.getElementById('bid-tab-' + id).classList.add('active');
        btn.classList.add('active');
    }

    // Countdown timers
    function updateTimers() {
        document.querySelectorAll('.bid-timer[data-end]').forEach(el => {
            const left = new Date(el.dataset.end) - new Date();
            if (left <= 0) { el.textContent = '⏰ Ended'; el.classList.add('ended'); return; }
            const d = Math.floor(left/86400000), h = Math.floor(left%86400000/3600000),
                  m = Math.floor(left%3600000/60000), s = Math.floor(left%60000/1000);
            el.textContent = d > 0 ? `⏰ ${d}d ${h}h ${m}m` : h > 0 ? `⏰ ${h}h ${m}m ${s}s` : `⏰ ${m}m ${s}s`;
            if (left < 3600000) el.style.background = 'linear-gradient(135deg,#ff4757,#c44569)';
        });
    }
    updateTimers();
    setInterval(updateTimers, 1000);
    </script>
</body>
</html>
