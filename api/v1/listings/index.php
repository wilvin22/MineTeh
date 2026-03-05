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

if (empty($listings)) {
    sendResponse(true, []);
    exit;
}

// Get all listing IDs
$listingIds = array_column($listings, 'id');

// Get all images for these listings in one query
$allImages = [];
if (!empty($listingIds)) {
    $imageQuery = 'listing_id=in.(' . implode(',', $listingIds) . ')&order=image_id.asc';
    $images = $supabase->customQuery('listing_images', 'listing_id,image_path,image_id', $imageQuery);
    
    if ($images !== false && !empty($images)) {
        // Group images by listing_id
        foreach ($images as $image) {
            $lid = $image['listing_id'];
            if (!isset($allImages[$lid])) {
                $allImages[$lid] = [];
            }
            $allImages[$lid][] = $image['image_path'];
        }
    }
}

// Get all seller IDs
$sellerIds = array_unique(array_column($listings, 'seller_id'));

// Get all sellers in one query
$allSellers = [];
if (!empty($sellerIds)) {
    $sellerQuery = 'account_id=in.(' . implode(',', $sellerIds) . ')';
    $sellers = $supabase->customQuery('accounts', 'account_id,username,first_name,last_name', $sellerQuery);
    
    if ($sellers !== false && !empty($sellers)) {
        foreach ($sellers as $seller) {
            $allSellers[$seller['account_id']] = $seller;
        }
    }
}

// Get highest bids for bid listings in one query
$bidListingIds = [];
foreach ($listings as $listing) {
    if ($listing['listing_type'] === 'BID') {
        $bidListingIds[] = $listing['id'];
    }
}

$highestBids = [];
if (!empty($bidListingIds)) {
    // Get all bids for these listings
    $bidQuery = 'listing_id=in.(' . implode(',', $bidListingIds) . ')&order=listing_id,bid_amount.desc';
    $bids = $supabase->customQuery('bids', 'listing_id,bid_amount', $bidQuery);
    
    if ($bids !== false && !empty($bids)) {
        // Get the highest bid for each listing
        foreach ($bids as $bid) {
            $lid = $bid['listing_id'];
            if (!isset($highestBids[$lid])) {
                $highestBids[$lid] = $bid['bid_amount'];
            }
        }
    }
}

// Attach data to listings
foreach ($listings as &$listing) {
    $listingId = $listing['id'];
    
    // Attach first image
    $listing['image'] = isset($allImages[$listingId]) && !empty($allImages[$listingId]) 
        ? $allImages[$listingId][0] 
        : null;
    
    // Attach seller info
    $listing['seller'] = isset($allSellers[$listing['seller_id']]) 
        ? $allSellers[$listing['seller_id']] 
        : null;
    
    // Attach highest bid for bid listings
    if ($listing['listing_type'] === 'BID') {
        $listing['highest_bid'] = isset($highestBids[$listingId]) 
            ? $highestBids[$listingId] 
            : null;
    }
}

sendResponse(true, $listings);
?>
