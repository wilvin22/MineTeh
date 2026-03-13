<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
date_default_timezone_set('Asia/Manila');

echo "<h1>Simple Delivery Monitor</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// Check admin access
if (!isset($_SESSION['user_id'])) {
    echo "<p class='error'>❌ Not logged in</p>";
    echo "<p><a href='login.php'>Login</a></p>";
    exit;
}

echo "<p class='success'>✅ Session active</p>";

// Load database
try {
    require_once '../database/supabase.php';
    echo "<p class='success'>✅ Database loaded</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
    exit;
}

// Check admin
try {
    $admin = $supabase->select('accounts', '*', ['account_id' => $_SESSION['user_id']], true);
    if ($admin && $admin['is_admin']) {
        echo "<p class='success'>✅ Admin access confirmed</p>";
    } else {
        echo "<p class='error'>❌ Not admin</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Admin check error: " . $e->getMessage() . "</p>";
    exit;
}

// Get basic stats
echo "<h2>📊 Basic Statistics</h2>";
try {
    $total_deliveries = $supabase->count('deliveries', []);
    echo "<p class='info'>Total deliveries: $total_deliveries</p>";
    
    $active_deliveries = $supabase->count('deliveries', [
        'delivery_status' => ['assigned', 'picked_up', 'in_transit']
    ]);
    echo "<p class='info'>Active deliveries: $active_deliveries</p>";
    
    $completed_deliveries = $supabase->count('deliveries', [
        'delivery_status' => 'delivered'
    ]);
    echo "<p class='info'>Completed deliveries: $completed_deliveries</p>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Stats error: " . $e->getMessage() . "</p>";
}

// Get recent deliveries
echo "<h2>📦 Recent Deliveries</h2>";
try {
    $recent_deliveries = $supabase->customQuery('deliveries', '*', 'order=created_at.desc&limit=10');
    
    if ($recent_deliveries && count($recent_deliveries) > 0) {
        echo "<table border='1' style='border-collapse:collapse; width:100%;'>";
        echo "<tr><th>ID</th><th>Status</th><th>Created</th><th>Rider ID</th><th>Customer</th></tr>";
        
        foreach ($recent_deliveries as $delivery) {
            echo "<tr>";
            echo "<td>#{$delivery['delivery_id']}</td>";
            echo "<td>{$delivery['delivery_status']}</td>";
            echo "<td>" . date('M d, Y H:i', strtotime($delivery['created_at'])) . "</td>";
            echo "<td>{$delivery['rider_id']}</td>";
            echo "<td>" . htmlspecialchars($delivery['customer_name'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p class='info'>No deliveries found</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Deliveries error: " . $e->getMessage() . "</p>";
}

// Test service loading
echo "<h2>🔧 Service Test</h2>";
$service_path = '../services/AutoDeliveryAssignment.php';
echo "<p class='info'>Looking for service at: $service_path</p>";

if (file_exists($service_path)) {
    echo "<p class='success'>✅ Service file exists</p>";
    
    try {
        require_once $service_path;
        echo "<p class='success'>✅ Service loaded</p>";
        
        try {
            $service = new AutoDeliveryAssignment($supabase);
            echo "<p class='success'>✅ Service instantiated</p>";
            
            $stats = $service->getAssignmentStats();
            echo "<p class='success'>✅ Service working</p>";
            echo "<pre>" . print_r($stats, true) . "</pre>";
            
        } catch (Exception $e) {
            echo "<p class='error'>❌ Service instantiation error: " . $e->getMessage() . "</p>";
        }
    } catch (Exception $e) {
        echo "<p class='error'>❌ Service load error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p class='error'>❌ Service file not found</p>";
    echo "<p class='info'>Current directory: " . getcwd() . "</p>";
    echo "<p class='info'>Files in parent directory:</p>";
    $files = scandir('..');
    echo "<pre>" . print_r($files, true) . "</pre>";
}

echo "<h2>🔗 Navigation</h2>";
echo "<p><a href='delivery-monitor.php'>Try Full Delivery Monitor</a></p>";
echo "<p><a href='index.php'>Admin Dashboard</a></p>";
?>