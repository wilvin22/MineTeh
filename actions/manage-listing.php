<?php
session_start();
header('Content-Type: application/json');
include '../database/supabase.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['listing_id']) || !isset($data['action'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$listing_id = (int)$data['listing_id'];
$action = $data['action'];

// Verify the user owns this listing
$listing = $supabase->select('listings', 'seller_id,listing_type,status', ['id' => $listing_id], true);

if (!$listing) {
    echo json_encode(['success' => false, 'message' => 'Listing not found']);
    exit;
}

if ($listing['seller_id'] != $user_id) {
    echo json_encode(['success' => false, 'message' => 'You do not own this listing']);
    exit;
}

// Perform the action
$result = false;

switch ($action) {
    case 'close':
        // Close auction — find highest bidder, notify them, mark listing sold
        $highest_bid_rows = $supabase->customQuery('bids', '*',
            'listing_id=eq.' . $listing_id . '&order=bid_amount.desc&limit=1');
        $winner_bid = !empty($highest_bid_rows) ? $highest_bid_rows[0] : null;

        if ($winner_bid) {
            include_once '../database/notifications_helper.php';
            $notificationHelper = new NotificationsHelper();

            // Notify winner
            $notificationHelper->createNotification(
                $winner_bid['user_id'],
                'listing_sold',
                'You won the auction!',
                'You won "' . $listing['title'] . '" for ₱' . number_format($winner_bid['bid_amount'], 2),
                'listing-details.php?id=' . $listing_id
            );

            // Notify seller
            $winner_rows = $supabase->customQuery('accounts', 'username',
                'account_id=eq.' . $winner_bid['user_id'] . '&limit=1');
            $winner_name = !empty($winner_rows) ? $winner_rows[0]['username'] : 'A buyer';

            $notificationHelper->notifyListingSold(
                $user_id, $listing_id, $listing['title'], $winner_bid['bid_amount'], $winner_name
            );
        }

        $result = $supabase->update('listings', ['status' => 'sold'], ['id' => $listing_id]);
        break;
        
    case 'disable':
        // Disable listing - set status to inactive
        $result = $supabase->update('listings', 
            ['status' => 'inactive'], 
            ['id' => $listing_id]
        );
        break;
        
    case 'enable':
        // Enable listing - set status back to active
        $result = $supabase->update('listings', 
            ['status' => 'active'], 
            ['id' => $listing_id]
        );
        break;
        
    case 'delete':
        // Delete listing permanently
        // First delete related data
        $supabase->delete('listing_images', ['listing_id' => $listing_id]);
        $supabase->delete('bids', ['listing_id' => $listing_id]);
        $supabase->delete('favorites', ['listing_id' => $listing_id]);
        $supabase->delete('cart_items', ['listing_id' => $listing_id]);
        
        // Then delete the listing itself
        $result = $supabase->delete('listings', ['id' => $listing_id]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
}

if ($result !== false) {
    echo json_encode(['success' => true, 'message' => 'Listing updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update listing']);
}
?>
