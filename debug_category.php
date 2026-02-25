<?php
session_start();
include "database/supabase.php";

// Test getting a specific listing to see category data
$listing_id = 4; // Change this to your actual listing ID

echo "Testing category data for listing ID: $listing_id\n\n";

$listing = $supabase->select('listings', '*', ['id' => $listing_id], true);

if ($listing) {
    echo "Listing data:\n";
    echo "  Category (old): " . ($listing['category'] ?? 'NOT SET') . "\n";
    echo "  Category ID (new): " . ($listing['category_id'] ?? 'NOT SET') . "\n";
    echo "  Full listing data: " . json_encode($listing) . "\n\n";
    
    // Get categories table
    echo "Categories table:\n";
    $categories = $supabase->select('categories', '*', []);
    foreach ($categories as $cat) {
        echo "  ID: {$cat['category_id']}, Slug: {$cat['category_slug']}, Name: {$cat['category_name']}\n";
    }
} else {
    echo "Listing not found\n";
}
?>