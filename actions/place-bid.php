<?php
session_start();
include "../database/supabase.php";
include "../database/notifications_helper.php";

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Login required']));
}

// Check if user is restricted
$user_status = isset($_SESSION['user_status']) ? $_SESSION['user_status'] : 'active';
if ($user_status === 'restricted') {
    $user_row = $supabase->customQuery('accounts', 'restriction_until', 'account_id=eq.' . (int)$_SESSION['user_id'] . '&limit=1');
    $user_row = !empty($user_row) ? $user_row[0] : null;
    $restriction_until = $user_row['restriction_until'] ?? null;

    if ($restriction_until && strtotime($restriction_until) <= time()) {
        $supabase->update('accounts', [
            'user_status'      => 'active',
            'restriction_until'=> null,
            'status_reason'    => null
        ], ['account_id' => $_SESSION['user_id']]);
        $_SESSION['user_status'] = 'active';
    } else {
        header("Location: ../home/homepage.php?error=restricted");
        exit;
    }
}

$notificationHelper = new NotificationsHelper();

if (isset($_POST['place_bid'])) {
    $user_id    = (int)$_SESSION['user_id'];
    $listing_id = (int)$_POST['listing_id'];
    $bid_amount = floatval($_POST['bid_amount']);

    // Get listing
    $listing_rows = $supabase->customQuery('listings', '*', 'id=eq.' . $listing_id . '&limit=1');
    $listing = !empty($listing_rows) ? $listing_rows[0] : null;

    if (!$listing) {
        die("Listing not found.");
    }

    if ($listing['seller_id'] == $user_id) {
        die("You cannot bid on your own listing.");
    }

    if ($listing['listing_type'] !== 'BID') {
        die("This listing does not accept bids.");
    }

    // Accept both 'active' and 'OPEN' as valid statuses
    $valid_statuses = ['active', 'OPEN'];
    if (!in_array($listing['status'], $valid_statuses)) {
        die("Bidding is closed for this listing.");
    }

    // Check auction end time
    if (!empty($listing['end_time']) && strtotime($listing['end_time']) <= time()) {
        die("This auction has ended.");
    }

    // Get current highest bid
    $highest_bid_rows = $supabase->customQuery('bids', 'bid_amount',
        'listing_id=eq.' . $listing_id . '&order=bid_amount.desc&limit=1');
    $highest_bid = !empty($highest_bid_rows) ? $highest_bid_rows[0] : null;

    $min_increment    = isset($listing['min_bid_increment']) ? floatval($listing['min_bid_increment']) : 1.00;
    $starting_price   = isset($listing['starting_price'])   ? floatval($listing['starting_price'])   : floatval($listing['price']);
    $min_required_bid = $highest_bid ? floatval($highest_bid['bid_amount']) + $min_increment : $starting_price;

    if ($bid_amount < $min_required_bid) {
        die("Bid must be at least ₱" . number_format($min_required_bid, 2) .
            " (increment: ₱" . number_format($min_increment, 2) . ").");
    }

    // Insert bid
    $inserted = $supabase->insert('bids', [
        'listing_id' => $listing_id,
        'user_id'    => $user_id,
        'bid_amount' => $bid_amount
    ]);

    if (!$inserted) {
        $err = $supabase->getLastError();
        die("Failed to place bid: " . ($err ? $err['response'] : 'unknown error'));
    }

    // Notify seller
    $bidder_rows = $supabase->customQuery('accounts', 'username', 'account_id=eq.' . $user_id . '&limit=1');
    $bidder_name = !empty($bidder_rows) ? $bidder_rows[0]['username'] : 'Someone';

    $notificationHelper->notifyBidReceived(
        $listing['seller_id'], $listing_id, $listing['title'], $bid_amount, $bidder_name
    );

    // Notify previously outbid users (each only once)
    $prev_bids = $supabase->customQuery('bids', 'user_id,bid_amount',
        'listing_id=eq.' . $listing_id . '&user_id=neq.' . $user_id . '&order=bid_amount.desc');

    if (!empty($prev_bids) && is_array($prev_bids)) {
        $notified = [];
        foreach ($prev_bids as $pb) {
            if (!in_array($pb['user_id'], $notified)) {
                $notificationHelper->notifyOutbid($pb['user_id'], $listing_id, $listing['title'], $bid_amount);
                $notified[] = $pb['user_id'];
            }
        }
    }

    header("Location: ../home/listing-details.php?id=" . $listing_id . "&bid=success");
    exit;
}
?>
