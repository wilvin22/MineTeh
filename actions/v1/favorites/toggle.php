<?php
require_once __DIR__ . '/../config.php';

$userId = requireAuth();

// Get request data
$data = getRequestData();
$listingId = $data['listing_id'] ?? null;

if (!$listingId) {
    sendError('Listing ID is required');
}

// Check if already favorited
$existing = $supabase->select('favorites', '*', [
    'user_id' => $userId,
    'listing_id' => (int)$listingId
]);

if (!empty($existing)) {
    // Remove from favorites
    $result = $supabase->delete('favorites', [
        'user_id' => $userId,
        'listing_id' => (int)$listingId
    ]);
    
    if ($result === false) {
        sendError('Failed to remove from favorites', 500);
    }
    
    sendResponse(true, ['is_favorited' => false], 'Removed from favorites');
} else {
    // Add to favorites
    $favoriteData = [
        'user_id' => $userId,
        'listing_id' => (int)$listingId,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $result = $supabase->insert('favorites', $favoriteData);
    
    if ($result === false) {
        sendError('Failed to add to favorites', 500);
    }
    
    sendResponse(true, ['is_favorited' => true], 'Added to favorites');
}
?>
