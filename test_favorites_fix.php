<?php
// Test file to verify favorites functionality
session_start();
date_default_timezone_set('Asia/Manila');

include 'config.php';
include 'database/supabase.php';

echo "<h2>Testing Favorites Functionality</h2>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "❌ No user logged in. Please log in first.<br>";
    echo "<a href='login.php'>Login</a><br>";
    exit;
}

$user_id = $_SESSION['user_id'];
echo "✅ User logged in: ID = $user_id<br><br>";

// Test 1: Check favorites table structure
echo "<h3>Test 1: Favorites Table Structure</h3>";
$favorites = $supabase->select('favorites', '*', ['user_id' => $user_id]);

if ($favorites === false) {
    echo "❌ Favorites table query failed<br>";
    echo "Error: " . json_encode($supabase->getLastError()) . "<br>";
} else {
    echo "✅ Favorites table accessible<br>";
    echo "Current favorites: " . count($favorites) . "<br>";
    
    if (!empty($favorites)) {
        echo "<h4>Current Favorites:</h4>";
        foreach ($favorites as $fav) {
            echo "- Listing ID: " . $fav['listing_id'] . "<br>";
        }
    }
}

// Test 2: Get a test listing
echo "<h3>Test 2: Get Test Listing</h3>";
$test_listings = $supabase->select('listings', 'id,title', [], false, 1);
if (!empty($test_listings)) {
    $test_listing = $test_listings[0];
    $test_listing_id = $test_listing['id'];
    echo "✅ Test listing found: ID = $test_listing_id, Title = " . htmlspecialchars($test_listing['title']) . "<br>";
    
    // Check if already favorited
    $is_favorited = false;
    if (!empty($favorites)) {
        foreach ($favorites as $fav) {
            if ($fav['listing_id'] == $test_listing_id) {
                $is_favorited = true;
                break;
            }
        }
    }
    
    echo "Current status: " . ($is_favorited ? "❤️ Favorited" : "🤍 Not favorited") . "<br>";
    
    // Test buttons
    echo "<h4>Test Actions:</h4>";
    if ($is_favorited) {
        echo "<button onclick='testRemoveFavorite($test_listing_id)'>Test Remove from Favorites</button><br>";
    } else {
        echo "<button onclick='testAddFavorite($test_listing_id)'>Test Add to Favorites</button><br>";
    }
    echo "<button onclick='testToggleFavorite($test_listing_id)'>Test Toggle Favorite</button><br>";
    
} else {
    echo "❌ No test listings found<br>";
}

echo "<h3>✅ Favorites Tests Ready</h3>";
echo "<p><a href='home/saved-items.php'>Go to Saved Items Page</a></p>";
?>

<script>
function testAddFavorite(listingId) {
    testFavoriteAction(listingId, 'add');
}

function testRemoveFavorite(listingId) {
    testFavoriteAction(listingId, 'remove');
}

function testToggleFavorite(listingId) {
    // Determine current state and toggle
    const action = confirm('Current state will be toggled. Continue?') ? 'toggle' : null;
    if (!action) return;
    
    // For toggle, we need to determine current state
    // This is a simplified test - in real app we'd check the UI state
    const currentAction = Math.random() > 0.5 ? 'add' : 'remove';
    testFavoriteAction(listingId, currentAction);
}

function testFavoriteAction(listingId, action) {
    console.log('Testing favorite action:', action, 'for listing:', listingId);
    
    fetch('actions/favorite-action.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            listing_id: listingId,
            action: action
        })
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            alert('✅ Favorite ' + action + ' successful!');
            location.reload();
        } else {
            alert('❌ Favorite ' + action + ' failed: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Favorite ' + action + ' error: ' + error.message);
    });
}
</script>