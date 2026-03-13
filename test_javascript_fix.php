<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate the listing data that might be causing issues
$listing = [
    'title' => 'Test Listing',
    'price' => 1000,
    'listing_type' => 'BID',
    'status' => 'OPEN',
    'description' => 'Test description',
    'location' => 'Test location',
    'created_at' => '2024-01-01 12:00:00',
    'seller_id' => 1,
    // Note: bid_end_time is intentionally missing to test the fix
    // Note: current_bid is intentionally missing to test the fix
    // Note: starting_price is intentionally missing to test the fix
    // Note: min_bid_increment is intentionally missing to test the fix
];

$listing_id = 28;
$images = [
    ['image_path' => 'uploads/test.jpg']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JavaScript Fix Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
    </style>
</head>
<body>
    <h1>JavaScript Fix Test</h1>
    <div id="test-results"></div>
    
    <script>
        console.log('Starting JavaScript tests...');
        
        // Test 1: Image array generation (the original issue)
        try {
            const images = <?php echo json_encode(array_map(function($img) {
                return str_replace('../', '', $img['image_path']);
            }, $images ?? [])); ?>;
            console.log('✅ Images array:', images);
            document.getElementById('test-results').innerHTML += '<p class="success">✅ Images array generated successfully</p>';
        } catch (error) {
            console.error('❌ Images array error:', error);
            document.getElementById('test-results').innerHTML += '<p class="error">❌ Images array failed: ' + error.message + '</p>';
        }
        
        // Test 2: Countdown timer (the main issue)
        try {
            const endTime = new Date('<?php echo isset($listing['bid_end_time']) && $listing['bid_end_time'] ? $listing['bid_end_time'] : '2099-12-31 23:59:59'; ?>').getTime();
            console.log('✅ End time:', endTime);
            document.getElementById('test-results').innerHTML += '<p class="success">✅ Countdown timer date parsing successful</p>';
        } catch (error) {
            console.error('❌ Countdown timer error:', error);
            document.getElementById('test-results').innerHTML += '<p class="error">❌ Countdown timer failed: ' + error.message + '</p>';
        }
        
        // Test 3: Bid validation variables
        try {
            const currentBid = <?php echo isset($listing['current_bid']) ? $listing['current_bid'] : (isset($listing['starting_price']) ? $listing['starting_price'] : 0); ?>;
            const minIncrement = <?php echo isset($listing['min_bid_increment']) ? $listing['min_bid_increment'] : 10; ?>;
            console.log('✅ Current bid:', currentBid, 'Min increment:', minIncrement);
            document.getElementById('test-results').innerHTML += '<p class="success">✅ Bid validation variables generated successfully</p>';
        } catch (error) {
            console.error('❌ Bid validation error:', error);
            document.getElementById('test-results').innerHTML += '<p class="error">❌ Bid validation failed: ' + error.message + '</p>';
        }
        
        // Test 4: Listing ID
        try {
            const listingId = <?php echo $listing_id; ?>;
            console.log('✅ Listing ID:', listingId);
            document.getElementById('test-results').innerHTML += '<p class="success">✅ Listing ID generated successfully</p>';
        } catch (error) {
            console.error('❌ Listing ID error:', error);
            document.getElementById('test-results').innerHTML += '<p class="error">❌ Listing ID failed: ' + error.message + '</p>';
        }
        
        console.log('All JavaScript tests completed!');
        document.getElementById('test-results').innerHTML += '<p class="info">🎉 All JavaScript tests completed! Check console for details.</p>';
        document.getElementById('test-results').innerHTML += '<p><a href="home/listing-details.php?id=28">Test Real Listing Details</a></p>';
    </script>
</body>
</html>