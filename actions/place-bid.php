<?php
session_start();
include "../database/supabase.php";
include "../database/notifications_helper.php";

if (!isset($_SESSION['user_id'])) {
    die("Login required");
}

// Check if user is restricted
$user_status = isset($_SESSION['user_status']) ? $_SESSION['user_status'] : 'active';
if ($user_status === 'restricted') {
    // Get user details to check restriction expiry
    $user = $supabase->select('accounts', 'restriction_until, status_reason', ['account_id' => $_SESSION['user_id']], true);
    
    $restriction_until = isset($user['restriction_until']) ? $user['restriction_until'] : null;
    
    if ($restriction_until && strtotime($restriction_until) <= time()) {
        // Restriction expired, reactivate user
        $supabase->update('accounts', [
            'user_status' => 'active',
            'restriction_until' => null,
            'status_reason' => null
        ], ['account_id' => $_SESSION['user_id']]);
        $_SESSION['user_status'] = 'active';
    } else {
        // Redirect to homepage with error message
        header("Location: ../home/homepage.php?error=restricted");
        exit;
    }
}

$notificationHelper = new NotificationsHelper();

if (isset($_POST['place_bid'])) {
    $user_id = $_SESSION['user_id'];
    $listing_id = $_POST['listing_id'];
    $bid_amount = $_POST['bid_amount'];

    // Get listing info
    $listing = $supabase->select('listings', '*', ['id' => $listing_id], true);

    if (!$listing) {
        die("Listing not found");
    }

    // Check if user is trying to bid on their own listing
    if ($listing['seller_id'] == $user_id) {
        die("You cannot bid on your own auction");
    }

    if ($listing['status'] !== 'OPEN' && $listing['status'] !== 'active') {
        die("Bidding is closed");
    }

    if ($listing['listing_type'] !== 'BID') {
        die("This listing does not accept bids");
    }

    // Check if auction has ended
    if (!empty($listing['end_time'])) {
        $end_time = new DateTime($listing['end_time']);
        $now = new DateTime();
        if ($now > $end_time) {
            die("Auction has ended");
        }
    }

    // Get highest current bid
    $highest_bid = $supabase->customQuery('bids', '*', 
        'listing_id=eq.' . $listing_id . '&order=bid_amount.desc&limit=1');
    
    // Calculate minimum required bid
    $min_bid_increment = isset($listing['min_bid_increment']) ? floatval($listing['min_bid_increment']) : 1.00;
    $starting_price = isset($listing['starting_price']) ? floatval($listing['starting_price']) : floatval($listing['price']);
    
    if (!empty($highest_bid) && is_array($highest_bid)) {
        $min_required_bid = floatval($highest_bid[0]['bid_amount']) + $min_bid_increment;
    } else {
        $min_required_bid = $starting_price;
    }
    
    // Validate bid amount
    if (floatval($bid_amount) < $min_required_bid) {
        die("Bid amount must be at least ₱" . number_format($min_required_bid, 2) . 
            " (minimum increment: ₱" . number_format($min_bid_increment, 2) . ")");
    }

    // Insert bid
    $supabase->insert('bids', [
        'listing_id' => $listing_id,
        'user_id' => $user_id,
        'bid_amount' => $bid_amount
    ]);

    // Get bidder's username
    $bidder = $supabase->select('accounts', 'username', ['account_id' => $user_id], true);
    $bidder_name = $bidder ? $bidder['username'] : 'Someone';

    // Notify seller about new bid
    $notificationHelper->notifyBidReceived(
        $listing['seller_id'],
        $listing_id,
        $listing['title'],
        $bid_amount,
        $bidder_name
    );

    // Check if there are previous bidders to notify (they've been outbid)
    $previous_bids = $supabase->customQuery('bids', '*', 
        'listing_id=eq.' . $listing_id . '&user_id=neq.' . $user_id . '&order=bid_amount.desc');
    
    if ($previous_bids && is_array($previous_bids)) {
        $notified_users = [];
        foreach ($previous_bids as $prev_bid) {
            // Only notify each user once
            if (!in_array($prev_bid['user_id'], $notified_users)) {
                $notificationHelper->notifyOutbid(
                    $prev_bid['user_id'],
                    $listing_id,
                    $listing['title'],
                    $bid_amount
                );
                $notified_users[] = $prev_bid['user_id'];
            }
        }
    }

    header("Location: ../home/listing-details.php?id=$listing_id");
    exit;
}
?>
