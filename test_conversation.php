<?php
session_start();
include 'database/supabase.php';

echo "<h2>Testing Conversation Creation</h2>";
echo "<style>body{font-family:Arial;padding:20px;} pre{background:#f5f5f5;padding:10px;border-radius:5px;}</style>";

// First, get actual user IDs from database
echo "<h3>Step 1: Get actual user IDs</h3>";
$users = $supabase->select('accounts', 'account_id,username', []);
echo "<pre>";
print_r($users);
echo "</pre>";

if (empty($users) || count($users) < 2) {
    echo "<p style='color:red;'>❌ Need at least 2 users in database. Please create accounts first.</p>";
    exit;
}

$user_id = $users[0]['account_id'];
$seller_id = $users[1]['account_id'];

echo "<p>Using user_id: $user_id, seller_id: $seller_id</p>";

// Get a listing
echo "<h3>Step 2: Get a listing</h3>";
$listings = $supabase->select('listings', 'id,title', []);
echo "<pre>";
print_r($listings);
echo "</pre>";

if (empty($listings)) {
    echo "<p style='color:red;'>❌ No listings found. Please create a listing first.</p>";
    exit;
}

$listing_id = $listings[0]['id'];
echo "<p>Using listing_id: $listing_id</p>";

// Test 1: Check existing conversations
echo "<h3>Step 3: Check for existing conversation</h3>";
$existing = $supabase->customQuery('conversations', '*', 
    'listing_id=eq.' . $listing_id . '&or=(and(user1_id.eq.' . $user_id . ',user2_id.eq.' . $seller_id . '),and(user1_id.eq.' . $seller_id . ',user2_id.eq.' . $user_id . '))');
echo "<pre>";
print_r($existing);
echo "</pre>";

if (!empty($existing)) {
    echo "<p style='color:green;'>✅ Found existing conversation: " . $existing[0]['conversation_id'] . "</p>";
    echo "<p><a href='home/messages.php?conversation_id=" . $existing[0]['conversation_id'] . "'>Go to conversation</a></p>";
} else {
    echo "<p>No existing conversation found. Creating new one...</p>";
    
    // Test 2: Create new conversation
    echo "<h3>Step 4: Create new conversation</h3>";
    $new_conversation = $supabase->insert('conversations', [
        'user1_id' => $user_id,
        'user2_id' => $seller_id,
        'listing_id' => $listing_id
    ]);
    
    echo "<p>Raw response:</p><pre>";
    print_r($new_conversation);
    echo "</pre>";
    
    if ($new_conversation === false) {
        echo "<p style='color:red;'>❌ Insert failed! Check error details below:</p>";
        $error = $supabase->getLastError();
        if ($error) {
            echo "<pre style='background:#ffe6e6;padding:15px;border-left:4px solid red;'>";
            echo "HTTP Code: " . $error['http_code'] . "\n";
            echo "Response: " . $error['response'] . "\n";
            echo "URL: " . $error['url'] . "\n";
            echo "</pre>";
        }
    } elseif (is_array($new_conversation) && !empty($new_conversation)) {
        if (isset($new_conversation[0]['conversation_id'])) {
            $conv_id = $new_conversation[0]['conversation_id'];
            echo "<p style='color:green;'>✅ Conversation created with ID: $conv_id</p>";
            echo "<p><a href='home/messages.php?conversation_id=$conv_id'>Go to conversation</a></p>";
        } elseif (isset($new_conversation['conversation_id'])) {
            $conv_id = $new_conversation['conversation_id'];
            echo "<p style='color:green;'>✅ Conversation created with ID: $conv_id</p>";
            echo "<p><a href='home/messages.php?conversation_id=$conv_id'>Go to conversation</a></p>";
        } else {
            echo "<p style='color:orange;'>⚠️ Conversation created but ID not in expected format</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ Unexpected response format</p>";
    }
}

// Test 3: Get all conversations
echo "<h3>Step 5: All conversations in database</h3>";
$all = $supabase->select('conversations', '*', []);
if ($all === false) {
    echo "<p style='color:red;'>❌ Failed to fetch conversations</p>";
    $error = $supabase->getLastError();
    if ($error) {
        echo "<pre style='background:#ffe6e6;padding:15px;'>";
        print_r($error);
        echo "</pre>";
    }
} else {
    echo "<pre>";
    print_r($all);
    echo "</pre>";
    echo "<p>Total conversations: " . count($all) . "</p>";
}
?>
