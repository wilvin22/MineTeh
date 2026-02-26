<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

include 'database/supabase.php';

// Test with a specific listing ID
$listing_id = isset($_GET['id']) ? (int)$_GET['id'] : 3;

echo "<h2>Testing Listing Query for ID: $listing_id</h2>";

// Test 1: Direct select
echo "<h3>Test 1: Direct Select</h3>";
$result = $supabase->select('listings', '*', ['id' => $listing_id]);
echo "<pre>";
echo "Result type: " . gettype($result) . "\n";
echo "Result value: ";
var_dump($result);
echo "</pre>";

if ($result === false) {
    echo "<p style='color: red;'>Query failed!</p>";
    $error = $supabase->getLastError();
    if ($error) {
        echo "<pre>";
        print_r($error);
        echo "</pre>";
    }
}

// Test 2: Get all listings
echo "<h3>Test 2: Get All Listings (first 5)</h3>";
$all_listings = $supabase->customQuery('listings', 'id,title,price', 'limit=5');
echo "<pre>";
var_dump($all_listings);
echo "</pre>";

// Test 3: Custom query with specific ID
echo "<h3>Test 3: Custom Query with ID filter</h3>";
$custom_result = $supabase->customQuery('listings', '*', 'id=eq.' . $listing_id);
echo "<pre>";
var_dump($custom_result);
echo "</pre>";
?>
