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
        // Close auction - set status to CLOSED
        $result = $supabase->update('listings', 
            ['status' => 'CLOSED'], 
            ['id' => $listing_id]
        );
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
