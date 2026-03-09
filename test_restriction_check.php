<?php
session_start();
date_default_timezone_set('Asia/Manila');

include 'config.php';
include 'database/supabase.php';

echo "<!DOCTYPE html><html><head><title>Restriction Check Test</title></head><body>";
echo "<h2>Restriction Check Test - Version 3</h2>";
echo "<hr>";

// Check session
echo "<h3>1. Session Info:</h3>";
echo "User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'NOT SET') . "<br>";
echo "Username: " . (isset($_SESSION['username']) ? $_SESSION['username'] : 'NOT SET') . "<br>";

// Check user status from database
$user_is_restricted = false;
if (isset($_SESSION['user_id'])) {
    echo "<hr><h3>2. Database Query:</h3>";
    echo "Querying for account_id: " . $_SESSION['user_id'] . "<br>";
    $user = $supabase->select('accounts', 'user_status, restriction_until, status_reason', ['account_id' => $_SESSION['user_id']], true);
    
    echo "Query result type: " . gettype($user) . "<br>";
    if ($user === false) {
        echo "❌ Query returned FALSE (error occurred)<br>";
        $error = $supabase->getLastError();
        if ($error) {
            echo "<pre>Error details: " . print_r($error, true) . "</pre>";
        }
    } elseif (empty($user)) {
        echo "❌ Query returned empty (no user found)<br>";
    }
    
    if ($user && is_array($user)) {
        echo "✅ User found in database<br>";
        echo "user_status: <strong>" . (isset($user['user_status']) ? $user['user_status'] : 'NOT SET') . "</strong><br>";
        echo "restriction_until: " . (isset($user['restriction_until']) ? $user['restriction_until'] : 'NULL') . "<br>";
        echo "status_reason: " . (isset($user['status_reason']) ? $user['status_reason'] : 'NULL') . "<br>";
        
        $user_status = isset($user['user_status']) ? $user['user_status'] : 'active';
        
        echo "<hr><h3>3. Restriction Logic:</h3>";
        
        if ($user_status === 'restricted') {
            echo "✅ User status is 'restricted'<br>";
            
            $restriction_until = isset($user['restriction_until']) ? $user['restriction_until'] : null;
            
            if ($restriction_until && strtotime($restriction_until) <= time()) {
                echo "⏰ Restriction EXPIRED - user should be reactivated<br>";
                $user_is_restricted = false;
            } else {
                echo "⚠️ Restriction ACTIVE<br>";
                $user_is_restricted = true;
            }
        } else {
            echo "❌ User status is NOT 'restricted' (status: " . $user_status . ")<br>";
            $user_is_restricted = false;
        }
        
        echo "<hr><h3>4. Final Result:</h3>";
        echo "<strong style='font-size: 20px; color: " . ($user_is_restricted ? 'red' : 'green') . ";'>";
        echo "user_is_restricted = " . ($user_is_restricted ? 'TRUE' : 'FALSE');
        echo "</strong><br>";
        
        echo "<hr><h3>5. What Should Display:</h3>";
        if ($user_is_restricted) {
            echo "<div style='text-align: center; padding: 20px; background: #fff3cd; color: #856404; border-radius: 8px; max-width: 400px;'>";
            echo "<strong>⚠️ Account Restricted</strong>";
            echo "<p style='margin: 8px 0 0 0; font-size: 14px;'>You cannot place bids while your account is restricted.</p>";
            echo "</div>";
        } else {
            echo "<p>✅ User should see the normal bid form</p>";
        }
        
    } else {
        echo "❌ Could not fetch user from database<br>";
    }
} else {
    echo "<hr><p>❌ No user logged in</p>";
}

echo "<hr>";
echo "<p><a href='home/homepage.php'>Go to Homepage</a></p>";
echo "<p><a href='home/listing-details.php?id=1'>Go to Listing Details (old file)</a></p>";
echo "</body></html>";
?>
