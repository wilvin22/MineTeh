<?php
/**
 * Automatic Auction Closing Script
 * 
 * This script should be run periodically (every 5-10 minutes) via cron job
 * to automatically close expired auctions and notify winners.
 * 
 * Cron job example (run every 5 minutes):
 * Add this line to your crontab:
 * 
 *   asterisk/5 * * * * php /path/to/MineTeh/cron/close-expired-auctions.php
 * 
 * (Replace 'asterisk' with the * symbol)
 */

require_once __DIR__ . '/../database/supabase.php';
require_once __DIR__ . '/../database/notifications_helper.php';

// Log file
$log_file = __DIR__ . '/auction_close.log';

function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
    echo "[$timestamp] $message\n";
}

log_message("=== Starting Auction Closing Script ===");

try {
    global $supabase;
    
    // Get all open auctions that have expired
    $now = date('Y-m-d H:i:s');
    $expired_auctions = $supabase->customQuery('listings', '*', "listing_type=eq.BID&status=eq.OPEN&bid_end_time=lt.$now");
    
    if ($expired_auctions === false) {
        log_message("ERROR: Failed to query expired auctions");
        exit(1);
    }
    
    if (empty($expired_auctions)) {
        log_message("No expired auctions found");
        exit(0);
    }
    
    log_message("Found " . count($expired_auctions) . " expired auction(s)");
    
    foreach ($expired_auctions as $auction) {
        $listing_id = $auction['listing_id'];
        $seller_id = $auction['seller_id'];
        $title = $auction['title'];
        
        log_message("Processing auction #$listing_id: $title");
        
        // Get highest bid
        $bids = $supabase->customQuery('bids', '*', "listing_id=eq.$listing_id&order=bid_amount.desc&limit=1");
        
        if ($bids === false) {
            log_message("ERROR: Failed to get bids for auction #$listing_id");
            continue;
        }
        
        if (empty($bids)) {
            // No bids - close auction without winner
            log_message("Auction #$listing_id has no bids - closing without winner");
            
            $update_result = $supabase->update('listings', [
                'status' => 'CLOSED'
            ], ['listing_id' => $listing_id]);
            
            if ($update_result !== false) {
                // Notify seller that auction ended with no bids
                create_notification(
                    $seller_id,
                    'auction_no_bids',
                    "Your auction \"$title\" has ended with no bids",
                    $listing_id
                );
                
                log_message("SUCCESS: Closed auction #$listing_id (no bids)");
            } else {
                log_message("ERROR: Failed to close auction #$listing_id");
            }
            
            continue;
        }
        
        // Auction has bids - determine winner
        $winning_bid = $bids[0];
        $winner_id = $winning_bid['user_id'];
        $winning_amount = $winning_bid['bid_amount'];
        
        log_message("Winner: User #$winner_id with bid of ₱$winning_amount");
        
        // Update listing status and set winner
        $update_result = $supabase->update('listings', [
            'status' => 'CLOSED',
            'winner_id' => $winner_id,
            'final_price' => $winning_amount
        ], ['listing_id' => $listing_id]);
        
        if ($update_result === false) {
            log_message("ERROR: Failed to update auction #$listing_id");
            continue;
        }
        
        // Notify winner
        create_notification(
            $winner_id,
            'auction_won',
            "Congratulations! You won the auction for \"$title\" with a bid of ₱" . number_format($winning_amount, 2),
            $listing_id
        );
        
        // Notify seller
        create_notification(
            $seller_id,
            'auction_sold',
            "Your auction \"$title\" has ended. Winner: User #$winner_id with ₱" . number_format($winning_amount, 2),
            $listing_id
        );
        
        // Notify all other bidders that they lost
        $all_bids = $supabase->customQuery('bids', 'user_id', "listing_id=eq.$listing_id&user_id=neq.$winner_id");
        
        if ($all_bids !== false) {
            $notified_users = [];
            
            foreach ($all_bids as $bid) {
                $bidder_id = $bid['user_id'];
                
                // Avoid duplicate notifications
                if (in_array($bidder_id, $notified_users)) {
                    continue;
                }
                
                create_notification(
                    $bidder_id,
                    'auction_lost',
                    "The auction for \"$title\" has ended. Unfortunately, you were outbid.",
                    $listing_id
                );
                
                $notified_users[] = $bidder_id;
            }
            
            log_message("Notified " . count($notified_users) . " losing bidder(s)");
        }
        
        log_message("SUCCESS: Closed auction #$listing_id with winner User #$winner_id");
    }
    
    log_message("=== Auction Closing Script Completed Successfully ===");
    exit(0);
    
} catch (Exception $e) {
    log_message("FATAL ERROR: " . $e->getMessage());
    exit(1);
}
?>
