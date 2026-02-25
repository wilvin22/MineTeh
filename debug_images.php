<?php
session_start();
include "database/supabase.php";

// Test getting images for a specific listing
$listing_id = 4; // Change this to your actual listing ID

echo "Testing image retrieval for listing ID: $listing_id\n\n";

$images = $supabase->select('listing_images', '*', ['listing_id' => $listing_id]);

echo "Number of images found: " . count($images) . "\n\n";

if (!empty($images)) {
    echo "Image data structure:\n";
    foreach ($images as $index => $image) {
        echo "Image $index:\n";
        echo "  Raw data: " . json_encode($image) . "\n";
        echo "  ID: " . (isset($image['id']) ? $image['id'] : 'NOT SET') . "\n";
        echo "  Path: " . (isset($image['image_path']) ? $image['image_path'] : 'NOT SET') . "\n";
        echo "  Listing ID: " . (isset($image['listing_id']) ? $image['listing_id'] : 'NOT SET') . "\n";
        echo "\n";
    }
} else {
    echo "No images found. Let's check if the table exists and has data:\n";
    
    // Try to get all images
    $all_images = $supabase->select('listing_images', '*', []);
    echo "Total images in table: " . count($all_images) . "\n";
    
    if (!empty($all_images)) {
        echo "Sample image data: " . json_encode($all_images[0]) . "\n";
    }
}
?>