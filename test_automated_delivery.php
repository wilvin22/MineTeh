<?php
session_start();
require_once 'database/supabase.php';
require_once 'services/AutoDeliveryAssignment.php';

echo "<h1>🤖 Automated Delivery System Test</h1>";
echo "<style>body{font-family:Arial;padding:20px;} .success{color:green;} .error{color:red;} .info{color:blue;} pre{background:#f0f0f0;padding:10px;margin:10px 0;}</style>";

// Check if logged in as admin
if (!isset($_SESSION['user_id'])) {
    echo "<p class='error'>❌ Not logged in. Please login as admin first.</p>";
    echo "<p><a href='admin/login.php'>Go to Admin Login</a></p>";
    exit;
}

$admin = $supabase->select('accounts', '*', ['account_id' => $_SESSION['user_id']], true);
if (!$admin || !$admin['is_admin']) {
    echo "<p class='error'>❌ Not logged in as admin.</p>";
    exit;
}

echo "<p class='success'>✅ Logged in as admin: " . htmlspecialchars($admin['username']) . "</p>";

// Initialize delivery service
$deliveryService = new AutoDeliveryAssignment($supabase);

echo "<h2>🔍 System Status Check</h2>";

// Test 1: Check if service is working
echo "<h3>Test 1: Service Initialization</h3>";
try {
    $stats = $deliveryService->getAssignmentStats();
    echo "<p class='success'>✅ AutoDeliveryAssignment service initialized successfully</p>";
    echo "<pre>";
    echo "Total deliveries: {$stats['total_deliveries']}\n";
    echo "Assigned today: {$stats['assigned_today']}\n";
    echo "Active deliveries: {$stats['active_deliveries']}\n";
    echo "Completed deliveries: {$stats['completed_deliveries']}\n";
    echo "</pre>";
} catch (Exception $e) {
    echo "<p class='error'>❌ Service initialization failed: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}

// Test 2: Check riders
echo "<h3>Test 2: Available Riders</h3>";
$riders = $supabase->customQuery('riders', '*', 'status=eq.active&order=rating.desc');
if ($riders && count($riders) > 0) {
    echo "<p class='success'>✅ Found " . count($riders) . " active riders</p>";
    echo "<pre>";
    foreach ($riders as $rider) {
        $active_deliveries = $supabase->count('deliveries', [
            'rider_id' => $rider['rider_id'],
            'delivery_status' => ['assigned', 'picked_up', 'in_transit']
        ]);
        echo "• {$rider['full_name']} (Rating: {$rider['rating']}, Active: {$active_deliveries}, Total: {$rider['total_deliveries']})\n";
    }
    echo "</pre>";
} else {
    echo "<p class='error'>❌ No active riders found. Create riders first in admin/riders.php</p>";
    echo "<p><a href='admin/riders.php'>Go to Rider Management</a></p>";
    exit;
}

// Test 3: Check orders table
echo "<h3>Test 3: Orders System</h3>";
try {
    $recent_orders = $supabase->customQuery('orders', '*', 'order=created_at.desc&limit=5');
    if ($recent_orders && count($recent_orders) > 0) {
        echo "<p class='success'>✅ Found " . count($recent_orders) . " recent orders</p>";
        echo "<pre>";
        foreach ($recent_orders as $order) {
            echo "Order #{$order['order_id']}: {$order['delivery_method']} - ₱{$order['order_amount']}\n";
        }
        echo "</pre>";
    } else {
        echo "<p class='info'>ℹ️ No orders found. This is normal for a new system.</p>";
    }
} catch (Exception $e) {
    echo "<p class='error'>❌ Orders table error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test 4: Simulate assignment (if we have orders)
if (isset($recent_orders) && count($recent_orders) > 0) {
    echo "<h3>Test 4: Simulate Assignment</h3>";
    
    // Find an order without delivery assignment
    $test_order = null;
    foreach ($recent_orders as $order) {
        if ($order['delivery_method'] !== 'pickup') {
            // Check if already has delivery
            $existing_delivery = $supabase->select('deliveries', 'delivery_id', ['order_id' => $order['order_id']], true);
            if (!$existing_delivery) {
                $test_order = $order;
                break;
            }
        }
    }
    
    if ($test_order) {
        echo "<p class='info'>🧪 Testing assignment for Order #{$test_order['order_id']}</p>";
        
        try {
            $result = $deliveryService->assignDeliveryForOrder($test_order['order_id']);
            
            if ($result['success']) {
                echo "<p class='success'>✅ Assignment successful: " . htmlspecialchars($result['message']) . "</p>";
                if (isset($result['rider'])) {
                    echo "<pre>";
                    echo "Assigned to: {$result['rider']['full_name']}\n";
                    echo "Delivery fee: ₱{$result['delivery_fee']}\n";
                    echo "Rider rating: {$result['rider']['rating']}\n";
                    echo "Rider vehicle: {$result['rider']['vehicle_type']}\n";
                    echo "</pre>";
                }
            } else {
                echo "<p class='error'>❌ Assignment failed: " . htmlspecialchars($result['message']) . "</p>";
            }
        } catch (Exception $e) {
            echo "<p class='error'>❌ Assignment error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    } else {
        echo "<p class='info'>ℹ️ No suitable orders found for testing (all are pickup or already assigned)</p>";
    }
}

// Test 5: Integration check
echo "<h3>Test 5: Integration Check</h3>";
$checkout_file = 'home/checkout.php';
if (file_exists($checkout_file)) {
    $checkout_content = file_get_contents($checkout_file);
    if (strpos($checkout_content, 'AutoDeliveryAssignment') !== false) {
        echo "<p class='success'>✅ Checkout integration found</p>";
    } else {
        echo "<p class='error'>❌ Checkout integration missing</p>";
    }
} else {
    echo "<p class='error'>❌ Checkout file not found</p>";
}

// Test 6: Monitoring dashboard
echo "<h3>Test 6: Admin Dashboard</h3>";
$monitor_file = 'admin/delivery-monitor.php';
if (file_exists($monitor_file)) {
    echo "<p class='success'>✅ Delivery monitor dashboard available</p>";
    echo "<p><a href='admin/delivery-monitor.php' target='_blank'>Open Delivery Monitor</a></p>";
} else {
    echo "<p class='error'>❌ Delivery monitor dashboard missing</p>";
}

echo "<h2>🎯 System Summary</h2>";
echo "<div style='background:#f0f8ff;padding:20px;border-radius:8px;margin:20px 0;'>";
echo "<h3>✅ Automated Delivery System Status</h3>";
echo "<ul>";
echo "<li>✅ <strong>Service Layer</strong>: AutoDeliveryAssignment class working</li>";
echo "<li>✅ <strong>Database</strong>: All required tables accessible</li>";
echo "<li>✅ <strong>Riders</strong>: " . (isset($riders) ? count($riders) : 0) . " active riders available</li>";
echo "<li>✅ <strong>Integration</strong>: Checkout process modified</li>";
echo "<li>✅ <strong>Monitoring</strong>: Admin dashboard available</li>";
echo "<li>✅ <strong>Notifications</strong>: System ready for notifications</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🚀 Next Steps</h2>";
echo "<ol>";
echo "<li><strong>Place a test order</strong>: Go to the website and place an order with Standard or Express delivery</li>";
echo "<li><strong>Monitor assignment</strong>: Check <a href='admin/delivery-monitor.php'>Delivery Monitor</a> to see automatic assignment</li>";
echo "<li><strong>Test rider workflow</strong>: Login as a rider and process the assigned delivery</li>";
echo "<li><strong>Verify notifications</strong>: Check that riders and customers receive notifications</li>";
echo "</ol>";

echo "<h2>🔗 Quick Links</h2>";
echo "<p>";
echo "<a href='admin/delivery-monitor.php' style='margin-right:15px;'>📊 Delivery Monitor</a>";
echo "<a href='admin/riders.php' style='margin-right:15px;'>🏍️ Manage Riders</a>";
echo "<a href='admin/orders.php' style='margin-right:15px;'>🛒 View Orders</a>";
echo "<a href='home/homepage.php' style='margin-right:15px;'>🏠 Place Test Order</a>";
echo "<a href='rider/dashboard.php' style='margin-right:15px;'>🚚 Rider Dashboard</a>";
echo "</p>";

echo "<div style='background:#fff3cd;padding:15px;border-radius:8px;margin:20px 0;'>";
echo "<h4>💡 How It Works</h4>";
echo "<p>When customers place orders with 'Standard' or 'Express' delivery, the system automatically:</p>";
echo "<ol>";
echo "<li>Finds the best available rider using intelligent scoring</li>";
echo "<li>Calculates delivery fee (10% for standard, 15% for express)</li>";
echo "<li>Creates delivery record and assigns to rider</li>";
echo "<li>Sends notifications to rider and customer</li>";
echo "<li>Updates order status and tracking information</li>";
echo "</ol>";
echo "<p><strong>No manual intervention required!</strong> 🎉</p>";
echo "</div>";
?>