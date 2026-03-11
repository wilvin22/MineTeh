<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
date_default_timezone_set('Asia/Manila');

include 'config.php';
include 'database/supabase.php';

$listing_id = isset($_GET['id']) ? (int)$_GET['id'] : 30;

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

// Get seller
$seller = $supabase->select('accounts', 'username,first_name,last_name', ['account_id' => $listing['seller_id']]);
$seller = !empty($seller) && is_array($seller) ? $seller[0] : ['username' => 'Unknown', 'first_name' => 'Unknown', 'last_name' => 'Seller'];

// Check favorites
$is_favorited = false;
if (isset($_SESSION['user_id'])) {
    $favorite_check = $supabase->select('favorites', '*', [
        'user_id' => $_SESSION['user_id'],
        'listing_id' => $listing_id
    ]);
    $is_favorited = !empty($favorite_check);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($listing['title']); ?> - MineTeh</title>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; max-width: 800px; margin: 0 auto; }
        .listing-header { border-bottom: 2px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
        .listing-title { font-size: 28px; font-weight: bold; color: #333; margin-bottom: 10px; }
        .listing-price { font-size: 24px; color: #e74c3c; font-weight: bold; margin-bottom: 15px; }
        .listing-meta { display: flex; gap: 20px; margin-bottom: 15px; color: #666; }
        .listing-description { line-height: 1.6; margin: 20px 0; }
        .action-btn { padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; margin: 5px; text-decoration: none; display: inline-block; text-align: center; }
        .btn-primary { background: #3498db; color: white; }
        .btn-secondary { background: #95a5a6; color: white; }
        .success { color: green; }
        .error { color: red; }
        #debug-output { background: #f0f0f0; padding: 15px; margin: 20px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="listing-header">
        <h1 class="listing-title"><?php echo htmlspecialchars($listing['title']); ?></h1>
        <div class="listing-price">₱<?php echo number_format($listing['price'], 2); ?></div>
        <div class="listing-meta">
            <span>📍 <?php echo htmlspecialchars($listing['location'] ?? 'Unknown'); ?></span>
            <span>🏷️ <?php echo htmlspecialchars($listing['listing_type'] ?? 'Unknown'); ?></span>
            <span>🕒 <?php echo date('M d, Y', strtotime($listing['created_at'])); ?></span>
        </div>
    </div>
    
    <div class="listing-description">
        <h3>Description</h3>
        <p><?php echo nl2br(htmlspecialchars($listing['description'] ?? 'No description')); ?></p>
    </div>
    
    <div>
        <h3>Seller Information</h3>
        <p><strong><?php echo htmlspecialchars($seller['first_name'] . ' ' . $seller['last_name']); ?></strong></p>
        <p>@<?php echo htmlspecialchars($seller['username']); ?></p>
    </div>
    
    <div>
        <h3>Actions</h3>
        <?php if (isset($listing['listing_type']) && $listing['listing_type'] == 'FIXED'): ?>
            <button onclick="addToCart()" class="action-btn btn-primary">🛒 Add to Cart</button>
            <a href="checkout.php?listing_id=<?php echo $listing_id; ?>" class="action-btn btn-primary">Buy Now</a>
        <?php endif; ?>
        
        <button onclick="toggleFavorite()" class="action-btn btn-secondary">
            <?php echo $is_favorited ? '❤️ Favorited' : '🤍 Add to Favorites'; ?>
        </button>
    </div>
    
    <div id="debug-output">
        <h3>Debug Information</h3>
        <p>Listing ID: <?php echo $listing_id; ?></p>
        <p>Images found: <?php echo count($images); ?></p>
        <p>JavaScript status: <span id="js-status">Not executed</span></p>
    </div>

    <script>
        console.log('Minimal listing script starting...');
        
        // Test all the problematic JavaScript generation
        try {
            // Test 1: Images array
            const images = <?php echo json_encode(array_map(function($img) {
                return str_replace('../', '', $img['image_path']);
            }, $images ?? [])); ?>;
            console.log('✅ Images array:', images);
            
            // Test 2: Countdown timer date
            const endTime = new Date('<?php echo isset($listing['bid_end_time']) && $listing['bid_end_time'] ? $listing['bid_end_time'] : '2099-12-31 23:59:59'; ?>').getTime();
            console.log('✅ End time:', endTime);
            
            // Test 3: Bid variables
            const currentBid = <?php echo isset($listing['current_bid']) ? $listing['current_bid'] : (isset($listing['starting_price']) ? $listing['starting_price'] : 0); ?>;
            const minIncrement = <?php echo isset($listing['min_bid_increment']) ? $listing['min_bid_increment'] : 10; ?>;
            console.log('✅ Bid variables:', currentBid, minIncrement);
            
            // Test 4: Listing data
            const listingData = {
                id: <?php echo $listing_id; ?>,
                title: <?php echo json_encode($listing['title']); ?>,
                type: <?php echo json_encode($listing['listing_type'] ?? 'UNKNOWN'); ?>
            };
            console.log('✅ Listing data:', listingData);
            
            document.getElementById('js-status').innerHTML = '<span class="success">✅ All JavaScript executed successfully!</span>';
            
        } catch (error) {
            console.error('❌ JavaScript error:', error);
            document.getElementById('js-status').innerHTML = '<span class="error">❌ JavaScript error: ' + error.message + '</span>';
        }
        
        // Test functions
        function addToCart() {
            alert('Add to cart clicked - Listing ID: <?php echo $listing_id; ?>');
        }
        
        function toggleFavorite() {
            alert('Toggle favorite clicked - Currently: <?php echo $is_favorited ? "favorited" : "not favorited"; ?>');
        }
        
        console.log('Minimal listing script completed successfully!');
    </script>
    
    <p style="margin-top: 30px;">
        <a href="home/listing-details.php?id=<?php echo $listing_id; ?>">← Try Full Listing Details</a> |
        <a href="homepage.php">← Back to Homepage</a>
    </p>
</body>
</html>