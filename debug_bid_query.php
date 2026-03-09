<?php
session_start();
include 'database/supabase.php';

// Test the bid query for both listings
$laptop_id = 5;  // Laptop listing ID
$pc_id = 17;     // PC listing ID

echo "<h2>Debug: Bid Queries</h2>";

// Laptop bids
echo "<h3>Laptop (ID: $laptop_id) Bids:</h3>";
$laptop_bids = $supabase->customQuery('bids', '*', 'listing_id=eq.' . $laptop_id . '&order=bid_amount.desc');
echo "<pre>";
print_r($laptop_bids);
echo "</pre>";

// PC bids
echo "<h3>PC (ID: $pc_id) Bids:</h3>";
$pc_bids = $supabase->customQuery('bids', '*', 'listing_id=eq.' . $pc_id . '&order=bid_amount.desc');
echo "<pre>";
print_r($pc_bids);
echo "</pre>";
?>
