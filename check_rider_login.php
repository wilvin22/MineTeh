<?php
session_start();
require_once 'database/supabase.php';

echo "<h1>Rider Login Debug</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} pre{background:#f0f0f0;padding:10px;}</style>";

// Check if logged in
if (!isset($_SESSION['user_id'])) {
    echo "<p class='error'>❌ Not logged in. Please login first.</p>";
    echo "<p><a href='login.php'>Go to Login</a></p>";
    exit;
}

echo "<h2>Current Session:</h2>";
echo "<pre>";
echo "user_id: " . $_SESSION['user_id'] . "\n";
echo "username: " . ($_SESSION['username'] ?? 'not set') . "\n";
echo "is_admin: " . (($_SESSION['is_admin'] ?? false) ? 'true' : 'false') . "\n";
echo "is_rider: " . (($_SESSION['is_rider'] ?? false) ? 'true' : 'false') . "\n";
echo "</pre>";

// Get user data from database
echo "<h2>Database Check:</h2>";
try {
    $user = $supabase->select('accounts', '*', ['account_id' => $_SESSION['user_id']], true);
    
    if ($user) {
        echo "<pre>";
        echo "account_id: " . $user['account_id'] . "\n";
        echo "username: " . $user['username'] . "\n";
        echo "email: " . $user['email'] . "\n";
        echo "is_admin: " . (($user['is_admin'] ?? false) ? 'true' : 'false') . "\n";
        echo "is_rider: " . (($user['is_rider'] ?? false) ? 'true' : 'false') . "\n";
        echo "</pre>";
        
        // Check if is_rider column exists
        if (!isset($user['is_rider'])) {
            echo "<p class='error'>❌ WARNING: 'is_rider' column does not exist in accounts table!</p>";
            echo "<p>You need to run the SQL migration to add this column.</p>";
        } else {
            if ($user['is_rider']) {
                echo "<p class='success'>✅ This account IS a rider</p>";
                
                // Check if rider exists in riders table
                $rider = $supabase->select('riders', '*', ['account_id' => $user['account_id']], true);
                if ($rider) {
                    echo "<p class='success'>✅ Rider profile exists</p>";
                    echo "<pre>";
                    echo "rider_id: " . $rider['rider_id'] . "\n";
                    echo "full_name: " . $rider['full_name'] . "\n";
                    echo "phone_number: " . $rider['phone_number'] . "\n";
                    echo "status: " . $rider['status'] . "\n";
                    echo "</pre>";
                } else {
                    echo "<p class='error'>❌ Rider profile NOT found in riders table</p>";
                }
            } else {
                echo "<p class='error'>❌ This account is NOT a rider (is_rider = false)</p>";
                echo "<p>The admin needs to set is_rider = true for this account.</p>";
            }
        }
    } else {
        echo "<p class='error'>❌ User not found in database</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<h2>What Should Happen:</h2>";
echo "<ul>";
echo "<li>If is_rider = TRUE → Redirect to rider/dashboard.php</li>";
echo "<li>If is_rider = FALSE → Redirect to home/homepage.php</li>";
echo "<li>If is_admin = TRUE → Redirect to admin-dashboard.php</li>";
echo "</ul>";

echo "<h2>Actions:</h2>";
echo "<p><a href='rider/dashboard.php'><button>Try Accessing Rider Dashboard</button></a></p>";
echo "<p><a href='logout.php'><button>Logout</button></a></p>";
echo "<p><a href='login.php'><button>Login Again</button></a></p>";
?>
