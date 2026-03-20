<?php
session_start();
require_once __DIR__ . '/../includes/block_admin_access.php';
date_default_timezone_set('Asia/Manila');
include '../config.php';
include '../database/supabase.php';

if (!isset($_GET['id'])) {
    header("Location: homepage.php");
    exit;
}

$profile_user_id = (int)$_GET['id'];
$current_user_id = $_SESSION['user_id'] ?? null;

// Get profile user
$profile_user = $supabase->select('accounts', '*', ['account_id' => $profile_user_id], true);
if (!$profile_user) {
    die("User not found.");
}

// Get location from user_addresses (city + province only, not full address)
$profile_addr = $supabase->select('user_addresses', 'city,state_province', ['user_id' => $profile_user_id, 'is_default' => true], true);
if (empty($profile_addr)) {
    $profile_addr_any = $supabase->customQuery('user_addresses', 'city,state_province', 'user_id=eq.' . $profile_user_id . '&limit=1');
    $profile_addr = !empty($profile_addr_any) ? $profile_addr_any[0] : null;
}
$profile_location = null;
if (!empty($profile_addr)) {
    $parts = array_filter([$profile_addr['city'] ?? null, $profile_addr['state_province'] ?? null]);
    $profile_location = implode(', ', $parts) ?: null;
}
$profile_user['location'] = $profile_location;

// Get their active listings
$listings = $supabase->customQuery('listings', '*', 'seller_id=eq.' . $profile_user_id . '&status=eq.active&order=created_at.desc');
$all_listings = $supabase->customQuery('listings', 'id,status', 'seller_id=eq.' . $profile_user_id);
$total_listings = is_array($all_listings) ? count($all_listings) : 0;
$sold_count     = is_array($all_listings) ? count(array_filter($all_listings, fn($l) => $l['status'] === 'sold')) : 0;

// Get reviews/ratings
$reviews = $supabase->customQuery('reviews', '*', 'seller_id=eq.' . $profile_user_id . '&order=created_at.desc');
$avg_rating  = 0;
$total_reviews = 0;
if (!empty($reviews) && is_array($reviews)) {
    $total_reviews = count($reviews);
    $sum = array_sum(array_column($reviews, 'rating'));
    $avg_rating = $total_reviews > 0 ? round($sum / $total_reviews, 1) : 0;
}

// Enrich listings with images
if (!empty($listings)) {
    foreach ($listings as &$l) {
        $images = $supabase->select('listing_images', 'image_path', ['listing_id' => $l['id']]);
        $l['image'] = !empty($images) ? getImageUrl($images[0]['image_path']) : BASE_URL . '/assets/no-image.png';
    }
    unset($l);
}

// Member since
$member_since = date('F Y', strtotime($profile_user['created_at'] ?? 'now'));
// Where did the user come from? buying or selling section
$from = isset($_GET['from']) ? $_GET['from'] : 'main';
$is_own_profile = ($current_user_id == $profile_user_id);

// Check if current user has had a conversation with this seller (eligibility to review)
$can_review = false;
$review_listing_id = isset($_GET['listing_id']) ? (int)$_GET['listing_id'] : null;
if ($current_user_id && !$is_own_profile) {
    $seller_listing_ids = $supabase->customQuery('listings', 'id', 'seller_id=eq.' . $profile_user_id);
    if (!empty($seller_listing_ids) && is_array($seller_listing_ids)) {
        $ids = implode(',', array_column($seller_listing_ids, 'id'));
        $conv_check = $supabase->customQuery('conversations', 'conversation_id',
            'listing_id=in.(' . $ids . ')&or=(user1_id.eq.' . $current_user_id . ',user2_id.eq.' . $current_user_id . ')');
        $can_review = !empty($conv_check);
    }
}

// Auto-scroll to review form if ?review=1
$auto_scroll_review = isset($_GET['review']) && $_GET['review'] == '1';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($profile_user['username']); ?>'s Profile - MineTeh</title>
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <style>
        * { box-sizing: border-box; }
        body { background: #f5f7fa; margin: 0; font-family: Arial, sans-serif; display: flex; }

        .profile-subnav {
            width: 280px; min-width: 280px; height: 100vh;
            background: #f4f4f4; border-right: 1px solid #e0e0e0;
            display: flex; flex-direction: column;
            position: fixed; left: 0; top: 0; z-index: 1000;
            box-shadow: 2px 0 5px rgba(0,0,0,0.1);
            overflow-y: auto; padding: 20px; box-sizing: border-box;
        }
        .subnav-header { margin-bottom: 16px; }
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
        .profile-main {
            margin-left: 280px; width: calc(100% - 280px); min-height: 100vh;
        }
        @media (max-width: 768px) {
            .profile-subnav { width: 220px; min-width: 220px; }
            .profile-main   { margin-left: 220px; width: calc(100% - 220px); }
        }
        .user-info {
            padding: 15px; background: white; border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1); margin-top: 10px;
        }
        .user-info p { margin: 5px 0 0; font-size: 14px; color: #666; }
        /* Page content styles */
        .page-container { max-width: 900px; margin: 0 auto; padding: 30px 20px; }
        .profile-card { background: white; border-radius: 16px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.09); margin-bottom: 24px; display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap; }
        .profile-avatar { width: 90px; height: 90px; border-radius: 50%; background: linear-gradient(135deg, #945a9b, #6a406e); color: white; display: flex; align-items: center; justify-content: center; font-size: 36px; font-weight: bold; flex-shrink: 0; }
        .profile-info { flex: 1; min-width: 200px; }
        .profile-name { font-size: 24px; font-weight: bold; color: #333; margin-bottom: 4px; }
        .profile-username { font-size: 15px; color: #666; margin-bottom: 10px; }
        .profile-meta { display: flex; gap: 20px; flex-wrap: wrap; font-size: 14px; color: #555; }
        .profile-meta span { display: flex; align-items: center; gap: 5px; }
        .stars { color: #f5a623; font-size: 18px; letter-spacing: 2px; }
        .rating-text { font-size: 14px; color: #666; margin-left: 6px; }
        .stats-row { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 24px; }
        .stat-box { background: white; border-radius: 12px; padding: 18px 22px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); flex: 1; min-width: 120px; text-align: center; }
        .stat-num { font-size: 28px; font-weight: bold; color: #945a9b; }
        .stat-label { font-size: 13px; color: #666; margin-top: 3px; }
        .section-title { font-size: 20px; font-weight: bold; color: #333; margin: 0 0 16px; padding-bottom: 8px; border-bottom: 2px solid #945a9b; }
        .listings-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px; margin-bottom: 30px; }
        .listing-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.07); text-decoration: none; color: inherit; display: block; transition: transform 0.2s, box-shadow 0.2s; }
        .listing-card:hover { transform: translateY(-3px); box-shadow: 0 6px 18px rgba(0,0,0,0.13); }
        .listing-card img { width: 100%; height: 140px; object-fit: cover; }
        .listing-card-info { padding: 10px 12px; }
        .listing-price { font-weight: bold; color: #945a9b; font-size: 16px; margin-bottom: 3px; }
        .listing-title { font-size: 13px; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .listing-loc { font-size: 12px; color: #888; margin-top: 3px; }
        .reviews-list { display: flex; flex-direction: column; gap: 14px; }
        .review-card { background: white; border-radius: 12px; padding: 18px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); }
        .review-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
        .reviewer-name { font-weight: bold; color: #333; }
        .review-date { font-size: 12px; color: #aaa; }
        .review-stars { color: #f5a623; font-size: 16px; margin-bottom: 6px; }
        .review-text { font-size: 14px; color: #555; line-height: 1.5; }
        .btn-contact { display: inline-block; padding: 11px 26px; background: #945a9b; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 15px; transition: background 0.2s; }
        .btn-contact:hover { background: #6a406e; }
        .empty-state { text-align: center; padding: 30px; color: #999; background: white; border-radius: 12px; }
        .review-form { background: white; border-radius: 12px; padding: 22px; box-shadow: 0 2px 8px rgba(0,0,0,0.07); margin-bottom: 20px; }
        .review-form h3 { font-size: 17px; font-weight: bold; margin-bottom: 14px; color: #333; }
        .star-select { display: flex; gap: 6px; margin-bottom: 14px; flex-direction: row-reverse; justify-content: flex-end; }
        .star-select input[type=radio] { display: none; }
        .star-select label { font-size: 28px; cursor: pointer; color: #ddd; transition: color 0.15s; }
        .star-select input[type=radio]:checked ~ label, .star-select label:hover, .star-select label:hover ~ label { color: #f5a623; }
        .review-form textarea { width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; font-size: 14px; resize: vertical; min-height: 80px; box-sizing: border-box; }
        .review-form textarea:focus { outline: none; border-color: #945a9b; }
        .review-form button { margin-top: 12px; padding: 10px 24px; background: #945a9b; color: white; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 14px; }
        .review-form button:hover { background: #6a406e; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .alert-success { background: #d1e7dd; color: #0f5132; }
        .alert-error   { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>

    <?php if ($from === 'buying'): ?>
        <!-- Buying sub-nav -->
        <div class="profile-subnav">
            <div class="subnav-header">
                <a href="buying.php" class="subnav-back">‹ MineTeh</a>
                <div class="subnav-title-small">Marketplace</div>
                <div class="subnav-title-big">Buying</div>
            </div>
            <div class="subnav-links">
                <a href="buying.php?tab=recent" class="subnav-link"><span class="nav-icon">🕐</span> Recent Activity</a>
                <a href="buying.php?tab=saved"  class="subnav-link"><span class="nav-icon">❤️</span> Saved</a>
                <a href="buying.php?tab=bids"   class="subnav-link"><span class="nav-icon">🔨</span> Your Bids</a>
                <a href="marketplace-profile.php?id=<?php echo $current_user_id; ?>&from=buying" class="subnav-link active"><span class="nav-icon">👤</span> Marketplace Profile</a>
            </div>
            <div class="subnav-footer">
                <a href="create-listing.php" class="btn-create-listing">＋ Create New Listing</a>
            </div>
            <div class="user-info" style="margin-top:10px;">
                <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></strong>
                <p>User ID: <?php echo $current_user_id; ?></p>
            </div>
        </div>

    <?php elseif ($from === 'selling'): ?>
        <!-- Selling sub-nav -->
        <div class="profile-subnav">
            <div class="subnav-header">
                <a href="selling.php" class="subnav-back">‹ MineTeh</a>
                <div class="subnav-title-small">Marketplace</div>
                <div class="subnav-title-big">Selling</div>
            </div>
            <div class="subnav-create">
                <a href="create-listing.php" class="btn-create-listing">＋ Create New Listing</a>
            </div>
            <div class="subnav-links">
                <a href="selling.php?tab=dashboard" class="subnav-link"><span class="nav-icon">📊</span> Seller Dashboard</a>
                <a href="selling.php?tab=listings"  class="subnav-link"><span class="nav-icon">🏷️</span> Your Listings</a>
                <a href="marketplace-profile.php?id=<?php echo $current_user_id; ?>&from=selling" class="subnav-link active"><span class="nav-icon">👤</span> Marketplace Profile</a>
            </div>
            <div class="subnav-footer">
                <a href="selling.php?tab=manage" class="btn-manage">Manage Listings</a>
            </div>
            <div class="user-info" style="margin-top:10px;">
                <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></strong>
                <p>User ID: <?php echo $current_user_id; ?></p>
            </div>
        </div>

    <?php else: ?>
        <!-- Default — no from param -->
        <div class="profile-subnav">
            <div class="subnav-header">
                <a href="homepage.php" class="subnav-back">‹ MineTeh</a>
                <div class="subnav-title-small">Marketplace</div>
                <div class="subnav-title-big">Profile</div>
            </div>
            <div class="subnav-links">
                <a href="buying.php?tab=recent" class="subnav-link"><span class="nav-icon">🕐</span> Recent Activity</a>
                <a href="buying.php?tab=saved"  class="subnav-link"><span class="nav-icon">❤️</span> Saved</a>
                <a href="buying.php?tab=bids"   class="subnav-link"><span class="nav-icon">🔨</span> Your Bids</a>
                <a href="marketplace-profile.php?id=<?php echo $current_user_id; ?>" class="subnav-link active"><span class="nav-icon">👤</span> Marketplace Profile</a>
            </div>
            <div class="subnav-footer">
                <a href="create-listing.php" class="btn-create-listing">＋ Create New Listing</a>
            </div>
            <div class="user-info" style="margin-top:10px;">
                <strong><?php echo htmlspecialchars($_SESSION['username'] ?? 'Guest'); ?></strong>
                <p>User ID: <?php echo $current_user_id; ?></p>
            </div>
        </div>
    <?php endif; ?>

    <div class="profile-main">
        <div class="page-container">

            <?php
            // Handle review submission
            $review_msg = '';
            $review_type = '';
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review']) && $current_user_id && !$is_own_profile) {
                $rating  = (int)$_POST['rating'];
                $comment = trim($_POST['comment']);
                $r_listing_id = isset($_POST['listing_id']) ? (int)$_POST['listing_id'] : null;

                if (!$can_review) {
                    $review_msg  = 'You can only review sellers you have had a conversation with.';
                    $review_type = 'error';
                } elseif ($rating >= 1 && $rating <= 5) {
                    $existing_review = $supabase->customQuery('reviews', 'review_id',
                        'seller_id=eq.' . $profile_user_id . '&reviewer_id=eq.' . $current_user_id .
                        ($r_listing_id ? '&listing_id=eq.' . $r_listing_id : ''));
                    if (!empty($existing_review)) {
                        $review_msg  = 'You have already reviewed this seller.';
                        $review_type = 'error';
                    } else {
                        $review_data = [
                            'seller_id'   => $profile_user_id,
                            'reviewer_id' => $current_user_id,
                            'rating'      => $rating,
                            'comment'     => $comment
                        ];
                        if ($r_listing_id) $review_data['listing_id'] = $r_listing_id;

                        $result = $supabase->insert('reviews', $review_data);
                        if ($result) {
                            $review_msg  = 'Review submitted successfully!';
                            $review_type = 'success';
                            $reviews = $supabase->customQuery('reviews', '*',
                                'seller_id=eq.' . $profile_user_id . '&order=created_at.desc');
                            $total_reviews = count($reviews);
                            $avg_rating = $total_reviews > 0
                                ? round(array_sum(array_column($reviews, 'rating')) / $total_reviews, 1) : 0;
                        } else {
                            $review_msg  = 'Failed to submit review. Please try again.';
                            $review_type = 'error';
                        }
                    }
                } else {
                    $review_msg  = 'Please select a star rating.';
                    $review_type = 'error';
                }
            }
            ?>

            <!-- Profile Card -->
            <div class="profile-card">
                <div class="profile-avatar">
                    <?php echo strtoupper(substr($profile_user['first_name'] ?? $profile_user['username'], 0, 1)); ?>
                </div>
                <div class="profile-info">
                    <div class="profile-name"><?php echo htmlspecialchars(($profile_user['first_name'] ?? '') . ' ' . ($profile_user['last_name'] ?? '')); ?></div>
                    <div class="profile-username">@<?php echo htmlspecialchars($profile_user['username']); ?></div>
                    <?php if ($total_reviews > 0): ?>
                        <div style="display:flex; align-items:center; margin-bottom: 10px;">
                            <span class="stars"><?php echo str_repeat('★', round($avg_rating)) . str_repeat('☆', 5 - round($avg_rating)); ?></span>
                            <span class="rating-text"><?php echo $avg_rating; ?> (<?php echo $total_reviews; ?> review<?php echo $total_reviews > 1 ? 's' : ''; ?>)</span>
                        </div>
                    <?php endif; ?>
                    <div class="profile-meta">
                        <?php if (!empty($profile_user['location'])): ?>
                            <span>📍 <?php echo htmlspecialchars($profile_user['location']); ?></span>
                        <?php endif; ?>
                        <span>📅 Member since <?php echo $member_since; ?></span>
                    </div>
                </div>
                <?php if (!$is_own_profile && $current_user_id): ?>
                    <a href="inbox.php?seller_id=<?php echo $profile_user_id; ?>" class="btn-contact">💬 Message</a>
                <?php endif; ?>
            </div>

            <!-- Stats -->
            <div class="stats-row">
                <div class="stat-box">
                    <div class="stat-num"><?php echo $total_listings; ?></div>
                    <div class="stat-label">Total Listings</div>
                </div>
                <div class="stat-box">
                    <div class="stat-num"><?php echo $sold_count; ?></div>
                    <div class="stat-label">Items Sold</div>
                </div>
                <div class="stat-box">
                    <div class="stat-num"><?php echo $total_reviews; ?></div>
                    <div class="stat-label">Reviews</div>
                </div>
                <div class="stat-box">
                    <div class="stat-num"><?php echo $avg_rating > 0 ? $avg_rating : '—'; ?></div>
                    <div class="stat-label">Avg Rating</div>
                </div>
            </div>

            <!-- Active Listings -->
            <div class="section-title">Active Listings (<?php echo count($listings ?? []); ?>)</div>
            <?php if (empty($listings)): ?>
                <div class="empty-state" style="margin-bottom: 24px;">No active listings right now.</div>
            <?php else: ?>
                <div class="listings-grid" style="margin-bottom: 30px;">
                    <?php foreach ($listings as $item): ?>
                        <a href="listing-details.php?id=<?php echo $item['id']; ?>" class="listing-card">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['title']); ?>">
                            <div class="listing-card-info">
                                <div class="listing-price">₱<?php echo number_format($item['price'], 2); ?></div>
                                <div class="listing-title"><?php echo htmlspecialchars($item['title']); ?></div>
                                <div class="listing-loc">📍 <?php echo htmlspecialchars($item['location'] ?? ''); ?></div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Reviews -->
            <div class="section-title" id="reviews-section">Reviews (<?php echo $total_reviews; ?>)</div>

            <?php if (!empty($review_msg)): ?>
                <div class="alert alert-<?php echo $review_type; ?>"><?php echo htmlspecialchars($review_msg); ?></div>
            <?php endif; ?>

            <?php
            $already_reviewed = false;
            if ($current_user_id && !$is_own_profile) {
                $check_query = 'seller_id=eq.' . $profile_user_id . '&reviewer_id=eq.' . $current_user_id;
                if ($review_listing_id) {
                    $check_query .= '&listing_id=eq.' . $review_listing_id;
                }
                $existing = $supabase->customQuery('reviews', 'review_id', $check_query);
                $already_reviewed = !empty($existing);
            }
            ?>

            <?php if (!$is_own_profile && $current_user_id && $can_review && !$already_reviewed): ?>
                <div class="review-form" id="review-form">
                    <h3>⭐ Leave a Review</h3>
                    <p style="font-size:13px;color:#666;margin-bottom:14px;">Share your experience with this seller.</p>
                    <form method="POST" action="">
                        <input type="hidden" name="listing_id" value="<?php echo $review_listing_id ?? ''; ?>">
                        <div class="star-select">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <input type="radio" name="rating" id="star<?php echo $i; ?>" value="<?php echo $i; ?>"
                                       <?php echo (isset($_POST['rating']) && $_POST['rating'] == $i) ? 'checked' : ''; ?>>
                                <label for="star<?php echo $i; ?>">★</label>
                            <?php endfor; ?>
                        </div>
                        <textarea name="comment" placeholder="Share your experience with this seller (optional)"><?php echo isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : ''; ?></textarea>
                        <button type="submit" name="submit_review">Submit Review</button>
                    </form>
                </div>
            <?php elseif (!$is_own_profile && $current_user_id && $already_reviewed): ?>
                <div class="alert alert-success" style="margin-bottom:16px;">✅ You have already reviewed this seller.</div>
            <?php elseif (!$is_own_profile && $current_user_id && !$can_review): ?>
                <div class="empty-state" style="margin-bottom:16px; font-size:14px;">
                    💬 You can leave a review after messaging this seller about a listing.
                </div>
            <?php endif; ?>

            <?php if (empty($reviews)): ?>
                <div class="empty-state">No reviews yet.</div>
            <?php else: ?>
                <div class="reviews-list">
                    <?php foreach ($reviews as $review):
                        $reviewer = $supabase->select('accounts', 'username,first_name', ['account_id' => $review['reviewer_id']], true);
                    ?>
                        <div class="review-card">
                            <div class="review-header">
                                <span class="reviewer-name"><?php echo htmlspecialchars($reviewer['first_name'] ?? $reviewer['username'] ?? 'Anonymous'); ?></span>
                                <span class="review-date"><?php echo date('M d, Y', strtotime($review['created_at'])); ?></span>
                            </div>
                            <div class="review-stars"><?php echo str_repeat('★', $review['rating']) . str_repeat('☆', 5 - $review['rating']); ?></div>
                            <?php if (!empty($review['comment'])): ?>
                                <div class="review-text"><?php echo htmlspecialchars($review['comment']); ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
    <?php if ($auto_scroll_review): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var el = document.getElementById('review-form') || document.getElementById('reviews-section');
            if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    </script>
    <?php endif; ?>
</body>
</html>
