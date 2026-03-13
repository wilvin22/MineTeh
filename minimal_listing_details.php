<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
date_default_timezone_set('Asia/Manila');

include 'config.php';
include 'database/supabase.php';

$listing_id = isset($_GET['id']) ? (int)$_GET['id'] : 27;

// Get listing
$listing = $supabase->select('listings', '*', ['id' => $listing_id]);
if ($listing === false || empty($listing) || !is_array($listing)) {
    die("Listing not found");
}
$listing = $listing[0];

// Get images
$images = $supabase->select('listing_images', '*', ['listing_id' => $listing_id]);
if (!is_array($images)) {
    $images = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($listing['title']); ?> - MineTeh</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($listing['title']); ?></h1>
        <p><strong>Price:</strong> ₱<?php echo number_format($listing['price'], 2); ?></p>
        <p><strong>Type:</strong> <?php echo $listing['listing_type']; ?></p>
        <p><strong>Status:</strong> <?php echo $listing['status']; ?></p>
        <p><strong>Images:</strong> <?php echo count($images); ?> found</p>
        
        <div id="debug-output"></div>
        
        <p><a href="home/listing-details.php?id=<?php echo $listing_id; ?>">Try Full Listing Details</a></p>
    </div>

    <script>
        console.log('Minimal listing details loaded');
        
        // Test the same JavaScript generation that's causing issues
        const images = <?php echo json_encode(array_map(function($img) {
            return str_replace('../', '', $img['image_path']);
        }, $images ?? [])); ?>;
        
        console.log('Images array:', images);
        
        // Test listing data access
        const listingData = {
            id: <?php echo $listing_id; ?>,
            title: <?php echo json_encode($listing['title']); ?>,
            price: <?php echo $listing['price']; ?>,
            type: <?php echo json_encode($listing['listing_type']); ?>
        };
        
        console.log('Listing data:', listingData);
        
        document.getElementById('debug-output').innerHTML = 
            '<p class="success">✅ JavaScript executed successfully!</p>' +
            '<p>Images: ' + JSON.stringify(images) + '</p>' +
            '<p>Listing: ' + JSON.stringify(listingData) + '</p>';
    </script>
</body>
</html>