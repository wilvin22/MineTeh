<?php
session_start();
include "../database/supabase.php";
include "../database/notifications_helper.php";

$listing_id = $_GET['id'];
$seller_id = $_SESSION['user_id'];

// Get listing details
$listing = $supabase->select('listings', '*', ['id' => $listing_id], true);

if ($listing && $listing['seller_id'] == $seller_id) {
    // Get highest bid
    $highest_bid = $supabase->customQuery('bids', '*', 
        'listing_id=eq.' . $listing_id . '&order=bid_amount.desc&limit=1');
    
    if ($highest_bid && is_array($highest_bid) && !empty($highest_bid)) {
        $winner_bid = $highest_bid[0];
        
        // Get winner info
        $winner = $supabase->select('accounts', 'username', ['account_id' => $winner_bid['user_id']], true);
        $winner_name = $winner ? $winner['username'] : 'A buyer';
        
        // Create notification helper
        $notificationHelper = new NotificationsHelper();
        
        // Notify seller
        $notificationHelper->notifyListingSold(
            $seller_id,
            $listing_id,
            $listing['title'],
            $winner_bid['bid_amount'],
            $winner_name
        );
        
        // Notify winner
        $notificationHelper->createNotification(
            $winner_bid['user_id'],
            'listing_sold',
            'Congratulations! You won the auction!',
            'You won "' . $listing['title'] . '" for ₱' . number_format($winner_bid['bid_amount'], 2),
            'listing-details.php?id=' . $listing_id
        );
    }
    
    // Close the listing
    $supabase->update('listings', 
        ['status' => 'CLOSED'],
        ['id' => $listing_id, 'seller_id' => $seller_id]
    );
}

header("Location: ../home/listing-details.php?id=$listing_id");
exit;
?>
