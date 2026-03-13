<?php
// Test file to verify cart remove functionality
session_start();
date_default_timezone_set('Asia/Manila');

include 'config.php';
include 'database/supabase.php';

echo "<h2>Testing Cart Remove Functionality</h2>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "❌ No user logged in. Please log in first.<br>";
    echo "<a href='login.php'>Login</a><br>";
    exit;
}

$user_id = $_SESSION['user_id'];
echo "✅ User logged in: ID = $user_id<br><br>";

// Test 1: Check cart table structure
echo "<h3>Test 1: Cart Table Structure</h3>";
$cart_items = $supabase->select('cart', '*', ['user_id' => $user_id]);

if ($cart_items === false) {
    echo "❌ Cart table query failed<br>";
    echo "Error: " . json_encode($supabase->getLastError()) . "<br>";
} else {
    echo "✅ Cart table accessible<br>";
    echo "Current cart items: " . count($cart_items) . "<br>";
    
    if (!empty($cart_items)) {
        echo "<h4>Current Cart Items:</h4>";
        foreach ($cart_items as $item) {
            echo "- Listing ID: " . $item['listing_id'] . ", Quantity: " . $item['quantity'] . "<br>";
        }
    }
}

// Test 2: Test remove action endpoint
echo "<h3>Test 2: Remove Action Endpoint Test</h3>";
if (!empty($cart_items)) {
    $test_listing_id = $cart_items[0]['listing_id'];
    echo "Testing remove for listing ID: $test_listing_id<br>";
    
    // Simulate the remove request
    $test_data = [
        'listing_id' => $test_listing_id,
        'action' => 'remove'
    ];
    
    echo "Test data: " . json_encode($test_data) . "<br>";
    echo "✅ Remove endpoint should work with this data<br>";
    echo "<button onclick='testRemove($test_listing_id)'>Test Remove This Item</button><br>";
} else {
    echo "ℹ️ No items in cart to test remove functionality<br>";
    echo "Add some items to cart first, then test remove<br>";
}

echo "<h3>✅ Cart Remove Tests Completed</h3>";
echo "<p><a href='home/cart.php'>Go to Cart Page</a></p>";
?>

<script>
function testRemove(listingId) {
    if (!confirm('Test remove listing ID ' + listingId + ' from cart?')) return;
    
    fetch('actions/cart-action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            listing_id: listingId,
            action: 'remove'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ Remove test successful!');
            location.reload();
        } else {
            alert('❌ Remove test failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Remove test error: ' + error.message);
    });
}
</script>