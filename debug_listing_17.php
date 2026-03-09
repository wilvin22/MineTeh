<?php
session_start();
include 'database/supabase.php';

$listing_id = 17; // PC listing

echo "<h2>Debug: Listing ID $listing_id</h2>";

// Get listing details
$listing = $supabase->select('listings', '*', ['id' => $listing_id]);
echo "<h3>Listing Data:</h3>";
echo "<pre>";
print_r($listing);
echo "</pre>";

// Get bids
$bids = $supabase->customQuery('bids', '*', 'listing_id=eq.' . $listing_id . '&order=bid_amount.desc');
echo "<h3>Bids Query: listing_id=eq.$listing_id&order=bid_amount.desc</h3>";
echo "<h3>Bids Result:</h3>";
echo "<pre>";
print_r($bids);
echo "</pre>";

echo "<h3>Bids is_array: " . (is_array($bids) ? 'YES' : 'NO') . "</h3>";
echo "<h3>Bids empty: " . (empty($bids) ? 'YES' : 'NO') . "</h3>";
echo "<h3>Bids count: " . (is_array($bids) ? count($bids) : 'N/A') . "</h3>";

// Check highest bid
$highest_bid = null;
if (!empty($bids) && is_array($bids)) {
    $highest_bid = $bids[0];
}

echo "<h3>Highest Bid:</h3>";
echo "<pre>";
print_r($highest_bid);
echo "</pre>";

// Calculate minimum bid
if ($listing && is_array($listing)) {
    $listing = $listing[0];
    $min_bid_increment = isset($listing['min_bid_increment']) ? floatval($listing['min_bid_increment']) : 1.00;
    $starting_price = isset($listing['starting_price']) ? floatval($listing['starting_price']) : floatval($listing['price']);
    $min_next_bid = $highest_bid ? floatval($highest_bid['bid_amount']) + $min_bid_increment : $starting_price;
    
    echo "<h3>Calculation:</h3>";
    echo "min_bid_increment: ₱" . number_format($min_bid_increment, 2) . "<br>";
    echo "starting_price: ₱" . number_format($starting_price, 2) . "<br>";
    echo "highest_bid exists: " . ($highest_bid ? 'YES' : 'NO') . "<br>";
    if ($highest_bid) {
        echo "highest_bid amount: ₱" . number_format($highest_bid['bid_amount'], 2) . "<br>";
    }
    echo "<strong>min_next_bid: ₱" . number_format($min_next_bid, 2) . "</strong><br>";
}
?>
