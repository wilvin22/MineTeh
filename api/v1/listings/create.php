<?php
require_once __DIR__ . '/../config.php';

$userId = requireAuth();

// Get request data
$data = getRequestData();

$title = $data['title'] ?? '';
$description = $data['description'] ?? '';
$price = $data['price'] ?? 0;
$location = $data['location'] ?? '';
$category = $data['category'] ?? '';
$listingType = $data['listing_type'] ?? 'FIXED';
$endTime = $data['end_time'] ?? null;
$minBidIncrement = $data['min_bid_increment'] ?? 1;

// Validate required fields
if (empty($title) || empty($description) || empty($location) || empty($category)) {
    sendError('Title, description, location, and category are required');
}

if ($price <= 0) {
    sendError('Price must be greater than 0');
}

// Prepare listing data
$listingData = [
    'seller_id' => $userId,
    'title' => $title,
    'description' => $description,
    'price' => $price,
    'location' => $location,
    'category' => $category,
    'listing_type' => $listingType,
    'status' => 'active',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s')
];

// Add bid-specific fields
if ($listingType === 'BID') {
    $listingData['starting_price'] = $price;
    $listingData['current_price'] = $price;
    $listingData['min_bid_increment'] = $minBidIncrement;
    
    if ($endTime) {
        $listingData['end_time'] = $endTime;
    }
}

// Insert listing
$result = $supabase->insert('listings', $listingData);

if ($result === false) {
    sendError('Failed to create listing', 500);
}

// Get the created listing
$newListing = $supabase->customQuery('listings', '*', 'seller_id=eq.' . $userId . '&order=created_at.desc&limit=1');

if (empty($newListing)) {
    sendError('Listing created but failed to retrieve details', 500);
}

sendResponse(true, $newListing[0], 'Listing created successfully', 201);
?>
