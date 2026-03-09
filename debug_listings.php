<?php
session_start();
include 'database/supabase.php';

// Get all BID listings
$listings = $supabase->customQuery('listings', 'id,title,price,min_bid_increment,listing_type', 'listing_type=eq.BID&order=id.desc&limit=10');

echo "<h2>Debug: Listing Prices and Increments</h2>";
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Title</th><th>Price</th><th>Min Bid Increment</th></tr>";

if ($listings && is_array($listings)) {
    foreach ($listings as $listing) {
        echo "<tr>";
        echo "<td>" . $listing['id'] . "</td>";
        echo "<td>" . htmlspecialchars($listing['title']) . "</td>";
        echo "<td>₱" . number_format($listing['price'], 2) . "</td>";
        echo "<td>₱" . number_format($listing['min_bid_increment'] ?? 0, 2) . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='4'>No BID listings found</td></tr>";
}

echo "</table>";
?>
