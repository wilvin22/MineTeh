<?php
require_once __DIR__ . '/../config.php';

// Get listing ID
$listingId = $_GET['id'] ?? null;

if (!$listingId) {
    sendError('Listing ID is required');
}

// Get listing
$listing = $supabase->select('listings', '*', ['id' => (int)$listingId]);

if (empty($listing)) {
    sendError('Listing not found', 404);
}

$listing = $listing[0];

// Get all images
$images = $supabase->select('listing_images', '*', ['listing_id' => (int)$listingId]);
$listing['images'] = $images ?: [];

// Get seller info
$seller = $supabase->select('accounts', 'account_id,username,first_name,last_name', ['account_id' => $listing['seller_id']]);
$listing['seller'] = !empty($seller) ? $seller[0] : null;

// For bid listings, get bid history
if ($listing['listing_type'] === 'BID') {
    $bids = $supabase->customQuery('bids', '*', 'listing_id=eq.' . $listingId . '&order=bid_amount.desc');
    
    if (!empty($bids)) {
        // Get bidder info for each bid
        foreach ($bids as &$bid) {
            $bidder = $supabase->select('accounts', 'username', ['account_id' => $bid['user_id']]);
            $bid['bidder'] = !empty($bidder) ? $bidder[0] : null;
        }
        $listing['bids'] = $bids;
        $listing['highest_bid'] = $bids[0];
    } else {
        $listing['bids'] = [];
        $listing['highest_bid'] = null;
    }
}

// Check if current user has favorited (if authenticated)
$userId = getAuthenticatedUserId();
if ($userId) {
    $favorite = $supabase->select('favorites', '*', [
        'user_id' => $userId,
        'listing_id' => (int)$listingId
    ]);
    $listing['is_favorited'] = !empty($favorite);
} else {
    $listing['is_favorited'] = false;
}

sendResponse(true, $listing);
?>
