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
    $bids_query = "select=bid_id,bid_amount,created_at,user_id,users!inner(username)&listing_id=eq.$listing_id&order=bid_amount.desc,created_at.desc";
    $bids_result = supabase_query('bids', $bids_query);
    
    if ($bids_result === false) {
        echo json_encode(['success' => false, 'message' => 'Failed to fetch bids']);
        exit;
    }
    
    $bids = json_decode($bids_result, true);
    
    // Format the response
    $formatted_bids = array_map(function($bid) {
        return [
            'bid_id' => $bid['bid_id'],
            'bid_amount' => $bid['bid_amount'],
            'created_at' => $bid['created_at'],
            'username' => $bid['users']['username'] ?? 'Anonymous'
        ];
    }, $bids);
    
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
