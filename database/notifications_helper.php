<?php
require_once __DIR__ . '/supabase.php';

class NotificationsHelper {
    private $supabase;
    
    public function __construct() {
        $this->supabase = new SupabaseClient();
    }
    
    /**
     * Create a new notification
     */
    public function createNotification($user_id, $type, $title, $message, $link = null) {
        $data = [
            'user_id' => $user_id,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => false
        ];
        
        return $this->supabase->insert('notifications', $data);
    }
    
    /**
     * Notify seller when someone bids on their listing
     */
    public function notifyBidReceived($seller_id, $listing_id, $listing_title, $bid_amount, $bidder_name) {
        $title = "New bid on your listing!";
        $message = "$bidder_name placed a bid of ₱" . number_format($bid_amount, 2) . " on \"$listing_title\"";
        $link = "listing-details.php?id=$listing_id";
        
        return $this->createNotification($seller_id, 'bid_received', $title, $message, $link);
    }
    
    /**
     * Notify bidder when they've been outbid
     */
    public function notifyOutbid($bidder_id, $listing_id, $listing_title, $new_bid_amount) {
        $title = "You've been outbid!";
        $message = "Someone placed a higher bid of ₱" . number_format($new_bid_amount, 2) . " on \"$listing_title\"";
        $link = "listing-details.php?id=$listing_id";
        
        return $this->createNotification($bidder_id, 'outbid', $title, $message, $link);
    }
    
    /**
     * Notify seller when their listing sells
     */
    public function notifyListingSold($seller_id, $listing_id, $listing_title, $final_price, $buyer_name) {
        $title = "Your listing sold!";
        $message = "\"$listing_title\" sold to $buyer_name for ₱" . number_format($final_price, 2);
        $link = "your-listings.php";
        
        return $this->createNotification($seller_id, 'listing_sold', $title, $message, $link);
    }
    
    /**
     * Notify user of new message
     */
    public function notifyNewMessage($user_id, $sender_name, $conversation_id) {
        $title = "New message from $sender_name";
        $message = "You have a new message from $sender_name";
        $link = "messages.php?conversation_id=$conversation_id";
        
        return $this->createNotification($user_id, 'new_message', $title, $message, $link);
    }
    
    /**
     * Notify buyer of order update
     */
    public function notifyOrderUpdate($buyer_id, $order_id, $status, $listing_title) {
        $title = "Order update";
        $message = "Your order for \"$listing_title\" is now $status";
        $link = "your-orders.php";
        
        return $this->createNotification($buyer_id, 'order_update', $title, $message, $link);
    }
    
    /**
     * Get unread notification count for a user
     */
    public function getUnreadCount($user_id) {
        $notifications = $this->supabase->customQuery('notifications', 'id', 
            'user_id=eq.' . $user_id . '&is_read=eq.false');
        
        return $notifications ? count($notifications) : 0;
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notification_id, $user_id) {
        return $this->supabase->update('notifications', 
            ['is_read' => true],
            'id=eq.' . $notification_id . '&user_id=eq.' . $user_id
        );
    }
    
    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead($user_id) {
        return $this->supabase->update('notifications', 
            ['is_read' => true],
            'user_id=eq.' . $user_id . '&is_read=eq.false'
        );
    }
}

// Standalone helper function for quick notification creation
function create_notification($user_id, $type, $message, $listing_id = null) {
    global $supabase;
    
    $data = [
        'user_id' => $user_id,
        'type' => $type,
        'title' => ucfirst(str_replace('_', ' ', $type)),
        'message' => $message,
        'link' => $listing_id ? "listing-details.php?id=$listing_id" : null,
        'is_read' => false
    ];
    
    return $supabase->insert('notifications', $data);
}
?>
