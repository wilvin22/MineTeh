<?php
// Enable maximum error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Start output buffering to catch any errors
ob_start();

try {
    // Simulate the exact same process as listing-details.php
    session_start();
    date_default_timezone_set('Asia/Manila');

    // Headers
    header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");

    include 'config.php';
    include 'database/supabase.php';

    // Use the same ID from the URL
    $listing_id = 30; // From the screenshot URL

    echo "<!-- Debug: Starting listing query -->\n";
    
    // Get listing details
    $listing = $supabase->select('listings', '*', ['id' => $listing_id]);
    
    if ($listing === false || empty($listing) || !is_array($listing)) {
        echo "<!-- Debug: Listing query failed -->\n";
        die("Listing not found - Debug mode");
    }
    
    echo "<!-- Debug: Listing query successful -->\n";
    $listing = $listing[0];
    
    echo "<!-- Debug: Listing extracted -->\n";
    
    // Check what fields exist in the listing
    echo "<!-- Debug: Listing fields: " . implode(', ', array_keys($listing)) . " -->\n";
    
    // Get images
    $images = $supabase->select('listing_images', '*', ['listing_id' => $listing_id]);
    if (!is_array($images)) {
        $images = [];
    }
    
    echo "<!-- Debug: Images query complete -->\n";
    
    // Get seller info
    $seller = $supabase->select('accounts', 'username,first_name,last_name', ['account_id' => $listing['seller_id']]);
    $seller = !empty($seller) && is_array($seller) ? $seller[0] : ['username' => 'Unknown', 'first_name' => 'Unknown', 'last_name' => 'Seller'];
    
    echo "<!-- Debug: Seller query complete -->\n";
    
    // Get bids if it's a bid listing
    $bids = [];
    $highest_bid = null;
    if (isset($listing['listing_type']) && $listing['listing_type'] === 'BID') {
        $bids = $supabase->customQuery('bids', '*', 'listing_id=eq.' . $listing_id . '&order=bid_amount.desc');
        if (!is_array($bids)) {
            $bids = [];
        }
        $highest_bid = !empty($bids) ? $bids[0] : null;
    }
    
    echo "<!-- Debug: Bids query complete -->\n";
    
    // Check if user has favorited this listing
    $is_favorited = false;
    if (isset($_SESSION['user_id'])) {
        $favorite_check = $supabase->select('favorites', '*', [
            'user_id' => $_SESSION['user_id'],
            'listing_id' => $listing_id
        ]);
        $is_favorited = !empty($favorite_check);
    }
    
    echo "<!-- Debug: Favorites query complete -->\n";
    
} catch (Exception $e) {
    echo "<!-- Debug: Exception caught: " . $e->getMessage() . " -->\n";
    echo "<!-- Debug: File: " . $e->getFile() . " Line: " . $e->getLine() . " -->\n";
    die("Error: " . $e->getMessage());
} catch (Error $e) {
    echo "<!-- Debug: Fatal error caught: " . $e->getMessage() . " -->\n";
    echo "<!-- Debug: File: " . $e->getFile() . " Line: " . $e->getLine() . " -->\n";
    die("Fatal Error: " . $e->getMessage());
}

// Get any buffered output
$debug_output = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Listing Error</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .debug { background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
    </style>
</head>
<body>
    <h1>Debug Listing Error</h1>
    
    <div class="debug">
        <h3>Debug Output:</h3>
        <pre><?php echo htmlspecialchars($debug_output); ?></pre>
    </div>
    
    <div class="debug">
        <h3>Listing Data:</h3>
        <pre><?php echo htmlspecialchars(print_r($listing, true)); ?></pre>
    </div>
    
    <div class="debug">
        <h3>JavaScript Test:</h3>
        <div id="js-test">JavaScript not executed</div>
    </div>
    
    <script>
        console.log('Debug script starting...');
        
        // Test the problematic JavaScript generation
        try {
            const images = <?php echo json_encode(array_map(function($img) {
                return str_replace('../', '', $img['image_path']);
            }, $images ?? [])); ?>;
            console.log('✅ Images:', images);
            
            const endTime = new Date('<?php echo isset($listing['bid_end_time']) && $listing['bid_end_time'] ? $listing['bid_end_time'] : '2099-12-31 23:59:59'; ?>').getTime();
            console.log('✅ End time:', endTime);
            
            const currentBid = <?php echo isset($listing['current_bid']) ? $listing['current_bid'] : (isset($listing['starting_price']) ? $listing['starting_price'] : 0); ?>;
            console.log('✅ Current bid:', currentBid);
            
            document.getElementById('js-test').innerHTML = '<span class="success">✅ JavaScript executed successfully!</span>';
            
        } catch (error) {
            console.error('❌ JavaScript error:', error);
            document.getElementById('js-test').innerHTML = '<span class="error">❌ JavaScript error: ' + error.message + '</span>';
        }
    </script>
    
    <p><a href="home/listing-details.php?id=<?php echo $listing_id; ?>">Try Real Listing Details</a></p>
</body>
</html>