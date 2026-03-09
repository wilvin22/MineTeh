<?php
session_start();
date_default_timezone_set('Asia/Manila');

include 'config.php';
include 'database/supabase.php';

echo "<h2>Listing Details Restriction Debug</h2>";
echo "<hr>";

// Check session
echo "<h3>Session Info:</h3>";
echo "User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "<br>";
echo "Username: " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'NOT SET') . "<br>";
echo "<hr>";

// Check user status from database
if (isset($_SESSION['user_id'])) {
    echo "<h3>Database User Status:</h3>";
    $user = $supabase->select('accounts', 'user_status, restriction_until, status_reason', ['account_id' => $_SESSION['user_id']], true);
    
    if ($user) {
        echo "user_status: " . (isset($user['user_status']) ? $user['user_status'] : 'NOT SET') . "<br>";
        echo "restriction_until: " . (isset($user['restriction_until']) ? $user['restriction_until'] : 'NULL') . "<br>";
        echo "status_reason: " . (isset($user['status_reason']) ? $user['status_reason'] : 'NULL') . "<br>";
        
        $user_status = isset($user['user_status']) ? $user['user_status'] : 'active';
        
        echo "<hr>";
        echo "<h3>Restriction Check Logic:</h3>";
        
        if ($user_status === 'restricted') {
            echo "✅ User status is 'restricted'<br>";
            
            $restriction_until = isset($user['restriction_until']) ? $user['restriction_until'] : null;
            echo "restriction_until value: " . ($restriction_until ? $restriction_until : 'NULL') . "<br>";
            
            if ($restriction_until) {
                $expiry_timestamp = strtotime($restriction_until);
                $current_timestamp = time();
                echo "Expiry timestamp: " . $expiry_timestamp . " (" . date('Y-m-d H:i:s', $expiry_timestamp) . ")<br>";
                echo "Current timestamp: " . $current_timestamp . " (" . date('Y-m-d H:i:s', $current_timestamp) . ")<br>";
                
                if ($expiry_timestamp <= $current_timestamp) {
                    echo "⏰ Restriction EXPIRED - should reactivate<br>";
                } else {
                    echo "⚠️ Restriction ACTIVE - user_is_restricted should be TRUE<br>";
                }
            } else {
                echo "⚠️ Permanent restriction - user_is_restricted should be TRUE<br>";
            }
        } else {
            echo "❌ User status is NOT 'restricted' (status: " . $user_status . ")<br>";
            echo "user_is_restricted should be FALSE<br>";
        }
        
        // Simulate the actual check
        $user_is_restricted = false;
        if ($user_status === 'restricted') {
            $restriction_until = isset($user['restriction_until']) ? $user['restriction_until'] : null;
            
            if ($restriction_until && strtotime($restriction_until) <= time()) {
                echo "<br><strong>RESULT: user_is_restricted = FALSE (expired)</strong><br>";
            } else {
                $user_is_restricted = true;
                echo "<br><strong>RESULT: user_is_restricted = TRUE</strong><br>";
            }
        } else {
            echo "<br><strong>RESULT: user_is_restricted = FALSE (not restricted)</strong><br>";
        }
        
    } else {
        echo "❌ Could not fetch user from database<br>";
        $error = $supabase->getLastError();
        if ($error) {
            echo "Error: " . print_r($error, true) . "<br>";
        }
    }
} else {
    echo "❌ No user logged in<br>";
}

echo "<hr>";
echo "<h3>What Should Happen:</h3>";
echo "If user_is_restricted = TRUE, the bid form should be hidden and replaced with:<br>";
echo "<pre>";
echo htmlspecialchars('<div style="text-align: center; padding: 20px; background: #fff3cd; color: #856404; border-radius: 8px;">
    <strong>⚠️ Account Restricted</strong>
    <p style="margin: 8px 0 0 0; font-size: 14px;">You cannot place bids while your account is restricted.</p>
    <a href="homepage.php" style="display: inline-block; margin-top: 10px; padding: 8px 16px; background: #667eea; color: white; text-decoration: none; border-radius: 6px; font-size: 14px;">View Details</a>
</div>');
echo "</pre>";

echo "<hr>";
echo "<p><a href='home/listing-details.php?id=1'>Test with Listing ID 1</a></p>";
echo "<p><a href='home/homepage.php'>Go to Homepage</a></p>";
?>
