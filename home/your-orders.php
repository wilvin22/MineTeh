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
$view_mode = isset($_GET['view']) ? $_GET['view'] : 'buyer';

// Fetch all conversations this user is part of
$all_convs = $supabase->customQuery('conversations', '*', 'or=(user1_id.eq.' . $user_id . ',user2_id.eq.' . $user_id . ')&order=updated_at.desc');

$processed = [];
if (!empty($all_convs)) {
    foreach ($all_convs as $conv) {
        $listing = $supabase->select('listings', '*', ['id' => $conv['listing_id']], true);
        if (!$listing) continue;

        $is_seller = ($listing['seller_id'] == $user_id);

        // Filter by view mode
        if ($view_mode === 'seller' && !$is_seller) continue;
        if ($view_mode === 'buyer'  &&  $is_seller) continue;

        $images = $supabase->select('listing_images', 'image_path', ['listing_id' => $listing['id']]);
        $conv['image']          = !empty($images) ? getImageUrl($images[0]['image_path']) : BASE_URL . '/assets/no-image.png';
        $conv['listing_title']  = $listing['title'];
        $conv['listing_price']  = $listing['price'];
        $conv['listing_status'] = $listing['status'];
        $conv['seller_id_val']  = $listing['seller_id'];

        // Other party = the person who is NOT the current user
        $other_id = ($conv['user1_id'] == $user_id) ? $conv['user2_id'] : $conv['user1_id'];
        $other = $supabase->select('accounts', 'username', ['account_id' => $other_id], true);
        $conv['other_party'] = $other ? $other['username'] : 'Unknown';

        $msgs = $supabase->customQuery('messages', 'message_text,created_at', 'conversation_id=eq.' . $conv['conversation_id'] . '&order=created_at.desc&limit=1');
        $conv['last_message']      = !empty($msgs) ? $msgs[0]['message_text'] : 'No messages yet';
        $conv['last_message_time'] = !empty($msgs) ? $msgs[0]['created_at'] : $conv['created_at'];
        $processed[] = $conv;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Conversations - MineTeh</title>
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <style>
        body { background: #f5f7fa; min-height: 100vh; }
        .page-container { max-width: 1000px; margin: 0 auto; padding: 30px 20px; }
        .page-header { text-align: center; margin-bottom: 35px; }
        .page-title { font-size: 32px; font-weight: bold; color: #333; margin-bottom: 8px; }
        .page-subtitle { font-size: 15px; color: #666; }
        .view-toggle { display: flex; justify-content: center; gap: 15px; margin-bottom: 30px; }
        .toggle-btn { padding: 11px 28px; border: 2px solid #945a9b; background: white; color: #945a9b; border-radius: 25px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; text-decoration: none; }
        .toggle-btn:hover { background: #f8f4f9; }
        .toggle-btn.active { background: #945a9b; color: white; }
        .conv-list { display: flex; flex-direction: column; gap: 16px; }
        .conv-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); display: flex; gap: 18px; align-items: center; transition: box-shadow 0.2s ease; text-decoration: none; color: inherit; }
        .conv-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.14); }
        .conv-image { width: 90px; height: 90px; object-fit: cover; border-radius: 10px; flex-shrink: 0; }
        .conv-info { flex: 1; min-width: 0; }
        .conv-title { font-size: 17px; font-weight: bold; color: #333; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .conv-price { color: #945a9b; font-weight: bold; font-size: 16px; margin-bottom: 6px; }
        .conv-meta { font-size: 13px; color: #666; margin-bottom: 6px; }
        .conv-last-msg { font-size: 13px; color: #888; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .conv-time { font-size: 12px; color: #aaa; white-space: nowrap; flex-shrink: 0; align-self: flex-start; }
        .status-badge { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; margin-left: 8px; }
        .status-active { background: #d1e7dd; color: #0f5132; }
        .status-sold { background: #f8d7da; color: #842029; }
        .status-inactive { background: #e2e3e5; color: #41464b; }
        .empty-state { text-align: center; padding: 60px 20px; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .empty-icon { font-size: 60px; margin-bottom: 16px; }
        .empty-title { font-size: 22px; font-weight: bold; color: #333; margin-bottom: 8px; }
        .empty-text { color: #666; margin-bottom: 20px; }
        .btn-primary { display: inline-block; padding: 12px 24px; background: #945a9b; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; }
        .btn-primary:hover { background: #6a406e; }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>
    <div class="main-wrapper">
        <div class="page-container">
            <div class="page-header">
                <h1 class="page-title">💬 My Conversations</h1>
                <p class="page-subtitle">All your buyer-seller conversations in one place</p>
            </div>
            <div class="view-toggle">
                <a href="?view=buyer" class="toggle-btn <?php echo $view_mode === 'buyer' ? 'active' : ''; ?>">🛒 Items I'm Interested In</a>
                <a href="?view=seller" class="toggle-btn <?php echo $view_mode === 'seller' ? 'active' : ''; ?>">💰 Inquiries on My Listings</a>
            </div>
            <?php if (empty($processed)): ?>
                <div class="empty-state">
                    <div class="empty-icon">💬</div>
                    <h2 class="empty-title">No Conversations Yet</h2>
                    <p class="empty-text">
                        <?php if ($view_mode === 'buyer'): ?>
                            You haven't contacted any sellers yet. Find something you like!
                        <?php else: ?>
                            No one has messaged you about your listings yet.
                        <?php endif; ?>
                    </p>
                    <a href="homepage.php" class="btn-primary">Browse Listings</a>
                </div>
            <?php else: ?>
                <div class="conv-list">
                    <?php foreach ($processed as $conv): ?>
                        <a href="inbox.php?conversation_id=<?php echo $conv['conversation_id']; ?>" class="conv-card">
                            <img src="<?php echo htmlspecialchars($conv['image']); ?>" alt="<?php echo htmlspecialchars($conv['listing_title']); ?>" class="conv-image">
                            <div class="conv-info">
                                <div class="conv-title">
                                    <?php echo htmlspecialchars($conv['listing_title']); ?>
                                    <span class="status-badge status-<?php echo strtolower($conv['listing_status']); ?>"><?php echo ucfirst($conv['listing_status']); ?></span>
                                </div>
                                <div class="conv-price">₱<?php echo number_format($conv['listing_price'], 2); ?></div>
                                <div class="conv-meta">
                                    <?php echo $view_mode === 'buyer' ? 'Seller' : 'Buyer'; ?>: <?php echo htmlspecialchars($conv['other_party']); ?>
                                </div>
                                <div class="conv-last-msg">💬 <?php echo htmlspecialchars(mb_strimwidth($conv['last_message'], 0, 80, '...')); ?></div>
                            </div>
                            <div class="conv-time"><?php echo date('M d', strtotime($conv['last_message_time'])); ?></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
