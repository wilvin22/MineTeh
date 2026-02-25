<?php
session_start();
include "database/supabase.php";

// Set a test user session
$_SESSION['user_id'] = 12; // Replace with your actual user ID

// Test the delete image API
$test_data = json_encode(['image_id' => 1]); // Replace with actual image ID

// Simulate the API call
$_POST = [];
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Capture the API output
ob_start();
include "api/delete-image.php";
$output = ob_get_clean();

echo "API Response: " . $output . "\n";
echo "Is valid JSON: " . (json_decode($output) ? 'Yes' : 'No') . "\n";

// Test getting images for a listing
echo "\nTesting image retrieval:\n";
$images = $supabase->select('listing_images', '*', ['listing_id' => 4]); // Replace with your listing ID
echo "Images found: " . count($images) . "\n";
foreach ($images as $image) {
    echo "Image ID: " . $image['id'] . ", Path: " . $image['image_path'] . "\n";
}
?>