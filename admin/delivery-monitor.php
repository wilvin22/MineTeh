<?php
session_start();
date_default_timezone_set('Asia/Manila');

require_once '../database/supabase.php';

// Check if user is admin
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$admin = $supabase->select('accounts', '*', ['account_id' => $_SESSION['user_id']], true);
if (!$admin || !$admin['is_admin']) {
    die('Access denied. Admin only.');
}

// Initialize stats with basic queries (fallback if service fails)
$stats = [
    'total_deliveries' => 0,
    'assigned_today' => 0,
    'active_deliveries' => 0,
    'completed_deliveries' => 0
];

$debug_info = [];

try {
    // Try to get basic stats directly from database
    $stats['total_deliveries'] = $supabase->count('deliveries', []);
    $debug_info[] = "✅ Total deliveries count: " . $stats['total_deliveries'];
    
    $today_deliveries = $supabase->customQuery('deliveries', 'delivery_id', 
        'created_at=gte.' . date('Y-m-d') . 'T00:00:00');
    $stats['assigned_today'] = $today_deliveries ? count($today_deliveries) : 0;
    $debug_info[] = "✅ Today's deliveries count: " . $stats['assigned_today'];
    
    $stats['active_deliveries'] = $supabase->count('deliveries', [
        'delivery_status' => ['assigned', 'picked_up', 'in_transit']
    ]);
    $debug_info[] = "✅ Active deliveries count: " . $stats['active_deliveries'];
    
    $stats['completed_deliveries'] = $supabase->count('deliveries', [
        'delivery_status' => 'delivered'
    ]);
    $debug_info[] = "✅ Completed deliveries count: " . $stats['completed_deliveries'];
} catch (Exception $e) {
    error_log('Stats error: ' . $e->getMessage());
    $debug_info[] = "❌ Stats error: " . $e->getMessage();
    
    // Get last error details
    $lastError = $supabase->getLastError();
    if ($lastError) {
        $debug_info[] = "❌ HTTP Code: " . $lastError['http_code'];
        $debug_info[] = "❌ URL: " . $lastError['url'];
        $debug_info[] = "❌ Response: " . $lastError['response'];
    }
}

// Try to load the AutoDeliveryAssignment service
$deliveryService = null;
$service_available = false;

try {
    if (file_exists('../services/AutoDeliveryAssignment.php')) {
        require_once '../services/AutoDeliveryAssignment.php';
        $deliveryService = new AutoDeliveryAssignment($supabase);
        $service_available = true;
        
        // Get enhanced stats if service is available
        $stats = $deliveryService->getAssignmentStats();
    }
} catch (Exception $e) {
    error_log('AutoDeliveryAssignment service error: ' . $e->getMessage());
    $service_available = false;
}

// Get recent deliveries with order and rider info
$recent_deliveries = [];
try {
    $recent_deliveries = $supabase->customQuery('deliveries', '*', 'order=created_at.desc&limit=20');
    
    // Enrich delivery data
    if ($recent_deliveries) {
        foreach ($recent_deliveries as &$delivery) {
            // Get rider info
            $rider = $supabase->select('riders', 'full_name,phone_number,vehicle_type', ['rider_id' => $delivery['rider_id']], true);
            $delivery['rider_name'] = $rider ? $rider['full_name'] : 'Unknown';
            $delivery['rider_phone'] = $rider ? $rider['phone_number'] : 'N/A';
            $delivery['rider_vehicle'] = $rider ? $rider['vehicle_type'] : 'N/A';
            
            // Get order info if available
            if (isset($delivery['order_id']) && $delivery['order_id']) {
                $order = $supabase->select('orders', 'order_amount,payment_method', ['order_id' => $delivery['order_id']], true);
                $delivery['order_amount'] = $order ? $order['order_amount'] : 0;
                $delivery['payment_method'] = $order ? $order['payment_method'] : 'N/A';
            } else {
                $delivery['order_amount'] = 0;
                $delivery['payment_method'] = 'N/A';
            }
        }
    }
} catch (Exception $e) {
    error_log('Recent deliveries error: ' . $e->getMessage());
    $recent_deliveries = [];
}

// Handle manual assignment (emergency override)
if (isset($_POST['manual_assign']) && $service_available) {
    $order_id = (int)$_POST['order_id'];
    $result = $deliveryService->assignDeliveryForOrder($order_id);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
    
    // Refresh page to show updated data
    header("Location: delivery-monitor.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Monitor - MineTeh Admin</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }

        .navbar {
            background: #2c3e50;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .navbar h1 {
            font-size: 24px;
        }

        .navbar a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            padding: 8px 16px;
            border-radius: 4px;
            transition: background 0.3s;
        }

        .navbar a:hover {
            background: rgba(255,255,255,0.1);
        }

        .container {
            max-width: 1400px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }

        .stat-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 20px;
        }

        .card h2 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .delivery-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .delivery-table th,
        .delivery-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        .delivery-table th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }

        .status-assigned {
            background: #fff3cd;
            color: #856404;
        }

        .status-picked_up {
            background: #cfe2ff;
            color: #084298;
        }

        .status-in_transit {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-delivered {
            background: #d1e7dd;
            color: #0f5132;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-primary {
            background: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background: #2980b9;
        }

        .btn-success {
            background: #27ae60;
            color: white;
        }

        .btn-success:hover {
            background: #219a52;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }

        .auto-assignment-info {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .auto-assignment-info h3 {
            color: #0066cc;
            margin-bottom: 10px;
        }

        .auto-assignment-info ul {
            margin-left: 20px;
            color: #333;
        }

        .auto-assignment-info li {
            margin-bottom: 5px;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .delivery-table {
                font-size: 12px;
            }
            
            .delivery-table th,
            .delivery-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>📊 Delivery Monitor</h1>
        <div>
            <a href="index.php">Dashboard</a>
            <a href="riders.php">Riders</a>
            <a href="orders.php">Orders</a>
            <a href="delivery-monitor.php">Delivery Monitor</a>
            <a href="users.php">Users</a>
            <a href="listings.php">Listings</a>
            <a href="categories.php">Categories</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="container">
        <!-- Service Status Alert -->
        <?php if (!$service_available): ?>
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                <h3 style="color: #856404; margin-bottom: 10px;">⚠️ Service Status</h3>
                <p style="color: #856404;">
                    The AutoDeliveryAssignment service is not available. Basic monitoring is active, but automated assignment may not be working.
                </p>
                <p style="margin-top: 10px;">
                    <a href="../test_delivery_monitor.php" style="color: #856404; text-decoration: underline;">Run Diagnostic</a>
                </p>
            </div>
        <?php else: ?>
            <div style="background: #d1e7dd; border: 1px solid #badbcc; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                <p style="color: #0f5132; margin: 0;">
                    ✅ <strong>Automated Delivery Service Active</strong> - All systems operational
                </p>
            </div>
        <?php endif; ?>

        <!-- Debug Information -->
        <?php if (!empty($debug_info)): ?>
            <div style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                <h3 style="color: #495057; margin-bottom: 10px;">🔧 Debug Information</h3>
                <?php foreach ($debug_info as $info): ?>
                    <p style="margin: 5px 0; font-family: monospace; font-size: 12px;"><?php echo htmlspecialchars($info); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($success)): ?>
            <div class="success-message">✓ <?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="error-message">⚠️ <?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Automated Assignment Info -->
        <div class="auto-assignment-info">
            <h3>🤖 Automated Delivery Assignment System</h3>
            <p>Deliveries are automatically assigned to riders when orders are placed. The system uses intelligent algorithms to:</p>
            <ul>
                <li>Select the best available rider based on rating, workload, and experience</li>
                <li>Calculate delivery fees automatically (10% for standard, 15% for express)</li>
                <li>Skip assignment for pickup orders</li>
                <li>Send notifications to riders and customers</li>
                <li>Update order status and tracking information</li>
            </ul>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-value"><?php echo $stats['total_deliveries']; ?></div>
                <div class="stat-label">Total Deliveries</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🆕</div>
                <div class="stat-value"><?php echo $stats['assigned_today']; ?></div>
                <div class="stat-label">Assigned Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🚚</div>
                <div class="stat-value"><?php echo $stats['active_deliveries']; ?></div>
                <div class="stat-label">Active Deliveries</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo $stats['completed_deliveries']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
        </div>

        <!-- Recent Deliveries -->
        <div class="card">
            <h2>🚚 Recent Automated Assignments</h2>
            
            <?php if (empty($recent_deliveries)): ?>
                <p style="text-align: center; color: #666; padding: 40px;">
                    No deliveries assigned yet. Deliveries will appear here automatically when orders are placed.
                </p>
            <?php else: ?>
                <table class="delivery-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Order</th>
                            <th>Rider</th>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Fee</th>
                            <th>Assigned</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_deliveries as $delivery): ?>
                            <tr>
                                <td>#<?php echo $delivery['delivery_id']; ?></td>
                                <td>
                                    <?php if (isset($delivery['order_id'])): ?>
                                        Order #<?php echo $delivery['order_id']; ?>
                                        <br><small>₱<?php echo number_format($delivery['order_amount'] ?? 0, 2); ?></small>
                                    <?php else: ?>
                                        Manual Assignment
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($delivery['rider_name']); ?></strong>
                                    <br><small><?php echo htmlspecialchars($delivery['rider_vehicle']); ?></small>
                                    <br><small><?php echo htmlspecialchars($delivery['rider_phone']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo htmlspecialchars($delivery['customer_name']); ?></strong>
                                    <br><small><?php echo htmlspecialchars($delivery['customer_phone']); ?></small>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $delivery['delivery_status']; ?>">
                                        <?php echo strtoupper(str_replace('_', ' ', $delivery['delivery_status'])); ?>
                                    </span>
                                </td>
                                <td>₱<?php echo number_format($delivery['delivery_fee'], 2); ?></td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($delivery['created_at'])); ?>
                                    <br><small><?php echo date('h:i A', strtotime($delivery['created_at'])); ?></small>
                                </td>
                                <td>
                                    <a href="../rider/delivery-details.php?id=<?php echo $delivery['delivery_id']; ?>" 
                                       class="btn btn-primary" target="_blank">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <!-- Manual Assignment (Emergency Override) -->
        <?php if ($service_available): ?>
        <div class="card">
            <h2>⚠️ Manual Assignment (Emergency Override)</h2>
            <p style="margin-bottom: 20px; color: #666;">
                Use this only if automatic assignment fails. Enter an order ID to manually trigger delivery assignment.
            </p>
            
            <form method="POST" style="display: flex; gap: 10px; align-items: center;">
                <input type="number" name="order_id" placeholder="Order ID" required 
                       style="padding: 10px; border: 1px solid #ddd; border-radius: 4px;">
                <button type="submit" name="manual_assign" class="btn btn-success">
                    Assign Delivery
                </button>
            </form>
        </div>
        <?php else: ?>
        <div class="card">
            <h2>⚠️ Manual Assignment (Service Unavailable)</h2>
            <p style="margin-bottom: 20px; color: #666;">
                Manual assignment is not available because the AutoDeliveryAssignment service is not loaded.
            </p>
            <p>
                <a href="../debug_delivery_monitor.php" class="btn btn-primary">Run Diagnostic</a>
            </p>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-refresh every 30 seconds
        setTimeout(() => {
            location.reload();
        }, 30000);
        
        // Add loading state to manual assignment
        document.querySelector('form').addEventListener('submit', function() {
            const btn = this.querySelector('button');
            btn.disabled = true;
            btn.textContent = 'Assigning...';
        });
    </script>
</body>
</html>