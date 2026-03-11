<?php
session_start();
require_once '../database/supabase.php';

header('Content-Type: application/json');

if (!isset($_GET['listing_id'])) {
    echo json_encode(['success' => false, 'message' => 'Listing ID required']);
    exit;
}

$listing_id = (int)$_GET['listing_id'];

try {
    // Get all bids for this listing with user information
    // Note: Supabase doesn't support joins in the same way, so we'll get bids first, then get user info
    $bids = $supabase->customQuery('bids', 'bid_id,bid_amount,created_at,user_id', 
        "listing_id=eq.$listing_id&order=bid_amount.desc,created_at.desc");
    
    if ($bids === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch bids']);
        exit;
    }
    
    // Get user information for each bid
    $formatted_bids = [];
    foreach ($bids as $bid) {
        $user = $supabase->select('users', 'username', ['user_id' => $bid['user_id']], true);
        
        $formatted_bids[] = [
            'bid_id' => $bid['bid_id'],
            'bid_amount' => $bid['bid_amount'],
            'created_at' => $bid['created_at'],
            'username' => $user['username'] ?? 'Anonymous'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'bids' => $formatted_bids,
        'count' => count($formatted_bids)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching bid history: ' . $e->getMessage()
    ]);
}
?>
