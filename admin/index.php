<?php
session_start();
date_default_timezone_set('Asia/Manila');

include '../config.php';
include '../database/supabase.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}

// Get statistics
$total_users = $supabase->count('accounts', []);
$total_listings = $supabase->count('listings', []);
$total_orders = $supabase->count('orders', []);
$active_listings = $supabase->count('listings', ['status' => 'active']);

// Get recent orders
$recent_orders = $supabase->customQuery('orders', '*', 'order=order_date.desc&limit=5');

// Calculate total revenue
$all_orders = $supabase->select('orders', 'order_amount', []);
$total_revenue = 0;
if (!empty($all_orders)) {
    foreach ($all_orders as $order) {
        $total_revenue += $order['order_amount'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - MineTeh</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 260px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 40px;
            text-align: center;
        }

        .nav-item {
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            color: white;
            display: block;
        }

        .nav-item:hover, .nav-item.active {
            background: rgba(255,255,255,0.2);
        }

        .main-content {
            margin-left: 260px;
            flex: 1;
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 32px;
            color: #333;
        }

        .logout-btn {
            padding: 10px 20px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
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
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .stat-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #666;
            font-size: 14px;
        }

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }

        .card-title {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 20px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: bold;
            color: #333;
        }

        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: bold;
        }

        .badge-success {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #842029;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="logo">🛡️ Admin Panel</div>
            <a href="index.php" class="nav-item active">📊 Dashboard</a>
            <a href="users.php" class="nav-item">👥 Users</a>
            <a href="riders.php" class="nav-item">🏍️ Riders</a>
            <a href="delivery-monitor.php" class="nav-item">📊 Delivery Monitor</a>
            <a href="listings.php" class="nav-item">📦 Listings</a>
            <a href="orders.php" class="nav-item">🛒 Orders</a>
            <a href="categories.php" class="nav-item">🏷️ Categories</a>
            <a href="../home/homepage.php" class="nav-item">🏠 View Site</a>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Dashboard</h1>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">👥</div>
                    <div class="stat-value"><?php echo $total_users; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">📦</div>
                    <div class="stat-value"><?php echo $total_listings; ?></div>
                    <div class="stat-label">Total Listings</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">🛒</div>
                    <div class="stat-value"><?php echo $total_orders; ?></div>
                    <div class="stat-label">Total Orders</div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">💰</div>
                    <div class="stat-value">₱<?php echo number_format($total_revenue, 0); ?></div>
                    <div class="stat-label">Total Revenue</div>
                </div>
            </div>

            <div class="card">
                <h2 class="card-title">Recent Orders</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_orders)): ?>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td>₱<?php echo number_format($order['order_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge badge-<?php 
                                            echo $order['order_status'] === 'delivered' ? 'success' : 
                                                ($order['order_status'] === 'cancelled' ? 'danger' : 'warning'); 
                                        ?>">
                                            <?php echo ucfirst($order['order_status']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #666;">No orders yet</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
