<?php
session_start();
require_once '../database/supabase.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit();
}

// Get statistics
try {
    // Total users
    $total_users = $supabase->count('users');
    
    // Total listings
    $total_listings = $supabase->count('listings');
    
    // Active listings
    $active_listings = $supabase->count('listings', ['status' => 'OPEN']);
    
    // Total orders
    $total_orders = $supabase->count('orders');
    
    // Pending orders
    $pending_orders = $supabase->count('orders', ['status' => 'pending']);
    
    // Total bids
    $total_bids = $supabase->count('bids');
    
    // Recent users (last 5)
    $recent_users = $supabase->customQuery('users', '*', 'order=created_at.desc&limit=5');
    if ($recent_users === false) {
        $recent_users = [];
    }
    
    // Recent listings (last 5)
    $recent_listings = $supabase->customQuery('listings', '*', 'order=created_at.desc&limit=5');
    if ($recent_listings === false) {
        $recent_listings = [];
    }
    
    // Recent orders (last 5)
    $recent_orders = $supabase->customQuery('orders', '*', 'order=created_at.desc&limit=5');
    if ($recent_orders === false) {
        $recent_orders = [];
    }
    
} catch (Exception $e) {
    $error_message = "Error loading dashboard: " . $e->getMessage();
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
            background: #f5f5f5;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 600;
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        .header-actions a {
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
            transition: all 0.3s;
        }

        .header-actions a:hover {
            background: rgba(255,255,255,0.3);
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
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }

        .stat-icon {
            font-size: 40px;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 14px;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .content-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
        }

        .section {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .section-title {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            color: #333;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        .list-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .list-item:last-child {
            border-bottom: none;
        }

        .list-item:hover {
            background: #f9f9f9;
        }

        .item-info {
            flex: 1;
        }

        .item-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .item-meta {
            font-size: 13px;
            color: #999;
        }

        .badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-success {
            background: #d4edda;
            color: #155724;
        }

        .badge-warning {
            background: #fff3cd;
            color: #856404;
        }

        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .badge-info {
            background: #d1ecf1;
            color: #0c5460;
        }

        .empty-state {
            text-align: center;
            padding: 40px;
            color: #999;
        }

        .empty-state-icon {
            font-size: 60px;
            margin-bottom: 15px;
            opacity: 0.5;
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .header-content {
                flex-direction: column;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>📊 Admin Dashboard</h1>
            <div class="header-actions">
                <a href="users.php">👥 Users</a>
                <a href="riders.php">🏍️ Riders</a>
                <a href="delivery-monitor.php">📊 Delivery Monitor</a>
                <a href="listings.php">📦 Listings</a>
                <a href="orders.php">🛒 Orders</a>
                <a href="categories.php">🏷️ Categories</a>
                <a href="../home/homepage.php">🏠 Home</a>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="container">
        <?php if (isset($error_message)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">👥</div>
                <div class="stat-value"><?php echo number_format($total_users); ?></div>
                <div class="stat-label">Total Users</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">📦</div>
                <div class="stat-value"><?php echo number_format($total_listings); ?></div>
                <div class="stat-label">Total Listings</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value"><?php echo number_format($active_listings); ?></div>
                <div class="stat-label">Active Listings</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🛒</div>
                <div class="stat-value"><?php echo number_format($total_orders); ?></div>
                <div class="stat-label">Total Orders</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-value"><?php echo number_format($pending_orders); ?></div>
                <div class="stat-label">Pending Orders</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">🔨</div>
                <div class="stat-value"><?php echo number_format($total_bids); ?></div>
                <div class="stat-label">Total Bids</div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="content-grid">
            <!-- Recent Users -->
            <div class="section">
                <div class="section-title">Recent Users</div>
                <?php if (empty($recent_users)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">👤</div>
                        <div>No users yet</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_users as $user): ?>
                        <div class="list-item">
                            <div class="item-info">
                                <div class="item-title"><?php echo htmlspecialchars($user['username']); ?></div>
                                <div class="item-meta">
                                    <?php echo htmlspecialchars($user['email']); ?> • 
                                    <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                </div>
                            </div>
                            <span class="badge badge-<?php echo $user['status'] == 'active' ? 'success' : ($user['status'] == 'restricted' ? 'warning' : 'danger'); ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Recent Listings -->
            <div class="section">
                <div class="section-title">Recent Listings</div>
                <?php if (empty($recent_listings)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📦</div>
                        <div>No listings yet</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_listings as $listing): ?>
                        <div class="list-item">
                            <div class="item-info">
                                <div class="item-title"><?php echo htmlspecialchars($listing['title']); ?></div>
                                <div class="item-meta">
                                    ₱<?php echo number_format($listing['price'], 2); ?> • 
                                    <?php echo $listing['listing_type']; ?> • 
                                    <?php echo date('M d, Y', strtotime($listing['created_at'])); ?>
                                </div>
                            </div>
                            <span class="badge badge-<?php echo $listing['status'] == 'OPEN' ? 'success' : 'danger'; ?>">
                                <?php echo $listing['status']; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Recent Orders -->
            <div class="section">
                <div class="section-title">Recent Orders</div>
                <?php if (empty($recent_orders)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">🛒</div>
                        <div>No orders yet</div>
                    </div>
                <?php else: ?>
                    <?php foreach ($recent_orders as $order): ?>
                        <div class="list-item">
                            <div class="item-info">
                                <div class="item-title">Order #<?php echo $order['order_id']; ?></div>
                                <div class="item-meta">
                                    ₱<?php echo number_format($order['total_amount'], 2); ?> • 
                                    <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                </div>
                            </div>
                            <span class="badge badge-<?php 
                                echo $order['status'] == 'delivered' ? 'success' : 
                                    ($order['status'] == 'pending' ? 'warning' : 'info'); 
                            ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        console.log('Admin dashboard loaded');
        
        // Auto-refresh every 60 seconds
        setTimeout(() => {
            location.reload();
        }, 60000);
    </script>
</body>
</html>
