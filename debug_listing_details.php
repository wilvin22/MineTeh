<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Listing Details</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// Test with the same ID from the test
$listing_id = 27; // From the test results

echo "<h2>Step-by-Step Debug</h2>";

// Step 1: Session
session_start();
date_default_timezone_set('Asia/Manila');
echo "<p class='success'>✅ Session started</p>";

// Step 2: Headers
header("Cache-Control: no-cache, no-store, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Expires: 0");
echo "<p class='success'>✅ Headers set</p>";

// Step 3: Includes
try {
    include 'config.php';
    echo "<p class='success'>✅ Config loaded</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Config error: " . $e->getMessage() . "</p>";
}

try {
    include 'database/supabase.php';
    echo "<p class='success'>✅ Database loaded</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
}

// Step 4: User restriction check
echo "<h3>User Restriction Check</h3>";
$user_is_restricted = false;
if (isset($_SESSION['user_id'])) {
    echo "<p class='info'>User ID: " . $_SESSION['user_id'] . "</p>";
    try {
        $user = $supabase->select('accounts', '*', ['account_id' => $_SESSION['user_id']], true);
        if ($user && is_array($user)) {
            $user_status = isset($user['user_status']) ? $user['user_status'] : 'active';
            echo "<p class='info'>User status: $user_status</p>";
        } else {
            echo "<p class='error'>❌ Could not fetch user</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ User query error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='info'>No user logged in</p>";
}

// Step 5: Listing query
echo "<h3>Listing Query</h3>";
try {
    $listing = $supabase->select('listings', '*', ['id' => $listing_id]);
    if ($listing === false || empty($listing) || !is_array($listing)) {
        echo "<p class='error'>❌ Listing query failed or empty</p>";
        echo "<p class='info'>Query result: " . print_r($listing, true) . "</p>";
    } else {
        echo "<p class='success'>✅ Listing found</p>";
        $listing = $listing[0]; // Get first result
        echo "<p class='info'>Title: " . htmlspecialchars($listing['title']) . "</p>";
        echo "<p class='info'>Status: " . $listing['status'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Listing query error: " . $e->getMessage() . "</p>";
}

// Step 6: Images query
echo "<h3>Images Query</h3>";
try {
    $images = $supabase->select('listing_images', '*', ['listing_id' => $listing_id]);
    if (!is_array($images)) {
        $images = [];
    }
    echo "<p class='success'>✅ Found " . count($images) . " images</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Images query error: " . $e->getMessage() . "</p>";
    $images = [];
}

// Step 7: JavaScript generation test
echo "<h3>JavaScript Generation Test</h3>";
try {
    $js_images = json_encode(array_map(function($img) {
        return str_replace('../', '', $img['image_path']);
    }, $images ?? []));
    echo "<p class='success'>✅ JavaScript array: $js_images</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ JavaScript generation error: " . $e->getMessage() . "</p>";
}

// Step 8: Test HTML output
echo "<h3>HTML Output Test</h3>";
echo "<p class='info'>Testing if HTML can be output properly...</p>";

?>
<!DOCTYPE html>
<html>
<head>
    <title>Test HTML</title>
</head>
<body>
    <h4>HTML Section Working</h4>
    <script>
        console.log('JavaScript is working');
        const testImages = <?php echo json_encode(array_map(function($img) {
            return str_replace('../', '', $img['image_path']);
        }, $images ?? [])); ?>;
        console.log('Test images:', testImages);
        document.write('<p style="color: green;">✅ JavaScript executed successfully</p>');
    </script>
    
    <h4>Back to PHP</h4>
    <?php
    echo "<p class='success'>✅ PHP after HTML working</p>";
    echo "<p><a href='home/listing-details.php?id=$listing_id'>Try Real Listing Details</a></p>";
    ?>
</body>
</html>