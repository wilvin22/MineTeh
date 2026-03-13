<?php
// Debug script to check what's causing the delivery monitor error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Delivery Monitor Debug</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

echo "<h2>Step 1: Check Basic PHP</h2>";
echo "<p class='success'>✅ PHP is working</p>";

echo "<h2>Step 2: Check Session</h2>";
session_start();
if (isset($_SESSION['user_id'])) {
    echo "<p class='success'>✅ Session active: User ID " . $_SESSION['user_id'] . "</p>";
} else {
    echo "<p class='error'>❌ No session found</p>";
}

echo "<h2>Step 3: Check Database Connection</h2>";
try {
    require_once 'database/supabase.php';
    echo "<p class='success'>✅ Database connection loaded</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

echo "<h2>Step 4: Check Services Directory</h2>";
if (is_dir('services')) {
    echo "<p class='success'>✅ Services directory exists</p>";
} else {
    echo "<p class='error'>❌ Services directory missing</p>";
    echo "<p class='info'>Creating services directory...</p>";
    mkdir('services', 0755, true);
    echo "<p class='success'>✅ Services directory created</p>";
}

echo "<h2>Step 5: Check AutoDeliveryAssignment File</h2>";
$service_file = 'services/AutoDeliveryAssignment.php';
if (file_exists($service_file)) {
    echo "<p class='success'>✅ AutoDeliveryAssignment.php exists</p>";
    
    // Check if file is readable
    if (is_readable($service_file)) {
        echo "<p class='success'>✅ File is readable</p>";
        
        // Try to include it
        try {
            require_once $service_file;
            echo "<p class='success'>✅ File included successfully</p>";
            
            // Try to instantiate the class
            try {
                $deliveryService = new AutoDeliveryAssignment($supabase);
                echo "<p class='success'>✅ Class instantiated successfully</p>";
                
                // Try to get stats
                try {
                    $stats = $deliveryService->getAssignmentStats();
                    echo "<p class='success'>✅ Stats retrieved successfully</p>";
                    echo "<pre>" . print_r($stats, true) . "</pre>";
                } catch (Exception $e) {
                    echo "<p class='error'>❌ Stats error: " . htmlspecialchars($e->getMessage()) . "</p>";
                }
            } catch (Exception $e) {
                echo "<p class='error'>❌ Class instantiation error: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ File include error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class='error'>❌ File is not readable</p>";
    }
} else {
    echo "<p class='error'>❌ AutoDeliveryAssignment.php missing</p>";
}

echo "<h2>Step 6: Check Admin Access</h2>";
if (isset($_SESSION['user_id'])) {
    try {
        $admin = $supabase->select('accounts', '*', ['account_id' => $_SESSION['user_id']], true);
        if ($admin && $admin['is_admin']) {
            echo "<p class='success'>✅ Admin access confirmed</p>";
        } else {
            echo "<p class='error'>❌ Not an admin user</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Admin check error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
} else {
    echo "<p class='error'>❌ No user session</p>";
}

echo "<h2>Step 7: Check Required Tables</h2>";
$required_tables = ['riders', 'deliveries', 'orders', 'accounts'];
foreach ($required_tables as $table) {
    try {
        $count = $supabase->count($table, []);
        echo "<p class='success'>✅ Table '$table' exists (count: $count)</p>";
    } catch (Exception $e) {
        echo "<p class='error'>❌ Table '$table' error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

echo "<h2>🔧 Recommended Actions</h2>";
echo "<ol>";
echo "<li>If services directory was missing, it's now created</li>";
echo "<li>If AutoDeliveryAssignment.php is missing, recreate it</li>";
echo "<li>If there are table errors, run the SQL migrations</li>";
echo "<li>If admin access fails, login as admin first</li>";
echo "</ol>";

echo "<p><a href='admin/delivery-monitor.php'>Try Delivery Monitor Again</a></p>";
?>