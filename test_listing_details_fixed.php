<?php
// Test file to verify listing-details.php fixes
session_start();
date_default_timezone_set('Asia/Manila');

include 'config.php';
include 'database/supabase.php';

// Test with a known listing ID
$listing_id = 28; // Use an existing listing ID

echo "<h2>Testing Listing Details Fixes</h2>";

// Test 1: Check if listing exists
echo "<h3>Test 1: Listing Query</h3>";
$listing = $supabase->select('listings', '*', ['id' => $listing_id]);
if ($listing && !empty($listing)) {
    echo "✅ Listing found: " . htmlspecialchars($listing[0]['title']) . "<br>";
    $listing = $listing[0];
} else {
    echo "❌ Listing not found<br>";
    exit;
}

// Test 2: Check images array handling
echo "<h3>Test 2: Images Array</h3>";
$images = $supabase->select('listing_images', '*', ['listing_id' => $listing_id]);
if (!is_array($images)) {
    $images = [];
}

$imageArray = [];
if (is_array($images) && !empty($images)) {
    foreach ($images as $img) {
        if (isset($img['image_path'])) {
            $imageArray[] = str_replace('../', '', $img['image_path']);
        }
    }
}

echo "✅ Images array processed successfully. Count: " . count($imageArray) . "<br>";
echo "Images JSON: " . json_encode($imageArray) . "<br>";

// Test 3: Check favorites table column
echo "<h3>Test 3: Favorites Table Structure</h3>";
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $favorite_check = $supabase->select('favorites', '*', [
        'user_id' => $user_id,
        'listing_id' => $listing_id
    ]);
    
    if ($favorite_check !== false) {
        echo "✅ Favorites query successful using 'user_id' column<br>";
        echo "Is favorited: " . (empty($favorite_check) ? 'No' : 'Yes') . "<br>";
    } else {
        echo "❌ Favorites query failed<br>";
        echo "Error: " . json_encode($supabase->getLastError()) . "<br>";
    }
} else {
    echo "ℹ️ No user logged in to test favorites<br>";
}

// Test 4: Check cart table column
echo "<h3>Test 4: Cart Table Structure</h3>";
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $cart_check = $supabase->select('cart', '*', [
        'user_id' => $user_id,
        'listing_id' => $listing_id
    ]);
    
    if ($cart_check !== false) {
        echo "✅ Cart query successful using 'user_id' column<br>";
        echo "In cart: " . (empty($cart_check) ? 'No' : 'Yes') . "<br>";
    } else {
        echo "❌ Cart query failed<br>";
        echo "Error: " . json_encode($supabase->getLastError()) . "<br>";
    }
} else {
    echo "ℹ️ No user logged in to test cart<br>";
}

echo "<h3>✅ All Tests Completed</h3>";
echo "<p><a href='home/listing-details.php?id=$listing_id'>Test the actual listing page</a></p>";
?>