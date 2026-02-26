<?php
require_once __DIR__ . '/../config.php';

$userId = requireAuth();

// Get user's favorites
$favorites = $supabase->select('favorites', '*', ['user_id' => $userId]);

if (empty($favorites)) {
    sendResponse(true, []);
}

// Get listing details for each favorite
$listings = [];
foreach ($favorites as $favorite) {
    $listing = $supabase->select('listings', '*', ['id' => $favorite['listing_id']]);
    
    if (!empty($listing)) {
        $listing = $listing[0];
        
        // Get first image
        $images = $supabase->select('listing_images', 'image_path', ['listing_id' => $listing['id']]);
        $listing['image'] = !empty($images) ? $images[0]['image_path'] : null;
        
        // Get seller info
        $seller = $supabase->select('accounts', 'username,first_name,last_name', ['account_id' => $listing['seller_id']]);
        $listing['seller'] = !empty($seller) ? $seller[0] : null;
        
        $listings[] = $listing;
    }
}

sendResponse(true, $listings);
?>
