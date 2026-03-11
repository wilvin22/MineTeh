<?php
// Enable all error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Start output buffering to catch any errors
ob_start();

echo "Starting debug...\n";

try {
    // Step 1: Basic setup
    session_start();
    date_default_timezone_set('Asia/Manila');
    echo "✅ Session and timezone set\n";

    // Step 2: Headers
    header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo "✅ Headers set\n";

    // Step 3: Includes
    include 'config.php';
    include 'database/supabase.php';
    echo "✅ Includes loaded\n";

    // Step 4: Get listing ID
    $listing_id = 27; // Use the test ID
    echo "✅ Listing ID: $listing_id\n";

    // Step 5: Query listing
    echo "Querying listing...\n";
    $listing = $supabase->select('listings', '*', ['id' => $listing_id]);
    echo "Query result type: " . gettype($listing) . "\n";
    
    if ($listing === false) {
        echo "❌ Query returned false\n";
        $error = $supabase->getLastError();
        if ($error) {
            echo "Error: " . print_r($error, true) . "\n";
        }
        exit;
    }
    
    if (empty($listing)) {
        echo "❌ Query returned empty\n";
        exit;
    }
    
    if (!is_array($listing)) {
        echo "❌ Query result is not array\n";
        exit;
    }
    
    echo "✅ Query successful, got " . count($listing) . " results\n";
    
    // Step 6: Extract first result
    $listing = $listing[0];
    echo "✅ Extracted first result\n";
    echo "Listing title: " . (isset($listing['title']) ? $listing['title'] : 'NO TITLE') . "\n";
    
    // Step 7: Test accessing listing properties
    $test_props = ['title', 'price', 'listing_type', 'status', 'seller_id'];
    foreach ($test_props as $prop) {
        if (isset($listing[$prop])) {
            echo "✅ $prop: " . $listing[$prop] . "\n";
        } else {
            echo "❌ Missing property: $prop\n";
        }
    }
    
    // Step 8: Test images query
    echo "Querying images...\n";
    $images = $supabase->select('listing_images', '*', ['listing_id' => $listing_id]);
    if (!is_array($images)) {
        $images = [];
    }
    echo "✅ Found " . count($images) . " images\n";
    
    // Step 9: Test JavaScript generation
    echo "Testing JavaScript generation...\n";
    $js_images = json_encode(array_map(function($img) {
        return str_replace('../', '', $img['image_path']);
    }, $images ?? []));
    echo "✅ JavaScript: $js_images\n";
    
    echo "\n=== ALL TESTS PASSED ===\n";
    echo "The issue is likely in the HTML/JavaScript section of the actual file.\n";
    
} catch (Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

// Get any output and display it
$output = ob_get_clean();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Step by Step Debug</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f0f0f0; }
        pre { background: white; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>Step by Step Debug Results</h1>
    <pre><?php echo htmlspecialchars($output); ?></pre>
    
    <h2>Next Steps</h2>
    <p><a href="home/listing-details.php?id=27">Try Real Listing Details</a></p>
    <p><a href="test_listing_details_simple.php">Back to Simple Test</a></p>
</body>
</html>