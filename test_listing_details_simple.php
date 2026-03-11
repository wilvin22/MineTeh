<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Listing Details Test</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// Test basic PHP functionality
echo "<h2>Basic PHP Test</h2>";
echo "<p class='success'>✅ PHP is working</p>";

// Test database connection
try {
    require_once 'database/supabase.php';
    echo "<p class='success'>✅ Database loaded</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
    exit;
}

// Test if we can get a listing
echo "<h2>Listing Query Test</h2>";
try {
    $listings = $supabase->select('listings', '*', [], false);
    if ($listings && count($listings) > 0) {
        echo "<p class='success'>✅ Found " . count($listings) . " listings</p>";
        $test_listing = $listings[0];
        echo "<p class='info'>Test listing ID: " . $test_listing['id'] . "</p>";
        echo "<p class='info'>Test listing title: " . htmlspecialchars($test_listing['title']) . "</p>";
        
        // Test image query
        $images = $supabase->select('listing_images', '*', ['listing_id' => $test_listing['id']]);
        if ($images) {
            echo "<p class='success'>✅ Found " . count($images) . " images for this listing</p>";
        } else {
            echo "<p class='info'>No images found for this listing</p>";
        }
        
        // Test JavaScript generation
        echo "<h2>JavaScript Generation Test</h2>";
        echo "<p class='info'>Testing array_map with images:</p>";
        $js_images = json_encode(array_map(function($img) {
            return str_replace('../', '', $img['image_path']);
        }, $images ?? []));
        echo "<p class='success'>✅ JavaScript array: $js_images</p>";
        
    } else {
        echo "<p class='error'>❌ No listings found</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Listing query error: " . $e->getMessage() . "</p>";
}

echo "<h2>🔗 Navigation</h2>";
if (isset($test_listing)) {
    echo "<p><a href='home/listing-details.php?id=" . $test_listing['id'] . "'>Test Listing Details</a></p>";
}
echo "<p><a href='home/homepage.php'>Homepage</a></p>";
?>