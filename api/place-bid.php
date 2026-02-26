<?php
session_start();
include "../database/supabase.php";

if (!isset($_SESSION['user_id'])) {
    die("Login required");
}

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

    header("Location: ../home/listing-details.php?id=$listing_id");
    exit;
}
?>
