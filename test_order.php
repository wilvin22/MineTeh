<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
include 'database/supabase.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Please log in first");
}

$user_id = $_SESSION['user_id'];

echo "<h2>Testing Order Creation</h2>";

// Get a test listing
$listings = $supabase->select('listings', '*', ['status' => 'active']);
if (empty($listings)) {
    die("No active listings found. Please create a listing first.");
}

$listing = $listings[0];
echo "<h3>Test Listing:</h3>";
echo "<pre>" . print_r($listing, true) . "</pre>";

// Get buyer info
$buyer = $supabase->select('accounts', '*', ['account_id' => $user_id], true);
echo "<h3>Buyer Info:</h3>";
echo "<pre>" . print_r($buyer, true) . "</pre>";

// Prepare test order data
$order_data = [
    'buyer_id' => $user_id,
    'seller_id' => $listing['seller_id'],
    'listing_id' => $listing['id'],
    'total_amount' => $listing['price'],
    'payment_method' => 'cod',
    'payment_status' => 'pending',
    'delivery_address' => 'Test Address, Test City, Test Province 1234',
    'delivery_method' => 'standard',
    'status' => 'pending'
];

echo "<h3>Order Data to Insert:</h3>";
echo "<pre>" . print_r($order_data, true) . "</pre>";

// Try to insert
echo "<h3>Attempting to insert order...</h3>";
$result = $supabase->insert('orders', $order_data);

echo "<h3>Insert Result:</h3>";
echo "<pre>" . print_r($result, true) . "</pre>";

if ($result && !empty($result[0])) {
    echo "<h3 style='color: green;'>✓ Order created successfully!</h3>";
    echo "Order ID: " . $result[0]['order_id'];
    
    // Clean up - delete the test order
    $supabase->delete('orders', ['order_id' => $result[0]['order_id']]);
    echo "<p><em>Test order has been deleted.</em></p>";
} else {
    echo "<h3 style='color: red;'>✗ Order creation failed!</h3>";
    
    // Get detailed error information
    $error = $supabase->getLastError();
    if ($error) {
        echo "<h3>Error Details:</h3>";
        echo "<pre>" . print_r($error, true) . "</pre>";
        
        echo "<h4>Error Response:</h4>";
        echo "<pre>" . htmlspecialchars($error['response']) . "</pre>";
    }
    
    // Check if orders table exists and what columns it has
    echo "<h3>Checking orders table structure...</h3>";
    $test_select = $supabase->select('orders', '*', ['order_id' => 999999]);
    echo "<pre>Table query result: " . print_r($test_select, true) . "</pre>";
}
?>
