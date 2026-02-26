<?php
require_once __DIR__ . '/../config.php';

// Get query parameters
$category = $_GET['category'] ?? null;
$type = $_GET['type'] ?? null; // BID or FIXED
$search = $_GET['search'] ?? null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;

// Build query
$query = 'status=eq.active';

if ($category) {
    $query .= '&category=eq.' . urlencode($category);
}

if ($type) {
    $query .= '&listing_type=eq.' . urlencode($type);
}

if ($search) {
    $query .= '&title=ilike.*' . urlencode($search) . '*';
}

$query .= '&order=created_at.desc&limit=' . $limit . '&offset=' . $offset;

// Get listings
$listings = $supabase->customQuery('listings', '*', $query);

if ($listings === false) {
    sendError('Failed to fetch listings', 500);
}

// Get first image for each listing
foreach ($listings as &$listing) {
    $images = $supabase->select('listing_images', 'image_path', ['listing_id' => $listing['id']]);
    $listing['image'] = !empty($images) ? $images[0]['image_path'] : null;
    
    // Get seller info
    $seller = $supabase->select('accounts', 'username,first_name,last_name', ['account_id' => $listing['seller_id']]);
    $listing['seller'] = !empty($seller) ? $seller[0] : null;
    
    // For bid listings, get highest bid
    if ($listing['listing_type'] === 'BID') {
        $bids = $supabase->customQuery('bids', 'bid_amount', 'listing_id=eq.' . $listing['id'] . '&order=bid_amount.desc&limit=1');
        $listing['highest_bid'] = !empty($bids) ? $bids[0]['bid_amount'] : null;
    }
}

sendResponse(true, $listings);
?>
