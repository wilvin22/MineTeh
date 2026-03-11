<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Delivery Monitor Test</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;}</style>";

// Test database connection
try {
    require_once 'database/supabase.php';
    echo "<p class='success'>✅ Database loaded</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Database error: " . $e->getMessage() . "</p>";
    exit;
}

// Test basic count
echo "<h2>Testing Basic Count</h2>";
try {
    $total = $supabase->count('deliveries', []);
    echo "<p class='info'>Total deliveries: $total</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Basic count error: " . $e->getMessage() . "</p>";
}

// Test array count (the problematic one)
echo "<h2>Testing Array Count (IN operator)</h2>";
try {
    $active = $supabase->count('deliveries', [
        'delivery_status' => ['assigned', 'picked_up', 'in_transit']
    ]);
    echo "<p class='success'>✅ Active deliveries: $active</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Array count error: " . $e->getMessage() . "</p>";
    
    // Show last error details
    $lastError = $supabase->getLastError();
    if ($lastError) {
        echo "<p class='error'>HTTP Code: " . $lastError['http_code'] . "</p>";
        echo "<p class='error'>URL: " . $lastError['url'] . "</p>";
        echo "<p class='error'>Response: " . $lastError['response'] . "</p>";
    }
}

// Test single value count
echo "<h2>Testing Single Value Count</h2>";
try {
    $completed = $supabase->count('deliveries', [
        'delivery_status' => 'delivered'
    ]);
    echo "<p class='success'>✅ Completed deliveries: $completed</p>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Single count error: " . $e->getMessage() . "</p>";
}

// Test service loading
echo "<h2>Testing AutoDeliveryAssignment Service</h2>";
try {
    if (file_exists('services/AutoDeliveryAssignment.php')) {
        require_once 'services/AutoDeliveryAssignment.php';
        $service = new AutoDeliveryAssignment($supabase);
        echo "<p class='success'>✅ Service loaded</p>";
        
        $stats = $service->getAssignmentStats();
        echo "<p class='success'>✅ Service stats retrieved</p>";
        echo "<pre>" . print_r($stats, true) . "</pre>";
    } else {
        echo "<p class='error'>❌ Service file not found</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Service error: " . $e->getMessage() . "</p>";
}

echo "<h2>🔗 Navigation</h2>";
echo "<p><a href='admin/delivery-monitor.php'>Try Full Delivery Monitor</a></p>";
echo "<p><a href='admin/delivery-monitor-simple.php'>Try Simple Delivery Monitor</a></p>";
?>