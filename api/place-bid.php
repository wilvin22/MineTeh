<?php
session_start();
include "../database/supabase.php";
include "../database/notifications_helper.php";

if (!isset($_SESSION['user_id'])) {
    die("Login required");
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
