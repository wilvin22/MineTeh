<?php
require_once __DIR__ . '/../config.php';

$userId = requireAuth();

// Get request data
$data = getRequestData();
$listingId = $data['listing_id'] ?? null;
$bidAmount = $data['bid_amount'] ?? 0;

// Validate input
if (!$listingId || $bidAmount <= 0) {
    sendError('Listing ID and valid bid amount are required');
}

// Get listing
$listing = $supabase->select('listings', '*', ['id' => (int)$listingId]);

if (empty($listing)) {
    sendError('Listing not found', 404);
}

$listing = $listing[0];

// Check if listing is a bid type
if ($listing['listing_type'] !== 'BID') {
    sendError('This listing is not an auction');
}

// Check if auction has ended
if (!empty($listing['end_time']) && strtotime($listing['end_time']) < time()) {
    sendError('Auction has ended');
}

// Check if user is the seller
if ($listing['seller_id'] == $userId) {
    sendError('You cannot bid on your own listing');
}

// Get highest bid
$highestBid = $supabase->customQuery('bids', '*', 'listing_id=eq.' . $listingId . '&order=bid_amount.desc&limit=1');

$minBidIncrement = $listing['min_bid_increment'] ?? 1;
$minNextBid = !empty($highestBid) ? $highestBid[0]['bid_amount'] + $minBidIncrement : $listing['starting_price'];

// Validate bid amount
if ($bidAmount < $minNextBid) {
    sendError('Bid must be at least ₱' . number_format($minNextBid, 2));
}

// Place bid
$bidData = [
    'listing_id' => (int)$listingId,
    'user_id' => $userId,
    'bid_amount' => $bidAmount,
    'bid_time' => date('Y-m-d H:i:s')
];

$result = $supabase->insert('bids', $bidData);

if ($result === false) {
    sendError('Failed to place bid', 500);
}

// Update listing current price
$supabase->update('listings', ['current_price' => $bidAmount], ['id' => (int)$listingId]);

sendResponse(true, [
    'bid_amount' => $bidAmount,
    'listing_id' => $listingId
], 'Bid placed successfully', 201);
?>
