<?php
session_start();
date_default_timezone_set('Asia/Manila');

include '../config.php';
include '../database/supabase.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: login.php");
    exit;
}

// Handle order actions
if (isset($_POST['delete_order'])) {
    $order_id = (int)$_POST['order_id'];
    $supabase->delete('orders', ['order_id' => $order_id]);
    $success = "Order deleted successfully";
}

if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['new_status'];
    $supabase->update('orders', ['order_status' => $new_status, 'updated_at' => date('Y-m-d H:i:s')], ['order_id' => $order_id]);
    $success = "Order status updated successfully";
}

// Get all orders
$orders = $supabase->customQuery('orders', '*', 'order=order_date.desc');

// Get buyer and seller names, listing titles
if (!empty($orders)) {
    foreach ($orders as &$order) {
        $buyer = $supabase->select('accounts', 'username', ['account_id' => $order['buyer_id']], true);
        $seller = $supabase->select('accounts', 'username', ['account_id' => $order['seller_id']], true);
        $listing = $supabase->select('listings', 'title', ['id' => $order['listing_id']], true);
        
        $order['buyer_name'] = $buyer ? $buyer['username'] : 'Unknown';
        $order['seller_name'] = $seller ? $seller['username'] : 'Unknown';
        $order['listing_title'] = $listing ? $listing['title'] : 'Unknown';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Admin</title>
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

        .card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .success {
            background: #d1e7dd;
            color: #0f5132;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
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

        .badge-pending, .badge-processing {
            background: #fff3cd;
            color: #856404;
        }

        .badge-shipped {
            background: #cfe2ff;
            color: #084298;
        }

        .badge-delivered {
            background: #d1e7dd;
            color: #0f5132;
        }

        .badge-cancelled {
            background: #f8d7da;
            color: #842029;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            margin-right: 5px;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-danger {
            background: #e74c3c;
            color: white;
        }

        select {
            padding: 6px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <div class="logo">🛡️ Admin Panel</div>
            <a href="index.php" class="nav-item">📊 Dashboard</a>
            <a href="users.php" class="nav-item">👥 Users</a>
            <a href="riders.php" class="nav-item">🏍️ Riders</a>
            <a href="delivery-monitor.php" class="nav-item">📊 Delivery Monitor</a>
            <a href="listings.php" class="nav-item">📦 Listings</a>
            <a href="orders.php" class="nav-item active">🛒 Orders</a>
            <a href="categories.php" class="nav-item">🏷️ Categories</a>
            <a href="../home/homepage.php" class="nav-item">🏠 View Site</a>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Orders Management</h1>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>

            <?php if (isset($success)): ?>
                <div class="success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="card">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Listing</th>
                            <th>Buyer</th>
                            <th>Seller</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($orders)): ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['order_id']; ?></td>
                                    <td><?php echo htmlspecialchars($order['listing_title']); ?></td>
                                    <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['seller_name']); ?></td>
                                    <td>₱<?php echo number_format($order['order_amount'], 2); ?></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($order['order_status']); ?>">
                                            <?php echo ucfirst($order['order_status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                    <td>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                            <select name="new_status">
                                                <option value="pending">Pending</option>
                                                <option value="processing">Processing</option>
                                                <option value="shipped">Shipped</option>
                                                <option value="delivered">Delivered</option>
                                                <option value="cancelled">Cancelled</option>
                                            </select>
                                            <button type="submit" name="update_status" class="btn btn-primary">Update</button>
                                        </form>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure?');">
                                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                                            <button type="submit" name="delete_order" class="btn btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
