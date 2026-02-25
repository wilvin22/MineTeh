<?php
session_start();
include 'database/supabase.php';

if (!isset($_SESSION['user_id'])) {
    die("Please log in first");
}

$user_id = $_SESSION['user_id'];

echo "<h2>Testing Unread Messages</h2>";
echo "<style>body{font-family:Arial;padding:20px;} pre{background:#f5f5f5;padding:10px;}</style>";

// Get all conversations for this user
echo "<h3>Your Conversations</h3>";
$user_convs = $supabase->customQuery('conversations', '*', 
    'or=(user1_id.eq.' . $user_id . ',user2_id.eq.' . $user_id . ')');

echo "<pre>";
print_r($user_convs);
echo "</pre>";

if (empty($user_convs)) {
    echo "<p>No conversations found.</p>";
} else {
    $total_unread = 0;
    
    foreach ($user_convs as $conv) {
        echo "<h3>Conversation ID: " . $conv['conversation_id'] . "</h3>";
        
        // Get all messages in this conversation
        $all_msgs = $supabase->customQuery('messages', '*', 
            'conversation_id=eq.' . $conv['conversation_id']);
        
        echo "<p>Total messages: " . count($all_msgs) . "</p>";
        
        // Count unread messages from other user
        $unread = $supabase->customQuery('messages', '*', 
            'conversation_id=eq.' . $conv['conversation_id'] . '&sender_id=neq.' . $user_id . '&is_read=eq.false');
        
        echo "<p>Unread messages from other user: " . count($unread) . "</p>";
        
        if (!empty($unread)) {
            echo "<pre>";
            print_r($unread);
            echo "</pre>";
        }
        
        $total_unread += count($unread);
    }
    
    echo "<hr>";
    echo "<h3>Summary</h3>";
    echo "<p><strong>Total unread messages: $total_unread</strong></p>";
    
    if ($total_unread > 0) {
        echo "<p style='color:green;'>✅ Red notification dot SHOULD appear on sidebar</p>";
    } else {
        echo "<p style='color:orange;'>⚠️ No unread messages - notification dot will NOT appear</p>";
        echo "<p>To test: Have another user send you a message, or manually set is_read=false in database</p>";
    }
}

// Test: Set a message as unread for testing
echo "<hr>";
echo "<h3>Quick Test</h3>";
echo "<p>Want to test the notification? <a href='test_unread.php?mark_unread=1'>Click here to mark a message as unread</a></p>";

if (isset($_GET['mark_unread'])) {
    // Find any message sent TO this user
    $any_msg = $supabase->customQuery('messages', '*', 
        'sender_id=neq.' . $user_id . '&limit=1');
    
    if (!empty($any_msg)) {
        $msg_id = $any_msg[0]['message_id'];
        $supabase->update('messages', ['is_read' => false], ['message_id' => $msg_id]);
        echo "<p style='color:green;'>✅ Marked message $msg_id as unread! Refresh any page to see the notification.</p>";
    } else {
        echo "<p style='color:red;'>❌ No messages found from other users. Have someone send you a message first.</p>";
    }
}
?>
