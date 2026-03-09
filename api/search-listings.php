<?php
session_start();
require_once '../database/supabase.php';

header('Content-Type: application/json');

$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$listing_type = isset($_GET['type']) ? trim($_GET['type']) : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 0;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'created_at.desc';

try {
    // Build query
    $query = 'select=*';
    
    // Add status filter (only show open listings)
    $filters = ['status=eq.OPEN'];
    
    // Search in title and description
    if (!empty($search_query)) {
        $search_encoded = urlencode($search_query);
        $filters[] = 'or=(title.ilike.*' . $search_encoded . '*,description.ilike.*' . $search_encoded . '*)';
    }
    
    // Category filter
    if (!empty($category)) {
        $filters[] = 'category=eq.' . urlencode($category);
    }
    
    // Listing type filter
    if (!empty($listing_type)) {
        $filters[] = 'listing_type=eq.' . urlencode($listing_type);
    }
    
    // Price range filter
    if ($min_price > 0) {
        $filters[] = 'price=gte.' . $min_price;
    }
    if ($max_price > 0) {
        $filters[] = 'price=lte.' . $max_price;
    }
    
    // Combine filters
    if (!empty($filters)) {
        $query .= '&' . implode('&', $filters);
    }
    
    // Add sorting
    $query .= '&order=' . $sort_by;
    
    // Execute query
    $result = supabase_query('listings', $query);
    
    if ($result === false) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to search listings'
        ]);
        exit;
    }
    
    $listings = json_decode($result, true);
    
    // Get first image for each listing
    foreach ($listings as &$listing) {
        $images_query = "select=*&listing_id=eq.{$listing['listing_id']}&order=image_id.asc&limit=1";
        $images_result = supabase_query('listing_images', $images_query);
        
        if ($images_result) {
            $images = json_decode($images_result, true);
            $listing['image'] = !empty($images) ? $images[0]['image_path'] : null;
        } else {
            $listing['image'] = null;
        }
    }
    
    echo json_encode([
        'success' => true,
        'listings' => $listings,
        'count' => count($listings),
        'query' => $search_query
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error searching listings: ' . $e->getMessage()
    ]);
}
?>
